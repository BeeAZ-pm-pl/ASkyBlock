<?php

namespace BeeAZ\ASkyBlock\skyblock;

use BeeAZ\ASkyBlock\ASkyBlock;
use BeeAZ\ASkyBlock\utils\Utils;
use pocketmine\world\format\io\data\BaseNbtWorldData;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\Server;
use pocketmine\player\Player;
use RuntimeException;
use ErrorException;
use pocketmine\block\tile\Chest;
use pocketmine\item\StringToItemParser;

class SkyBlock {

  public function copyFolder(string $sourceFolder, string $destinationFolder) {
    if (!file_exists($destinationFolder)) {
      mkdir($destinationFolder, 0777, true);
    }
    $files = scandir($sourceFolder);
    foreach ($files as $file) {
      if ($file == "." || $file == "..") {
        continue;
      }
      $source = $sourceFolder . "/" . $file;
      $destination = $destinationFolder . "/" . $file;
      if (is_dir($source)) {
        $this->copyFolder($source, $destination);
      } else {
        copy($source, $destination);
      }
    }
  }

  public function deleteFolder(string $dirPath) {
    if (!is_dir($dirPath)) {
      return "Folder not found";
    }
    $dirHandle = opendir($dirPath);
    while (($file = readdir($dirHandle)) !== false) {
      if ($file != '.' && $file != '..') {
        $filePath = $dirPath . '/' . $file;
        if (is_dir($filePath)) {
          $this->deleteFolder($filePath);
        } else {
          unlink($filePath);
        }
      }
    }
    closedir($dirHandle);
    rmdir($dirPath);
  }

  public function genWorld(string $newName, string $type) {
    $skyblock = ASkyBlock::getInstance();
    $from = $skyblock->getWorldPath() . "/$type";
    $to = $skyblock->getWorldPath() . "/$newName";
    try {
      rename($from, $to);
    } catch (ErrorException $e) {
      throw new RuntimeException("Unable to create island $newName : {$e->getMessage()}");
    }
    $skyblock->getWorldManager()->loadWorld($newName);
    $world = $skyblock->getWorldManager()->getWorldByName($newName);
    if (!$world instanceof World) {
      return;
    }
    $nbt = $world->getProvider()->getWorldData();
    if (!$nbt instanceof BaseNbtWorldData) {
      return;
    }
    $nbt->getCompoundTag()->setString("LevelName", $newName);
    $skyblock->getWorldManager()->unloadWorld($world);
    $skyblock->getWorldManager()->loadWorld($newName);
  }

  public function removeWorld(string $name) {
    $skyblock = ASkyBlock::getInstance();
    $world = $skyblock->getWorldManager()->getWorldByName($name);
    if (count($world->getPlayers()) > 0) {
      foreach ($world->getPlayers() as $player) {
        $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
      }
    }
    $skyblock->getWorldManager()->unloadWorld($world);
    $path = $skyblock->getWorldPath() . "/" . $name;
    $this->deleteFolder($path);
  }

  public function create(string $islandName, string $type) {
    $skyblock = ASkyBlock::getInstance();
    $islandName = strtolower($islandName);
    $dir = $skyblock->getDataFolder() . "/world/$type";
    if (is_dir($dir)) {
      $this->copyFolder($dir, $skyblock->getWorldPath() . "/$type");
      $this->genWorld($islandName, $type);
      Utils::createSimpleConfig($islandName);
      $this->addItemToChest($islandName, $type);
      return true;
    }
    return false;
  }

