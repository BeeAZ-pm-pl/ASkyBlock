<?php

namespace BeeAZ\ASkyBlock\utils;

use BeeAZ\ASkyBlock\ASkyBlock;
use BeeAZ\ASkyBlock\language\Language;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class Utils {

  private const DEFAULT_FORMAT = [
    "friends" => [],
    "settings" => [
      "pvp" => false,
      "lock" => false,
      "ban" => [],
      "welcomeMessage" => "Welcome to the island"
    ],
    "points" => 0
  ];

  public static function createSimpleConfig(string $name) {
    $name = strtolower($name);
    $config = new Config(ASkyBlock::getInstance()->getWorldPath() . "/$name/skyblock.yml");
    $config->setAll(self::DEFAULT_FORMAT);
    $config->save();
  }

  public static function queryData(string $islandName) {
    $name = strtolower($islandName);
    return new Config(ASkyBlock::getInstance()->getWorldPath() . "/$name/skyblock.yml");
  }

  public static function getMessage(string $text) {
    return TextFormat::colorize(Language::getLanguage()->get($text));
  }
}
