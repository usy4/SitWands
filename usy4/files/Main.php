<?php

namespace usy4\files;

use usy4\files\commands\sitwands;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\Entity;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\ListTag;
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

class Main extends PluginBase implements Listener{


	public $sit = [];

	public function onEnable() : void{

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getCommandMap()->register($this->getName(), new FFACommand($this));        
    }

	public function addWands(Player $player) 
	{
		$item = ItemFactory::getInstance()->get(280, 0, 1);
		$item->setCustomName("§r§dSit on the player §6Wand\n§7[Damage SomeOne]");
		$player->getInventory()->addItem($item);
		$item2 = ItemFactory::getInstance()->get(369, 0, 1);
		$item2->setCustomName("§r§dPlayer sit on you §6Wand\n§7[Damage SomeOne]");
		$player->sendMessage("Done.");
		$player->getInventory()->addItem($item2);
	}

    
	public function onDamage(EntityDamageEvent $event): void{
		$entity = $event->getEntity();
		if($entity instanceof Player){
	
			if($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player){
				$item = $damager->getInventory()->getItemInHand()->getName();
				if($item == "§r§dSit You on One §6Wand\n§7[Damage SomeOne]"){
					$event->cancel();
					if(isset($this->sit[$damager->getName()]) or isset($this->sit2[$damager->getName()])) {	
						$event->cancel();	
						$damager->sendTip("§cYou Already In Sit Mode");	
						return;	
					}	
					if(isset($this->sit[$entity->getName()]) or isset($this->sit2[$entity->getName()])) {
						$event->cancel();
						$damager->sendTip("§cS/he Already In Sit Mode");
						return;	 
					}	 
					$this->onHit($entity, $damager);
				}
				if($item == "§r§dSit On You §6Wand\n§7[Damage SomeOne]"){
					$event->cancel();
					if(isset($this->sit[$damager->getName()]) or isset($this->sit2[$damager->getName()])){
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
		if($player2->getName() === $player1->getName()) return;
		if(!isset($this->onCombat[$player1->getName()])){
			$player1->sendMessage($player2->getName().'§d sit on You');
		}             
		if(!isset($this->onCombat[$player2->getName()])){
			$player2->sendMessage('§dYou sit on §r'.$player1->getName());
		}
		$this->sit[$player2->getName()] = [$player1->getName()];
		$this->sit2[$player1->getName()] = [$player2->getName()];
		$this->SitOnPlayer($player1, $player2);
	}
       
	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();
		switch($packet->pid()){
			case InteractPacket::NETWORK_ID:
				if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
					$player = $event->getPlayer();
					if(isset($this->sit[$player->getName()])){
						$this->dismountFromPlayer($player);
					}
					$event->cancel();
				}
				break;
		}
	}
    
	public function SitOnPlayer(Player $player1, Player $player2): bool{
		$pk = new SetActorLinkPacket();
		$pk->link = new EntityLink($player1->getId(), $player2->getId(), EntityLink::TYPE_RIDER, true, true);
		foreach ($this->getServer()->getOnlinePlayers() as $players) {
			$players->getNetworkSession()->sendDataPacket($pk);
		}        
		$entity->getNetworkSession()->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, +1.6, 0));
		return true;
	}

	public function dismountFromPlayer(Player $entity): bool {
		foreach($this->sit[$entity->getName()] as $p){
			unset($this->sit2[($p)]);
			unset($this->sit[($p)]);
		}
		unset($this->sit[($entity->getName())]);
		$entity->teleport(new Vector3($entity->x, $entity->y+0.1, $entity->z));
		return true;
	}

    
	public function dismountFromPlayer2(Player $entity): bool {
		foreach($this->sit2[$entity->getName()] as $p){
			unset($this->sit[($p)]);
		}
		unset($this->sit2[($entity->getName())]);
        	$entity->teleport(new Vector3($entity->x, $entity->y+0.1, $entity->z));
        	return true;
    	}


    	public function onSneak(PlayerToggleSneakEvent $event){
        	$player = $event->getPlayer();
        	if(isset($this->sit[$player->getName()])){
            		$this->dismountFromPlayer($player);
        	}
    	}
    	
	public function onJump(PlayerJumpEvent $event){
        	$player = $event->getPlayer();
        	if(isset($this->sit[$player->getName()])){
        		$this->dismountFromPlayer($player);
        	}
    	}
	
    	public function onTeleport(EntityTeleportEvent $event){
        	$player = $event->getEntity();
        	if(isset($this->sit[$player->getName()])){
            		$this->dismountFromPlayer($player);
        	}
    	}


    	public function onQuit(PlayerQuitEvent $event) {
        	$player = $event->getPlayer();
        	if(isset($this->sit[$player->getName()])){
            		$this->dismountFromPlayer($player);
        	}
        	if(isset($this->sit2[$player->getName()])){
            		$this->dismountFromPlayer2($player);
        	}
    	}

    	public function onDeath(PlayerDeathEvent $event) {
        	$player = $event->getPlayer();
        	if(isset($this->sit[$player->getName()])){
            		$this->dismountFromPlayer($player);
        	}
        	if(isset($this->sit2[$player->getName()])){
            		$this->dismountFromPlayer2($player);
        	}
    	}

}
