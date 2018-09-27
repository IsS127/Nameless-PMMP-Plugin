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
		parent::__construct('register', 'Creates an account on our website', '/register <email>', array());
		$this->setPermission('namelessmc.user.register');
		
		$this->_plugin = $plugin;
	}
	
	public function execute(CommandSender $sender, $alias, array $args){
		if(!$sender->hasPermission('namelessmc.user.register')){
			$sender->sendMessage(TextFormat::RED . 'You do not have permission to use this command');
			return false;
		}
		
		if($sender instanceof Player){
			if(count($args)){
				$email = $args[0];
				$player = $this->_plugin->getServer()->getPlayerExact($sender->getName());
				$uuid = $player->getUniqueId()->toString();

				$this->_plugin->getServer()->getAsyncPool()->submitTask(new APITask($this->_plugin->getAPIURL(), $sender->getName(), 'POST', 'register', array('username' => $sender->getName(), 'uuid' => $uuid, 'email' => $email)));
				
			} else
				$sender->sendMessage(TextFormat::RED . 'Invalid usage: /register <email>');

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
				if($response->code == 7)
					$player->sendMessage(TextFormat::RED . 'Please enter a valid email address');
				else if($response->code == 11)
					$player->sendMessage(TextFormat::RED . 'Your username already exists on the website!');
				else if($response->code == 12)
					$player->sendMessage(TextFormat::RED . 'Your UUID already exists on the website!');
				else if($response->code == 10)
					$player->sendMessage(TextFormat::RED . 'Your email address already exists on the website!');
				else if($response->code == 14)
					$player->sendMessage(TextFormat::RED . 'Unable to send registration email, please contact an admin to activate your account');
				else
					$player->sendMessage(TextFormat::RED . 'There was an API error executing the command!');
			}
			
			if($instance->isDebuggingEnabled()){
				$instance->getLogger()->info('There was an API error with action register');
				$instance->getLogger()->info('Error code: ' . $response->code);
				$instance->getLogger()->info('Error message: ' . $response->message);
			}
			
		} else {
			if($player){
				if(isset($response->link)){
					$player->sendMessage(TextFormat::BLUE . 'Thanks for registering!');
					$player->sendMessage(TextFormat::BLUE . 'Please visit the following link to complete registration:');
					$player->sendMessage(TextFormat::GREEN . $response->link);

				} else if(isset($response->message)){
					$player->sendMessage(TextFormat::BLUE . 'Thanks for registering!');
					$player->sendMessage(TextFormat::BLUE . 'Please check your emails to complete registration.');
					
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