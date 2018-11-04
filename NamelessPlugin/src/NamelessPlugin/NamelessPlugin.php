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
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class NamelessPlugin extends PluginBase {
	private $_plugin_url = '', $_default_language = '';
	private static $_instance;
	private $_debugging_enabled = false;
	private $_language = null;
	
	/*
	 *  onEnable method
	 */
	public function onEnable(){
		self::$_instance = $this;

		$this->saveDefaultConfig();

		if(!is_dir($this->getDataFolder()))
			mkdir($this->getDataFolder());

		if(!is_dir($this->getDataFolder() . 'languages'))
			mkdir($this->getDataFolder() . 'languages');

		// Language files
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getFile() . "resources/languages")) as $file){
			$file = str_replace("\\", "/", $file);
			$file_array = explode("/", $file);

			// Only save .yml
			if(!file_exists($this->getDataFolder() . 'languages/' . $file_array[count($file_array) - 1]) && substr($file_array[count($file_array) - 1], strrpos($file_array[count($file_array) - 1], '.') + 1) == "yml"){
				$this->saveResource("languages/" . $file_array[count($file_array) - 1]);
			}
			
			// Temp - force language file regeneration
			//if(substr($file_array[count($file_array) - 1], strrpos($file_array[count($file_array) - 1], '.') + 1) == "yml")
			//	$this->saveResource("languages/" . $file_array[count($file_array) - 1], true);
		}

		// Get language from config
		$this->_default_language = ($this->getConfig()->get('default-language') ? $this->getConfig()->get('default-language') : 'eng_en.yml');
		
		if(file_exists($this->getDataFolder() . 'languages/' . $this->_default_language)){
			$lang = new Config($this->getDataFolder() . 'languages/' . $this->_default_language, Config::YAML);
			$this->_language = $lang->getAll();

		} else {
			$this->getLogger()->info('===========================================================');
			$this->getLogger()->info('Unable to load default language! Disabling plugin...');
			$this->getLogger()->info('===========================================================');
			$this->getServer()->getPluginManager()->disablePlugin($this);

			return;

		}

		if(!strlen($this->getConfig()->get('api-url'))){
			$this->getLogger()->info('===========================================================');
			$this->getLogger()->info($this->getMessage('api-url-required'));
			$this->getLogger()->info('===========================================================');
			$this->getServer()->getPluginManager()->disablePlugin($this);

			return;

		} else {
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
		
	}
	
	/*
	 *  Get API URL
	 */
	public function getAPIURL(){
		return $this->_plugin_url;
	}
	
	/*
	 *  Get message from config
	 */
	public function getMessage($message){
		// Parse colours
		$colours = array(
			'&0' => TextFormat::BLACK,
			'&1' => TextFormat::DARK_BLUE,
			'&2' => TextFormat::DARK_GREEN,
			'&3' => TextFormat::DARK_AQUA,
			'&4' => TextFormat::DARK_RED,
			'&5' => TextFormat::DARK_PURPLE,
			'&6' => TextFormat::GOLD,
			'&7' => TextFormat::GRAY,
			'&8' => TextFormat::DARK_GRAY,
			'&9' => TextFormat::BLUE,
			'&a' => TextFormat::GREEN,
			'&b' => TextFormat::AQUA,
			'&c' => TextFormat::RED,
			'&d' => TextFormat::LIGHT_PURPLE,
			'&e' => TextFormat::YELLOW,
			'&f' => TextFormat::WHITE,
			'&k' => TextFormat::OBFUSCATED,
			'&l' => TextFormat::BOLD,
			'&m' => TextFormat::STRIKETHROUGH,
			'&n' => TextFormat::UNDERLINE,
			'&o' => TextFormat::ITALIC,
			'&r' => TextFormat::RESET
		);
		
		return str_replace(array_keys($colours), array_values($colours), $this->_language[$message]);
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
					$instance->getLogger()->info(str_replace('{x}', $action, self::getInstance()->getMessage('invalid-action')));
				
					break;
			}
		} else {
			if($player){
				$player->sendMessage(self::getInstance()->getMessage('no-api-data'));
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