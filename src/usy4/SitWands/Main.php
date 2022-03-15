<?php

namespace usy4\SitWands;

/*  
 *  A plugin for PocketMine-MP.
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 	
 */

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\Entity;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\item\ItemFactory;
use pocketmine\event\Listener;

use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerJumpEvent;

use pocketmine\command\{
	Command, CommandSender
};

use usy4\SitWands\commands\SitWandCommand;

class Main extends PluginBase implements Listener{
	
	/** @var string[] */
    public $sit = [];
	
	/** @var string[] */
    public $onCombat = [];
	
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register($this->getName(), new SitWandCommand($this));
    }
	
    public function addWands(Player $player) 
	{
		$item = ItemFactory::getInstance()->get(280, 0, 1);
		$item2 = ItemFactory::getInstance()->get(369, 0, 1);
		
		$item->setCustomName("§r§dSit You on One §6Wand\n§7[Damage SomeOne]");
		$item2->setCustomName("§r§dSit On You §6Wand\n§7[Damage SomeOne]");
		
		// $player->sendMessage("Done.");
		
		$player->getInventory()->addItem($item);
		$player->getInventory()->addItem($item2);
	}

    public function onDamage(EntityDamageEvent $event): void{
		$entity = $event->getEntity();
		if($entity instanceof Player){
			if($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player){
				$itemName = $damager->getInventory()->getItemInHand()->getCustomName();
				if($itemName == "§r§dSit You on One §6Wand\n§7[Damage SomeOne]"){
					$event->cancel();
					if(isset($this->sit[$damager->getName()]) || isset($this->sit2[$damager->getName()])) {
						$event->cancel();
						$damager->sendTip("§cYou Already In Sit Mode");
						return;
					}
					
					if(isset($this->sit[$entity->getName()]) || isset($this->sit2[$entity->getName()])) {
						$event->cancel();
						$damager->sendTip("§cS/he Already In Sit Mode");
						return;
					}
					
					$this->onHit($entity, $damager);
				}
				
				if($itemName == "§r§dSit On You §6Wand\n§7[Damage SomeOne]"){
					$event->cancel();
					if(isset($this->sit[$damager->getName()]) || isset($this->sit2[$damager->getName()])){
						$event->cancel();
						$damager->sendTip("§cYou Already In Sit Mode");
						return;
					}
					
					if(isset($this->sit[$entity->getName()])) {
						$event->cancel();
						$damager->sendTip("§cS/he Already In Sit Mode");
						return;
					}
					
					$this->onHit($damager, $entity);
				}
			}
		}
	}
	
	public function onHit(Player $player1, Player $player2) {
		if($player2->getName() === $player1->getName()) 
			return;
		
		if(!isset($this->onCombat[$player1->getName()])){
			$player1->sendMessage($player2->getName().'§d sit on You');
		}
		
		if(!isset($this->onCombat[$player2->getName()])){
			$player2->sendMessage('§dYou sit on §r'.$player1->getName());   
		}
		
		// $this->sit[$entity->getName()] = [$damager->getName()];
		$this->sit[$player2->getName()] = [$player1->getName()];
		$this->sit2[$player1->getName()] = [$player2->getName()];
		
		$this->sitOnPlayer($player1, $player2);
	}
	
	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		$player = $event->getOrigin()->getPlayer();
		
		if($packet instanceof InteractPacket){
			if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
				if(isset($this->sit[$player->getName()])){
					$event->cancel();
					$this->dismountFromPlayer($player);
				}
			}
		}
		
		// $packet = $event->getPacket();
		// switch($packet->pid()){
			// case InteractPacket::NETWORK_ID:
				// if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
					// $player = $event->getPlayer();
					// if(isset($this->sit[$player->getName()])){
						// $this->dismountFromPlayer($player);
					// }
					
					// $event->cancel();
				// }
			// break;
		// }
	}
	
	public function sitOnPlayer(Player $damager, Player $entity): bool{
		
		$link = new EntityLink($damager->getId(), $entity->getId(), EntityLink::TYPE_RIDER, true, true);
		$pk = SetActorLinkPacket::create(EntityLink $link);
		
		// $pk = new SetActorLinkPacket();
		// $pk->link = new EntityLink($damager->getId(), $entity->getId(), EntityLink::TYPE_RIDER, true, true);
		
		foreach ($this->getServer()->getOnlinePlayers() as $players) {
			$players->getNetworkSession()->sendDataPacket($pk);
		}
		
		$pk = new SetActorDataPacket();
        $pk->actorRuntimeId = $entity->tagId;
        $pk->metadata = [EntityMetadataProperties::RIDER_SEAT_POSITION => new Vector3(0, +1.6, 0)];
        $entity->getNetworkSession()->sendDataPacket($pk);
		
		// $entity->getNetworkSession()->getDataPropertyManager()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, +1.6, 0));
		return true;
    }
	
	public function dismountFromPlayer(Player $entity): bool {
		foreach($this->sit[$entity->getName()] as $p){
			unset($this->sit2[($p)]);
			unset($this->sit[($p)]);
		}
		
		unset($this->sit[($entity->getName())]);
		$entity->teleport(new Vector3($entity->getPosition()->x, $entity->getPosition()->y + 0.1, $entity->getPosition()->z));
		return true;
	}
	
	public function dismountFromPlayer2(Player $entity): bool {
		foreach($this->sit2[$entity->getName()] as $p){
			unset($this->sit[($p)]);
		}
		
		unset($this->sit2[($entity->getName())]);
		$entity->teleport(new Vector3($entity->getPosition()->x, $entity->getPosition()->y + 0.1, $entity->getPosition()->z));
		return true;
	}
	
	public function onSneak(PlayerToggleSneakEvent $event){
		$player = $event->getPlayer();
		if(!$player instanceof Player)
			return;
		if(isset($this->sit[$player->getName()])){
			$this->dismountFromPlayer($player);
		}
	}
	
	public function onJump(PlayerJumpEvent $event){
		$player = $event->getPlayer();
		if(!$player instanceof Player)
			return;
		if(isset($this->sit[$player->getName()])){
			$this->dismountFromPlayer($player);
		}
    }
	
	public function onTeleport(EntityTeleportEvent $event){
		$player = $event->getEntity();
		if(!$player instanceof Player)
			return;
		if(isset($this->sit[$player->getName()])){
			$this->dismountFromPlayer($player);
		}
	}
	
	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		if(!$player instanceof Player)
			return;
		
		if(isset($this->sit[$player->getName()])){
			$this->dismountFromPlayer($player);
		}
		
		if(isset($this->sit2[$player->getName()])){
			$this->dismountFromPlayer2($player);
		}
	}
	
	public function onDeath(PlayerDeathEvent $event) {
		$player = $event->getPlayer();
		if(!$player instanceof Player)
			return;
		
		if(isset($this->sit[$player->getName()])){
			$this->dismountFromPlayer($player);
		}
		
		if(isset($this->sit2[$player->getName()])){
			$this->dismountFromPlayer2($player);
		}
	}
}
