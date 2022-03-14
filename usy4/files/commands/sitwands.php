<?php 

namespace usy4\files\commands;

use usy4\files\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\Plugin;
use pocketmine\player\Player;
use pocketmine\nbt\tag\ListTag;

class sitwands extends Command implements PluginOwned
{

	public $plugin;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		parent::__construct("sitwand", " Give you 2 wands");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {

		if(!$sender instanceof Player) {
			$sender->sendMessage("use this command in game");
			return;
		}
        $this->plugin->addWands($sender);
	}

    public function getOwningPlugin(): Plugin{
        return $this->plugin;
        }

}
