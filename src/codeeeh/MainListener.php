<?php

namespace codeeeh;

use pocketmine\{

	plugin\PluginBase,
		
	entity\object\ItemEntity,
	entity\projectile\Arrow,
	
	scheduler\Task,
	utils\Config,
	
	event\Listener,
	event\entity\ItemSpawnEvent

	};;

class MainListener extends PluginBase implements Listener
{	
	private $timer, $general = false;
	
	function onEnable() : void
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveResource('timer.yml');
		$cfg = new Config($this->getDataFolder() . "timer.yml", CONFIG::YAML);
		if($cfg->get("general_mode"))
		{
			$this->general = true;
			$this->timer = $cfg->get("general_timer");
			$this->getServer()->getLogger()->info("General Mode activated");
		} else {
			$this->timer = $cfg->getNested("specific_timer");
			$this->getServer()->getLogger()->info("Specific Mode activated");
		}
	}
	
	/**
	*
	* @param ItemEntity $event
	* priority HIGHEST
	*
	*/
	public function onUnpextectedItemBirthSomewhere(ItemSpawnEvent $event)
	{
		if(($ent = $event->getEntity()) instanceof ItemEntity)
		{
			if($this->general)
			{
				$this
				->getScheduler()
				->scheduleDelayedTask
				(
					new selfDestruction($event->getEntity()), 20 * intval( $this->timer )
				);
			} else {
				$itemName = strtolower( $ent->getItem()->getVanillaName() );
				if( array_key_exists($itemName, $this->timer ) )
				{
					$this
					->getScheduler()
					->scheduleDelayedTask
					(
						new selfDestruction($event->getEntity()), 20 * intval( $this->timer[$itemName] )
					);
				}
			}
		}
	}
}

class selfDestruction extends Task
{
	private $entity;
	function __construct(ItemEntity $entity)
	{
		$this->entity = $entity;
	}
	
	public function onRun(int $i)
	{
		try
		{ 
			$this->entity->flagForDespawn();
		} catch (\Exception $e) { }
	}
}
