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
		parent::__construct('validate', $plugin->getMessage('validate-command-info'), '/validate <code>', array());
		$this->setPermission('namelessmc.user.validate');
		
		$this->_plugin = $plugin;
	}
	
	public function execute(CommandSender $sender, $alias, array $args){
		if(!$sender->hasPermission('namelessmc.user.validate')){
			$sender->sendMessage($plugin->getMessage('no-permission'));
			return false;
		}
		
		if($sender instanceof Player){
			if(count($args) && strlen($args[0])){
				$code = $args[0];
				$player = $this->_plugin->getServer()->getPlayerExact($sender->getName());
				$uuid = $player->getUniqueId()->toString();

				$this->_plugin->getServer()->getAsyncPool()->submitTask(new APITask($this->_plugin->getAPIURL(), $sender->getName(), 'POST', 'validateUser', array('uuid' => $uuid, 'code' => $code)));
				
			} else
				$sender->sendMessage(str_replace('{x}', '/validate <code>', $this->_plugin->getMessage('invalid-usage')));

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
				if($response->code == 16)
					$player->sendMessage($instance->getMessage('not-registered'));
				else if($response->code == 28)
					$player->sendMessage($instance->getMessage('invalid-code'));
				else
					$player->sendMessage($instance->getMessage('api-error'));
			}
			
			if($instance->isDebuggingEnabled()){
				$instance->getLogger()->info(str_replace('{x}', 'validateUser', $instance->getMessage('api-error-debug')));
				$instance->getLogger()->info(str_replace('{x}', $response->code, $instance->getMessage('error-code')));
				$instance->getLogger()->info(str_replace('{x}', $response->message, $instance->getMessage('error-message')));
			}
				
		} else {
			if($player){
				if(isset($response->message)){
					$player->sendMessage($instance->getMessage('account-validated'));

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