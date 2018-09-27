<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless-PMMP-Plugin
 *
 *  License: MIT
 *
 *  Main index file
 */
namespace NamelessPlugin;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class NamelessPlugin extends PluginBase {
	private $_plugin_url = '';
	private static $_instance;
	private $_debugging_enabled = false;
	
	/*
	 *  onEnable method
	 */
	public function onEnable(){
		self::$_instance = $this;

		$this->saveDefaultConfig();
		
		if(!strlen($this->getConfig()->get('api-url'))){
			$this->getLogger()->info('===========================================================');
			$this->getLogger()->info('Please enter an API URL in the NamelessPlugin config first!');
			$this->getLogger()->info('===========================================================');
			$this->getServer()->getPluginManager()->disablePlugin($this);

		} else {
			$this->getLogger()->info('Nameless Plugin enabled!');
			
			$this->_plugin_url = rtrim($this->getConfig()->get('api-url'), '/') . '/';
			
			if($this->getConfig()->get('api-debug-mode') == true)
				$this->_debugging_enabled = true;
			
			// Events
			$this->getServer()->getPluginManager()->registerEvents(new listeners\PlayerEventListener($this), $this);
			
			// Commands
			$this->getServer()->getCommandMap()->register('getuser', new commands\GetUserCommand($this));
			$this->getServer()->getCommandMap()->register('validate', new commands\ValidateCommand($this));
			
			if($this->getConfig()->get('enable-registration') == true)
				$this->getServer()->getCommandMap()->register('register', new commands\RegisterCommand($this));
			
		}
	}
	
	/*
	 *  onDisable method
	 */
	public function onDisable(){
		$this->getLogger()->info('Nameless Plugin disabled!');
	}
	
	/*
	 *  Get API URL
	 */
	public function getAPIURL(){
		return $this->_plugin_url;
	}
	
	/*
	 *  Is debugging enabled?
	 */
	public function isDebuggingEnabled(){
		return $this->_debugging_enabled;
	}
	
	/*
	 *  Deal with API response
	 *  TODO: hooks
	 */
	public static function handleResponse($player, $action, $response){
		$instance = self::getInstance();
		$player = $instance->getServer()->getPlayerExact($player);
		$response = json_decode($response);
		
		if(isset($response->error)){
			switch($action){
				case 'userInfo':
					commands\GetUserCommand::handleAPIResponse($instance, $player, $response);
				
					break;
					
				case 'validateUser':
					commands\ValidateCommand::handleAPIResponse($instance, $player, $response);
				
					break;
					
				case 'register':
					commands\registerCommand::handleAPIResponse($instance, $player, $response);
					
					break;
					
				default:
					$instance->getLogger()->info('Invalid action "' . $action . '"!');
				
					break;
			}
		} else {
			if($player){
				$player->sendMessage(TextFormat::RED . 'There API did not return any data!');
			}
		}
	}
	
	/*
	 *  Return instance of plugin
	 */
	public static function getInstance(){
		return self::$_instance;
	}
}