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
		parent::__construct('getuser', 'Returns information about a certain usermame or UUID', '/getUser <username/uuid>', array());
		$this->setPermission('namelessmc.admin.getuser');
		
		$this->_plugin = $plugin;
	}
	
	public function execute(CommandSender $sender, $alias, array $args){
		if(!$sender->hasPermission('namelessmc.admin.getuser')){
			$sender->sendMessage(TextFormat::RED . 'You do not have permission to use this command');
			return false;
		}
		
		if(count($args)){
			$user = $args[0];

			if(strlen($user) == 32 || strlen($user) == 36)
				$this->_plugin->getServer()->getAsyncPool()->submitTask(new APITask($this->_plugin->getAPIURL(), $sender->getName(), 'GET', 'userInfo', array('uuid' => $user)));
			else
				$this->_plugin->getServer()->getAsyncPool()->submitTask(new APITask($this->_plugin->getAPIURL(), $sender->getName(), 'GET', 'userInfo', array('username' => $user)));
			
		} else
			$sender->sendMessage(TextFormat::RED . 'Invalid usage: /getuser <username/uuid>');

		return true;
	}
	
	public function getPlugin() : Plugin {
		return $this->_plugin;
	}
	
	public static function handleAPIResponse($instance, $player, $response){
		if($response->error === true){
			if($player){
				$player->sendMessage(TextFormat::RED . 'There was an API error executing the command!');
			}
			
			if($instance->isDebuggingEnabled()){
				$instance->getLogger()->info('There was an API error with action getUser');
				$instance->getLogger()->info('Error code: ' . $response->code);
				$instance->getLogger()->info('Error message: ' . $response->message);
			}
				
		} else {
			if($player){
				if(isset($response->username)){
					$player->sendMessage(TextFormat::BLUE . 'Website user information:');
					$player->sendMessage(TextFormat::BLUE . 'Username: ' . TextFormat::GREEN . $response->username);
					
					if($response->username != $response->displayname)
						$player->sendMessage(TextFormat::BLUE . 'Nickname: ' . TextFormat::GREEN . $response->displayname);
					
					$player->sendMessage(TextFormat::BLUE . 'UUID: ' . TextFormat::GREEN . $response->uuid);
					$player->sendMessage(TextFormat::BLUE . 'Group: ' . TextFormat::GREEN . $response->group_name);
					$player->sendMessage(TextFormat::BLUE . 'Registered: ' . TextFormat::GREEN . date('d M Y, H:i', $response->registered));
					$player->sendMessage(TextFormat::BLUE . 'Validated: ' . ($response->validated ? TextFormat::GREEN . 'Yes' : TextFormat::Red . 'No'));
					$player->sendMessage(TextFormat::BLUE . 'Banned: ' . ($response->banned ? TextFormat::RED . 'Yes' : TextFormat::GREEN . 'No'));

				} else {
					$player->sendMessage(TextFormat::RED . 'Invalid API response!');
					
					if($instance->isDebuggingEnabled()){
						$instance->getLogger()->info('No response was returned by the API');
					}
				}
			} else {
				$instance->getLogger()->info('Unable to find player ' . $username);
			}
		}
	}
}