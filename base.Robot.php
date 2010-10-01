<?php

if (!class_exists('Robot')) :
class Robot {

	var $plugin;

	function __construct($plugin) 
	{
		$this->plugin = $plugin;
		
		
	}
	
	
	function check_referer($action = -1, $query_arg = '_wpnonce') 
	{		
		$result = isset($_REQUEST[$query_arg]) ? wp_verify_nonce($_REQUEST[$query_arg], $action) : false;
		
		return $result && -1 != $action && false === wp_get_referer(); 
	}
	

}
endif;

?>