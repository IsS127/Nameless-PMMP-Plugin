<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless-PMMP-Plugin
 *
 *  License: MIT
 *
 *  Register command class file
 */
namespace NamelessPlugin\commands;

use NamelessPlugin\utils\APITask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class RegisterCommand extends Command implements PluginIdentifiableCommand {
	private $_plugin;
	
	public function __construct($plugin){
		parent::__construct('register', $plugin->getMessage('register-command-info'), '/register <email>', array());
		$this->setPermission('namelessmc.user.register');
		
		$this->_plugin = $plugin;
	}
	
	public function execute(CommandSender $sender, $alias, array $args){
		if(!$sender->hasPermission('namelessmc.user.register')){
			$sender->sendMessage($this->_plugin->getMessage('no-permission'));
			return false;
		}
		
		if($sender instanceof Player){
			if(count($args) && strlen($args[0])){
				$email = $args[0];
				$player = $this->_plugin->getServer()->getPlayerExact($sender->getName());
				$uuid = $player->getUniqueId()->toString();

				$this->_plugin->getServer()->getAsyncPool()->submitTask(new APITask($this->_plugin->getAPIURL(), $sender->getName(), 'POST', 'register', array('username' => $sender->getName(), 'uuid' => $uuid, 'email' => $email)));
				
			} else
				$sender->sendMessage(str_replace('{x}', '/register <email>', $this->_plugin->getMessage('invalid-usage')));

		} else {
			$sender->sendMessage($this->_plugin->getMessage('ingame-command'));
		}
		
		return true;
	}
	
	public function getPlugin() : Plugin {
		return $this->_plugin;
	}
	
	public static function handleAPIResponse($instance, $player, $response){
		if($response->error === true){
			if($player){
				if($response->code == 7)
					$player->sendMessage($instance->getMessage('invalid-email'));
				else if($response->code == 11)
					$player->sendMessage($instance->getMessage('username-exists'));
				else if($response->code == 12)
					$player->sendMessage($instance->getMessage('uuid-exists'));
				else if($response->code == 10)
					$player->sendMessage($instance->getMessage('email-exists'));
				else if($response->code == 14)
					$player->sendMessage($instance->getMessage('email-failed'));
				else
					$player->sendMessage($instance->getMessage('api-error'));
			}
			
			if($instance->isDebuggingEnabled()){
				$instance->getLogger()->info(str_replace('{x}', 'register', $instance->getMessage('api-error-debug')));
				$instance->getLogger()->info(str_replace('{x}', $response->code, $instance->getMessage('error-code')));
				$instance->getLogger()->info(str_replace('{x}', $response->message, $instance->getMessage('error-message')));
			}
			
		} else {
			if($player){
				if(isset($response->link)){
					$player->sendMessage(str_replace('{x}', $response->link, $instance->getMessage('registered-successfully-link')));

				} else if(isset($response->message)){
					$player->sendMessage($instance->getMessage('registered-successfully-email'));
					
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