<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless-PMMP-Plugin
 *
 *  License: MIT
 *
 *  APITask class file
 */
namespace NamelessPlugin\utils;

use NamelessPlugin\NamelessPlugin;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat;
 
class APITask extends AsyncTask {
	private $_plugin_url, $_player, $_action, $_response;
	private $_fields = '';
	
	public function __construct($plugin_url, $player, $method, $action, $fields){
		$this->_plugin_url = $plugin_url . $action;
		$this->_player = $player;
		$this->_method = $method;
		$this->_action = $action;
		
		if(NamelessPlugin::getInstance()->isDebuggingEnabled()){
			NamelessPlugin::getInstance()->getLogger()->info('Fields:');
			NamelessPlugin::getInstance()->getLogger()->info(print_r($fields));
		}
		
		if($method == 'POST'){
			$this->_fields = $fields;
			
		} else {
			if(strpos($plugin_url, '?') !== false){
				$start_char = '&';
			} else {
				$start_char = '?';
			}
			
			if(count($fields)){
				$this->_fields .= $start_char;
				foreach($fields as $key => $field){
					$this->_fields .= $key . '=' . $field . '&';
				}
				rtrim($this->_fields, '&');
			}
		}
	}
	
	public function onRun(){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		if($this->_method == 'POST'){
			curl_setopt($ch, CURLOPT_URL, $this->_plugin_url);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_fields);
			
		} else {
			curl_setopt($ch, CURLOPT_URL, $this->_plugin_url . $this->_fields);
			
		}
		
		$this->_response = curl_exec($ch);
		
		curl_close($ch);
	}
	
	public function onCompletion($server){
		if(NamelessPlugin::getInstance()->isDebuggingEnabled()){
			$server->getLogger()->info('API task completed with URL ' . $this->_plugin_url);
			$server->getLogger()->info(print_r($this->_response));
		}

		NamelessPlugin::handleResponse($this->_player, $this->_action, $this->_response);
	}
}