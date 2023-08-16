<?php

namespace BeeAZ\ASkyBlock;

use BeeAZ\ASkyBlock\language\Language;
use BeeAZ\ASkyBlock\commands\ASkyBlockCommand;
use BeeAZ\ASkyBlock\task\CheckUpdateTask;
use BeeAZ\ASkyBlock\skyblock\SkyBlock;
use BeeAZ\ASkyBlock\listener\EventListener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class ASkyBlock extends PluginBase {

    public static $instance;

    protected function onEnable(): void {
        self::$instance = $this;
        $this->saveResource("config.yml");
        Language::init();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        if (!is_dir($this->getDataFolder() . "/world")) $this->getSkyBlock()->copyFolder($this->getFile() . "/resources/world", $this->getDataFolder() . "/world");
        if (!is_dir($this->getDataFolder() . "/language")) $this->getSkyBlock()->copyFolder($this->getFile() . "/resources/language", $this->getDataFolder() . "/language");
        $this->getServer()->getCommandMap()->register("askyblock", new ASkyBlockCommand());
        $this->getServer()->getAsyncPool()->submitTask(new CheckUpdateTask($this->getDescription()->getName(), $this->getDescription()->getVersion()));
    }

    public static function getInstance(): self {
        return self::$instance;
    }

    public function getWorldManager() {
        return $this->getServer()->getWorldManager();
    }

    public function getWorldPath() {
        return $this->getServer()->getDataPath() . "/worlds";
    }

    public function getSkyBlock() {
        return new SkyBlock();
    }

    public function getDataManager() {
        return new Config($this->getDataFolder() . "config.yml");
    }
}
