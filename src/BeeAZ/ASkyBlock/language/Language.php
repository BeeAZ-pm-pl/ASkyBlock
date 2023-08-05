<?php

namespace BeeAZ\ASkyBlock\language;

use BeeAZ\ASkyBlock\ASkyBlock;
use pocketmine\utils\Config;

class Language {

  private static $language;

  public static function init() {
    self::$language = ASkyBlock::getInstance()->getDataManager()->get('language-file', "english");
  }

  public static function getLanguage() {
    return new Config(ASkyBlock::getInstance()->getDataFolder() . '/language/' . self::$language . '.yml', Config::YAML);
  }
}
