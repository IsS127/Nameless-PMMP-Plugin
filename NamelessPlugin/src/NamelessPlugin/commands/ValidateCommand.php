<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless-PMMP-Plugin
 *
 *  License: MIT
 *
 *  Validate command class file
 */
namespace NamelessPlugin\commands;

use NamelessPlugin\utils\APITask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ValidateCommand extends Command implements PluginIdentifiableCommand {
	private $_plugin;
	
	public function __construct($plugin){
		parent::__construct('validate', 'Validates your website account', '/validate <code>', array());
		$this->setPermission('namelessmc.user.validate');
		
		$this->_plugin = $plugin;
	}
	
	public function execute(CommandSender $sender, $alias, array $args){
		if(!$sender->hasPermission('namelessmc.user.validate')){
			$sender->sendMessage(TextFormat::RED . 'You do not have permission to use this command');
			return false;
		}
		
		if($sender instanceof Player){
			if(count($args)){
				$code = $args[0];
				$player = $this->_plugin->getServer()->getPlayerExact($sender->getName());
				$uuid = $player->getUniqueId()->toString();

				$this->_plugin->getServer()->getAsyncPool()->submitTask(new APITask($this->_plugin->getAPIURL(), $sender->getName(), 'POST', 'validateUser', array('uuid' => $uuid, 'code' => $code)));
				
			} else
				$sender->sendMessage(TextFormat::RED . 'Invalid usage: /validate <code>');

		} else {
			$sender->sendMessage('You can only use this command ingame');
		}

		return true;
	}
	
	public function getPlugin() : Plugin {
		return $this->_plugin;
	}
	
	public static function handleAPIResponse($instance, $player, $response){
		if($response->error === true){
			if($player){
				if($response->code == 16)
					$player->sendMessage(TextFormat::RED . 'You have not registered on the website yet!');
				else if($response->code == 28)
					$player->sendMessage(TextFormat::RED . 'The code is incorrect!');
				else
					$player->sendMessage(TextFormat::RED . 'There was an API error executing the command!');
			}
			
			if($instance->isDebuggingEnabled()){
				$instance->getLogger()->info('There was an API error with action validateUser');
				$instance->getLogger()->info('Error code: ' . $response->code);
				$instance->getLogger()->info('Error message: ' . $response->message);
			}
				
		} else {
			if($player){
				if(isset($response->message)){
					$player->sendMessage(TextFormat::GREEN . 'You have activated your account successfully!');

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