  public function addItemToChest(string $islandName, string $type) {
    $skyblock = ASkyBlock::getInstance();
    $islandName = strtolower($islandName);
    $config = $skyblock->getDataManager();
    $skyblock->getWorldManager()->loadWorld($islandName);
    $world = $skyblock->getWorldManager()->getWorldByName($islandName);
    $ex = explode(":", $config->getNested("chest-start-position." . $type, "0:0:0"));
    $world->loadChunk($ex[0], $ex[2]);
    $chest = $world->getTile(new Position($ex[0], $ex[1], $ex[2], $world));
    if ($chest instanceof Chest) {
      $chest->setName($islandName . " Chest");
      foreach ($config->get("items-start") as $items) {
        if (strpos($items, ':')) {
          $items = explode(":", $items);
        } else {
          $items = [$items];
        }
        $item = StringToItemParser::getInstance()->parse($items[0])->setCount($items[1] ?? 1);
        $chest->getInventory()->addItem($item);
      }
    }
  }

  public function delete(string $islandName) {
    $islandName = strtolower($islandName);
    $this->removeWorld($islandName);
  }

  public function teleport(Player $player, string $islandName) {
    $islandName = strtolower($islandName);
    $worldManager = ASkyBlock::getInstance()->getWorldManager();
    $worldManager->loadWorld($islandName);
    $player->teleport($worldManager->getWorldByName($islandName)->getSafeSpawn());
    $player->sendMessage(Utils::queryData($islandName)->getNested("settings.welcomeMessage"));
  }

  public function kick(string $islandName, Player $player) {
    $islandName = strtolower($islandName);
    if ($player->getWorld()->getFolderName() === $islandName) {
      $player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
      return true;
    }
    return false;
  }

