<?php
/*
Plugin Name: i8 Framework
Plugin URI: http://infinity-8.me
Description: Live Easy.
Version: 1.0
Author: Davit Barbakadze
Author URI: http://infinity-8.me
*/


if (!class_exists('Plugino'))
    require_once(dirname(__FILE__) . '/class.Plugino.php');
    
if (!class_exists('Themo'))
    require_once(dirname(__FILE__) . '/class.Themo.php');

# stealth mode
add_filter('all_plugins', create_function('$plugins', 'unset($plugins["i8/i8.php"]);return $plugins;'));


?>