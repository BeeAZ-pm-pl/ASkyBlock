<?php

namespace BeeAZ\ASkyBlock\form;

use BeeAZ\ASkyBlock\ASkyBlock;
use BeeAZ\ASkyBlock\utils\Utils;
use BeeAZ\FormBuilderX\SimpleForm;
use BeeAZ\FormBuilderX\CustomForm;
use pocketmine\Server;
use pocketmine\player\Player;

class FormManager {

  public $playerList;

  public function startForm(Player $player) {
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new SimpleForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      switch ($data) {
        case 0:
          if (!$player->hasPermission("askyblock.create")) return;
          if ($skyblock->haveIsland($player->getName())) {
            $skyblock->teleport($player, $player->getName());
          } else {
            $this->openCreate($player);
          }
          break;
        case 1:
          if (!$player->hasPermission("askyblock.teleport")) return;
          $this->openTeleport($player);
          break;
        case 2:
          if (!$player->hasPermission("askyblock.settings")) return;
          if ($skyblock->haveIsland($player->getName())) {
            $this->openSettings($player);
          } else $player->sendMessage(Utils::getMessage("island-not-exists"));
          break;
        case 3:
          if (!$player->hasPermission("askyblock.top")) return;
          $this->openTop($player);
          break;
        case 4:
          if (!$player->hasPermission("askyblock.info")) return;
          $this->openInfo($player);
          break;
        case 5:
          if (!$player->hasPermission("askyblock.delete")) return;
          $this->openDelete($player);
          break;
      }
    });
    $form->setTitle(Utils::getMessage("form-manager-title"));
    $form->setContent(Utils::getMessage("form-manager-content"));
    $form->addButton(Utils::getMessage("form-join"));
    $form->addButton(Utils::getMessage("form-teleport"));
    $form->addButton(Utils::getMessage("form-settings"));
    $form->addButton(Utils::getMessage("form-top"));
    $form->addBUtton(Utils::getMessage("form-info"));
    if ($player->hasPermission("askyblock.delete")) {
      $form->addButton(Utils::getMessage("form-delete"));
    }
    $player->sendForm($form);
  }

  public function openCreate(Player $player) {
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new CustomForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      $path = ASkyBlock::getInstance()->getDataFolder() . "/world";
      $type = scandir($path);
      $type = array_values(array_diff($type, ['.', '..']));
      $skyblock->create($player->getName(), $type[$data[1]]);
      $player->sendMessage(Utils::getMessage("create-success"));
    });
    $path = ASkyBlock::getInstance()->getDataFolder() . "/world";
    $type = scandir($path);
    $type = array_values(array_diff($type, ['.', '..']));
    $form->setTitle(Utils::getMessage("form-create-title"));
    $form->addLabel(Utils::getMessage("form-create-label"));
    $form->addDropdown(Utils::getMessage("form-create-dropdown"), $type);
    $player->sendForm($form);
  }

  public function openTeleport(Player $player) {
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new CustomForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      if (isset($data[1]) && is_string($data[1])) {
        if ($skyblock->haveIsland($data[1])) {
          if (!$skyblock->isLocked($data[1]) || $player->hasPermission("askyblock.bybass")) {
            if (!$skyblock->isBanned($data[1], $player->getName()) || $player->hasPermission("askyblock.bybass")) {
              $skyblock->teleport($player, $data[1]);
              $player->sendMessage(Utils::getMessage("teleport-success"));
            } else $player->sendMessage(Utils::getMessage("teleport-banned"));
          } else $player->sendMessage(Utils::getMessage("teleport-locked"));
        } else $player->sendMessage(Utils::getMessage("island-not-exists"));
      } else $player->sendMessage(Utils::getMessage("teleport-data-wrong"));
    });
    $form->setTitle(Utils::getMessage("form-teleport-title"));
    $form->addLabel(Utils::getMessage("form-teleport-label"));
    $form->addInput(Utils::getMessage("form-teleport-input"));
    $player->sendForm($form);
  }

  public function openTop(Player $player) {
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new SimpleForm(function (Player $player, $data) {
      if ($data === null) return;
    });
    $form->setTitle(Utils::getMessage("form-top-title"));
    $form->setContent($skyblock->getTop());
    $player->sendForm($form);
  }

  public function openDelete(Player $player) {
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new CustomForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      if (isset($data[1]) && is_string($data[1])) {
        if ($skyblock->haveIsland($data[1])) {
          $skyblock->delete($data[1]);
        } else $player->sendMessage(Utils::getMessage("island-not-exists"));
      } else $player->sendMessage(Utils::getMessage("delete-data-wrong"));
    });
    $form->setTitle(Utils::getMessage("form-delete-title"));
    $form->addLabel(Utils::getMessage("form-delete-label"));
    $form->addInput(Utils::getMessage("form-delete-input"));
    $player->sendForm($form);
  }

  public function openSettings(Player $player) {
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new SimpleForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      switch ($data) {
        case 0:
          if (!$player->hasPermission("askyblock.settings.setspawn")) return;
          if ($player->getWorld()->getFolderName() == strtolower($player->getname())) {
            $skyblock->setSpawn($player->getName(), $player->getPosition());
            $player->sendMessage(Utils::getMessage("setspawn-success"));
          } else $player->sendMessage(Utils::getMessage("setspawn-error"));
          break;
        case 1:
          if (!$player->hasPermission("askyblock.settings.friends")) return;
          $this->openFriendsManager($player);
          break;
        case 2:
          if (!$player->hasPermission("askyblock.settings.kick")) return;
          $this->openKick($player);
          break;
        case 3:
          if (!$player->hasPermission("askyblock.settings.lock")) return;
          $skyblock->isLocked($player->getName()) ? $skyblock->unLocked($player->getName()) : $skyblock->setLocked($player->getName());
          break;
        case 4:
          if (!$player->hasPermission("askyblock.settings.pvp")) return;
          $skyblock->allowPvP($player->getName()) ? $skyblock->lockPvP($player->getName()) : $skyblock->unlockPvP($player->getName());
          break;
        case 5:
          if (!$player->hasPermission("askyblock.settings.ban")) return;
          $this->openBan($player);
          break;
        case 6:
          if (!$player->hasPermission("askyblock.settings.setwelcome")) return;
          $this->openWelcomeManager($player);
          break;
      }
    });
    $form->setTitle(Utils::getMessage("form-settings-title"));
    $form->setContent(Utils::getMessage("form-settings-content"));
    $form->addButton(Utils::getMessage("form-setspawn"));
    $form->addButton(Utils::getMessage("form-friends"));
    $form->addButton(Utils::getMessage("form-kick"));
    $form->addButton(str_replace("{status}", $skyblock->isLocked($player->getName()) ? "on" : "off", Utils::getMessage("form-lock")));
    $form->addButton(str_replace("{status}", $skyblock->allowPvP($player->getName()) ? "on" : "off", Utils::getMessage("form-pvp")));
    $form->addButton(Utils::getMessage("form-ban"));
    $form->addButton(Utils::getMessage("form-welcome"));
    $player->sendForm($form);
  }

  public function openWelcomeManager(Player $player) {
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new CustomForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      if (isset($data[1]) && is_string($data[1])) {
        $skyblock->setWelcomeMessage($player->getName(), $data[1]);
        $player->sendMessage(Utils::getMessage("setwelcome-success"));
      } else $player->sendMessage(Utils::getMessage("setwelcome-data-wrong"));
    });
    $form->setTitle(Utils::getMessage("form-welcome-title"));
    $form->addLabel(Utils::getMessage("form-welcome-label"));
    $form->addInput(Utils::getMessage("form-welcome-input"));
    $player->sendForm($form);
  }
  public function openKick(Player $player) {
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new CustomForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      if (isset($data[1]) && is_string($data[1])) {
        $visitor = Server::getInstance()->getPlayerByPrefix($data[1]);
        if ($visitor !== null && $skyblock->kick($player->getName(), $visitor)) {
          $visitor->sendMessage(Utils::getMessage("kick-player"));
          $player->sendMessage(Utils::getMessage("kick-success"));
        } else $player->sendMessage(Utils::getMessage("kick-error"));
      } else $player->sendMessage(Utils::getMessage("kick-data-wrong"));
    });
    $form->setTitle(Utils::getMessage("form-kick-title"));
    $form->addLabel(Utils::getMessage("form-kick-label"));
    $form->addInput(Utils::getMessage("form-kick-input"));
    $player->sendForm($form);
  }

  public function openBan(Player $player) {
    $list = [];
    foreach (Server::getInstance()->getOnlinePlayers() as $p) {
      $list[] = $p->getName();
    }
    $this->playerList[$player->getName()] = $list;
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new CustomForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      $playerName = $this->playerList[$player->getName()][$data[2]];
      $data[1] == 0 ? $skyblock->setBan($player->getName(), $playerName) : $skyblock->unBan($player->getName(), $playerName);
    });
    $form->setTitle(Utils::getMessage("form-ban-title"));
    $form->addLabel(str_replace("{list}", $skyblock->listBanned($player->getName()), Utils::getMessage("form-ban-label")));
    $form->addToggle(Utils::getMessage("form-ban-toggle"));
    $form->addDropdown(Utils::getMessage("form-ban-dropdown"), $this->playerList[$player->getName()]);
    $player->sendForm($form);
  }

  public function openFriendsManager(Player $player) {
    $list = [];
    foreach (Server::getInstance()->getOnlinePlayers() as $p) {
      $list[] = $p->getName();
    }
    $this->playerList[$player->getName()] = $list;
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new CustomForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      $playerName = $this->playerList[$player->getName()][$data[2]];
      $data[1] == 0 ? $skyblock->addFriends($player->getName(), $playerName) : $skyblock->removeFriends($player->getName(), $playerName);
    });
    $form->setTitle(Utils::getMessage("form-friends-title"));
    $form->addLabel(str_replace("{list}", $skyblock->listFriends($player->getName()), Utils::getMessage("form-friends-label")));
    $form->addToggle(Utils::getMessage("form-friends-toggle"));
    $form->addDropdown(Utils::getMessage("form-friends-dropdown"), $this->playerList[$player->getName()]);
    $player->sendForm($form);
  }

  public function openInfo(Player $player, $text = '') {
    $list = [];
    foreach (Server::getInstance()->getOnlinePlayers() as $p) {
      $list[] = $p->getName();
    }
    $this->playerList[$player->getName()] = $list;
    $skyblock = ASkyBlock::getInstance()->getSkyBlock();
    $form = new CustomForm(function (Player $player, $data) use ($skyblock) {
      if ($data === null) return;
      $playerName = $this->playerList[$player->getName()][$data[1]];
      $this->openInfo($player, $skyblock->getInfo($playerName));
    });
    $form->setTitle(Utils::getMessage("form-info-title"));
    $form->addLabel($text);
    $form->addDropdown(Utils::getMessage("form-info-dropdown"), $this->playerList[$player->getName()]);
    $player->sendForm($form);
  }
}
