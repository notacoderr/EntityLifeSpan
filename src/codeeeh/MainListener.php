<?php

namespace codeeeh;

use pocketmine\{

	plugin\PluginBase,
	
	entity\object\ItemEntity,
	entity\projectile\Projectile,
	
	utils\Config,
	utils\TextFormat,
	
	item\Durable,

	scheduler\Task,

	event\Listener,
	event\entity\ItemSpawnEvent,
	event\entity\ProjectileHitBlockEvent

};

class MainListener extends PluginBase implements Listener
{	
	private $mode = 0, $lifespan, $showname;
	
	function onEnable() : void
	{
		$this->saveResource('timer.yml');
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		$cfg = new Config($this->getDataFolder() . "timer.yml", CONFIG::YAML);

		switch($cfg->get("mode"))
		{
			case 'general':
				$this->mode = 0;
				$this->getServer()->getLogger()->info("General lifespan mode activated");
			break;
			case 'specific':
				$this->mode = 1;
				$this->getServer()->getLogger()->info("Specific lifespan mode activated");
			break;
			case 'mixed':
				$this->mode = 2;
				$this->getServer()->getLogger()->info("Mixed lifespan mode activated");
			break;
			default:
				$this->mode = 0;
				$this->getServer()->getLogger()->info("Unknown " . $cfg->get("mode") . " mode, using general instead.");
		}
		
		$this->lifespan = $cfg->getNested('lifespan');
		$this->showname = $cfg->getNested('showname');
	}

	/**
	* @param ItemSpawnEvent $event
	* priority HIGHEST
	*/
	public function onUnpextectedItemBirthSomewhere(ItemSpawnEvent $event)
	{
		if(($entity = $event->getEntity()) instanceof ItemEntity)
		{
			$itemname = $entity->getItem()->getVanillaName();
			$itemnameL = strtolower($itemname);
			switch($this->mode)
			{
				case 0:
					$time = abs(20 * $this->lifespan['general']);
				break;
				
				case 1:
					if(array_key_exists($itemnameL, $this->lifespan['specific']))
					{
						$time = abs(20 * $this->lifespan['specific'][$itemnameL]);
					} else {
						return;
					}
				break;
				
				case 2:
					if(array_key_exists($itemnameL, $this->lifespan['specific']))
					{
						$time = abs(20 * $this->lifespan['specific'][$itemnameL]);
					} else {
						$time = abs(20 * $this->lifespan['general']);
					}
				break;
			}
			
			if($entity->isClosed()) return;
			
			if($this->showname)
			{
				$item = $entity->getItem();
				
				/* var @itemname CustomName | VanillaName */
				$itemname = $item->getName();

				if($item instanceof Durable)
				{
					$durability = ceil(
						($item->getDamage() / $item->getMaxDurability()) * 100
					);

					switch(true)
					{
						case ($durability < 20): $color = TextFormat::GREEN;
						break;
						case (in_array($durability, range(21, 30))): $color = TextFormat::YELLOW;
						break;
						case (in_array($durability, range(31, 50))): $color = TextFormat::GOLD;
						break;
						case (in_array($durability, range(51, 80))): $color = TextFormat::RED;
						break;
						case ($durability > 81): $color = TextFormat::DARK_RED;
						break;
						default: $color = TextFormat::WHITE;
					}
					$nametag = "⌥ {$color}{$itemname}" . TextFormat::RESET;
					$nametag .= "\n⌥ Enchantments: " . count($item->getEnchantments());
				} else {
					if($item->getCount() > 1) 
					{
						$nametag = "⌥ {$item->getCount()}x {$itemname}" . TextFormat::RESET;
					} else {
						$nametag = "⌥ {$itemname}";
					}
				}

				$entity->setNameTag($nametag);
				$entity->setNameTagAlwaysVisible();
			}

			$this->getScheduler()->scheduleDelayedTask(
				new selfDestruction($entity), $time
			);
		}
	}
	
	/**
	* @param ProjectileHitBlockEvent $event
	* priority HIGHEST
	*/
	function onProjectileStuck(ProjectileHitBlockEvent $event)
	{
		if(($entity = $event->getEntity()) instanceof Projectile)
		{
			if($entity->isClosed()) return;

			$this->getScheduler()->scheduleDelayedTask(
				new selfDestruction($entity), 20 * $this->lifespan['projectile']
			);
		}
	}
}

class selfDestruction extends Task
{
	private $entity;

	function __construct($entity)
	{
		$this->entity = $entity;
	}

	public function onRun(int $i)
	{
		if(!$this->entity->isClosed()) $this->entity->close();
	}
}
