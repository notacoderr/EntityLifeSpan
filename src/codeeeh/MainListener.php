<?php

namespace codeeeh;

use pocketmine\{
	
		plugin\PluginBase,
		
		entity\object\ItemEntity,
	
		scheduler\Task,
		utils\Config,
	
		event\Listener,
		event\entity\ItemSpawnEvent
		
	};;

class MainListener extends PluginBase implements Listener
{	
	private $timer;
	
	function onEnable() : void
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveResource('timer.yml');
		$this->timer = (new Config($this->getDataFolder() . "timer.yml", CONFIG::YAML))->getAll();
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