  public function isLocked(string $islandName) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName)->getNested("settings.lock", false);
    return $config;
  }

  public function allowPvP(string $islandName) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName)->getNested("settings.pvp", false);
    return $config;
  }

  public function isFriends(string $islandName, string $name) {
    $islandName = strtolower($islandName);
    $name = strtolower($name);
    $config = Utils::queryData($islandName)->getNested("friends." . $name, false);
    return $config;
  }

  public function isBanned(string $islandName, string $name) {
    $islandName = strtolower($islandName);
    $name = strtolower($name);
    $config = Utils::queryData($islandName)->getNested("settings.ban." . $name, false);
    return $config;
  }

  public function listFriends(string $islandName) {
    $islandName = strtolower($islandName);
    $list = '';
    $config = Utils::queryData($islandName)->getAll()["friends"];
    foreach ($config as $friends => $data) {
      $list .= "- " . $friends . "\n";
    }
    return $list;
  }

  public function listBanned(string $islandName) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName)->getAll()["settings"]["ban"];
    $list = '';
    foreach ($config as $ban => $data) {
      $list .= "- " . $ban . "\n";
    }
    return $list;
  }

  public function addFriends(string $islandName, string $name) {
    $islandName = strtolower($islandName);
    $name = strtolower($name);
    $config = Utils::queryData($islandName);
    if ($config->getNested("friends." . $name, false) || $islandName == $name) {
      return false;
    }
    Server::getInstance()->getPlayerByPrefix($islandName)->sendMessage(Utils::getMessage("addfriends-success"));
    $config->setNested("friends." . $name, true);
    $config->save();
    return true;
  }

  public function removeFriends(string $islandName, string $name) {
    $islandName = strtolower($islandName);
    $name = strtolower($name);
    $config = Utils::queryData($islandName);
    if ($config->getNested("friends." . $name, false) == true) {
      Server::getInstance()->getPlayerByPrefix($islandName)->sendMessage(Utils::getMessage("removefriends-success"));
      $config->removeNested("friends." . $name);
      $config->save();
      return true;
    }
    return false;
  }

  public function setSpawn(string $islandName, Position $position) {
    $islandName = strtolower($islandName);
    $world = ASkyBlock::getInstance()->getWorldManager()->getWorldByName($islandName);
    $world->setSpawnLocation($position);
    $world->save();
  }

  public function haveIsland(string $islandName) {
    $skyblock = ASkyBlock::getInstance();
    $islandName = strtolower($islandName);
    if (file_exists($skyblock->getWorldPath() . "/$islandName/skyblock.yml")) {
      return true;
    }
    return false;
  }

  public function getTop() {
    $skyblock = ASkyBlock::getInstance();
    $path = $skyblock->getWorldPath();
    $folder = scandir($path);
    $top = [];
    $i = 1;
    foreach ($folder as $folderName) {
      if ($folderName == "." || $folderName == ".." || !$this->haveIsland($folderName)) {
        continue;
      }
      if (is_dir($path . "/" . $folderName)) {
        $top[$folderName] = Utils::queryData($folderName)->get("points");
      }
    }
    arsort($top);
    $list = "";
    foreach ($top as $name => $points) {
      if ($i <= 10) {
        $list .= str_replace(["{top}", "{name}", "{points}"], [$i, $name, $points], Utils::getMessage("top-format") . "\n");
        unset($top[$name]);
      }
      $i++;
    }
    return $list;
  }

  public function setLocked(string $islandName) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName);
    Server::getInstance()->getPlayerByPrefix($islandName)->sendMessage(Utils::getMessage("lock-success"));
    $config->setNested("settings.lock", true);
    $config->save();
  }

  public function unLocked(string $islandName) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName);
    Server::getInstance()->getPlayerByPrefix($islandName)->sendMessage(Utils::getMessage("unlock-success"));
    $config->setNested("settings.lock", false);
    $config->save();
  }
  public function lockPvP(string $islandName) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName);
    Server::getInstance()->getPlayerByPrefix($islandName)->sendMessage(Utils::getMessage("lockpvp-success"));
    $config->setNested("settings.pvp", false);
    $config->save();
  }

  public function unlockPvP(string $islandName) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName);
    Server::getInstance()->getPlayerByPrefix($islandName)->sendMessage(Utils::getMessage("unlockpvp-success"));
    $config->setNested("settings.pvp", true);
    $config->save();
  }

  public function setPoints(string $islandName, int $point) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName);
    $config->set("points", $config->get("points") + $point);
    $config->save();
  }

  public function takePoints(string $islandName, int $point) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName);
    $config->set("points", $config->get("points") - $point);
    $config->save();
  }

  public function getBlocks() {
    $config = ASkyBlock::getInstance()->getDataManager();
    $config = $config->get('blocks');
    return $config;
  }

  public function setBan(string $islandName, string $name) {
    $islandName = strtolower($islandName);
    $name = strtolower($name);
    $config = Utils::queryData($islandName);
    if ($config->getNested("settings.ban." . $name, false) || $islandName == $name) {
      return false;
    }
    Server::getInstance()->getPlayerByPrefix($islandName)->sendMessage(Utils::getMessage("ban-success"));
    $config->setNested("settings.ban." . $name, true);
    $config->save();
    return true;
  }

  public function unBan(string $islandName, string $name) {
    $islandName = strtolower($islandName);
    $name = strtolower($name);
    $config = Utils::queryData($islandName);
    if ($config->getNested("settings.ban." . $name, false) == true) {
      Server::getInstance()->getPlayerByPrefix($islandName)->sendMessage(Utils::getMessage("unban-success"));
      $config->removeNested("settings.ban." . $name);
      $config->save();
      return true;
    }
    return false;
  }

  public function setWelcomeMessage(string $islandName, string $text) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName);
    $config->setNested("settings.welcomeMessage", $text);
    $config->save();
  }

  public function getPoint(string $islandName) {
    $islandName = strtolower($islandName);
    $config = Utils::queryData($islandName);
    return $config->get("points");
  }

  public function getInfo(string $islandName) {
    $islandName = strtolower($islandName);
    if ($this->haveIsland($islandName)) {
      $config = Utils::queryData($islandName);
      $info = str_replace(["{lock}", "{pvp}", "{points}"], [$this->isLocked($islandName) ? "on" : "off", $this->allowPvP($islandName) ? "on" : "off", $this->getPoint($islandName)], Utils::getMessage("island-info"));
      return $info;
    }
    return '';
  }
}
