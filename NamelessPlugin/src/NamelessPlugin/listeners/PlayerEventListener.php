<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless-PMMP-Plugin
 *
 *  License: MIT
 *
 *  Player event listener class file
 */
namespace NamelessPlugin\listeners;

use NamelessPlugin\utils\APITask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class PlayerEventListener implements Listener {
	private $_plugin;
	
	public function __construct($plugin){
		$this->_plugin = $plugin;
	}
	
	public function onPlayerJoin(PlayerJoinEvent $e){
		//$player = $e->getPlayer();
	}
}