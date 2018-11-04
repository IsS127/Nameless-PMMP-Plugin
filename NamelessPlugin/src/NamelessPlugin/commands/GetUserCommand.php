<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless-PMMP-Plugin
 *
 *  License: MIT
 *
 *  getUser command class file
 */
namespace NamelessPlugin\commands;

use NamelessPlugin\utils\APITask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class GetUserCommand extends Command implements PluginIdentifiableCommand {
	private $_plugin;
	
	public function __construct($plugin){
		parent::__construct('getuser', $plugin->getMessage('get-user-command-info'), '/getUser <username/uuid>', array());
		$this->setPermission('namelessmc.admin.getuser');
		
		$this->_plugin = $plugin;
	}
	
	public function execute(CommandSender $sender, $alias, array $args){
		if(!$sender->hasPermission('namelessmc.admin.getuser')){
			$sender->sendMessage($this->_plugin->getMessage('no-permission'));
			return false;
		}
		
		if(count($args) && strlen($args[0])){
			$user = $args[0];

			if(strlen($user) == 32 || strlen($user) == 36)
				$this->_plugin->getServer()->getAsyncPool()->submitTask(new APITask($this->_plugin->getAPIURL(), $sender->getName(), 'GET', 'userInfo', array('uuid' => $user)));
			else
				$this->_plugin->getServer()->getAsyncPool()->submitTask(new APITask($this->_plugin->getAPIURL(), $sender->getName(), 'GET', 'userInfo', array('username' => $user)));
			
		} else
			$sender->sendMessage(str_replace('{x}', '/getuser <username/uuid>', $this->_plugin->getMessage('invalid_usage')));

		return true;
	}
	
	public function getPlugin() : Plugin {
		return $this->_plugin;
	}
	
	public static function handleAPIResponse($instance, $player, $response){
		if($response->error === true){
			if($player){
				$player->sendMessage($instance->getMessage('api-error'));
			}
			
			if($instance->isDebuggingEnabled()){
				$instance->getLogger()->info(str_replace('{x}', 'register', $instance->getMessage('api-error-debug')));
				$instance->getLogger()->info(str_replace('{x}', $response->code, $instance->getMessage('error-code')));
				$instance->getLogger()->info(str_replace('{x}', $response->message, $instance->getMessage('error-message')));
			}
			
		} else {
			if($player){
				if(isset($response->username)){
					$player->sendMessage($instance->getMessage('website-user-info'));
					$player->sendMessage(str_replace('{x}', $response->username, $instance->getMessage('website-user-info-username')));
					
					if($response->username != $response->displayname)
						$player->sendMessage(str_replace('{x}', $response->displayname, $instance->getMessage('website-user-info-nickname')));
					
					$player->sendMessage(str_replace('{x}', $response->uuid, $instance->getMessage('website-user-info-uuid')));
					$player->sendMessage(str_replace('{x}', $response->group_name, $instance->getMessage('website-user-info-group')));
					$player->sendMessage(str_replace('{x}', date('d M Y, H:i', $response->registered), $instance->getMessage('website-user-info-registered')));
					$player->sendMessage(($response->validated ? $instance->getMessage('website-user-info-validated-yes') : $instance->getMessage('website-user-info-validated-no')));
					$player->sendMessage(($response->banned ? $instance->getMessage('website-user-info-banned-yes') : $instance->getMessage('website-user-info-banned-no')));

				} else {
					$player->sendMessage($instance->getMessage('invalid-response'));
					
					if($instance->isDebuggingEnabled()){
						$instance->getLogger()->info($instance->getMessage('no-api-response'));
					}
				}
			} else {
				$instance->getLogger()->info(str_replace('{x}', $username, $instance->getMessage('unable-to-find-player')));
			}
		}
	}
}