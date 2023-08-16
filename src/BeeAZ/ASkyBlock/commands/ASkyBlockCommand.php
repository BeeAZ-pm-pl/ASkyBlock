<?php

namespace BeeAZ\ASkyBlock\commands;

use BeeAZ\ASkyBlock\ASkyBlock;
use BeeAZ\ASkyBlock\form\FormManager;
use pocketmine\plugin\PluginOwned;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ASkyBlockCommand extends Command implements PluginOwned {

  public function __construct() {
    parent::__construct("askyblock", "SkyBlock Command", null, ["skyblock", "sb", "is"]);
    $this->setPermission("askyblock.command");
  }

  public function execute(CommandSender $sender, string $label, array $args) {
    $form = new FormManager();
    if ($sender instanceof Player) $form->startForm($sender);
  }

  public function getOwningPlugin(): ASkyBlock {
    return ASkyBlock::getInstance();
  }
}
