<?php

require_once(dirname(__FILE__) . '/class.i8Core.php');

class Plugino extends i8Core {
	
	var $url;
	var $path;
	
	var $__FILE__;
	
	function __construct($__FILE__ = false)
	{
		parent::__construct();
		
		if ($__FILE__) {
			$this->__FILE__ = $__FILE__;	
		} else {
			# probably it would be cleaner to pass a filename as an argument, but by now this is easier for developer
			$trace = debug_backtrace(false);
			$this->__FILE__ = $trace[1]['file'];
		}
		
		# plugin urls and paths		
		$plugin_dir = plugin_basename(dirname($this->__FILE__));
		$this->url	= WP_PLUGIN_URL . '/' . $plugin_dir;
		$this->path	= WP_PLUGIN_DIR . '/' . $plugin_dir;
		
		
		# check if uninstall has called this, logic will break here, if it has
		if ($this->_uninstalling())
		{
			add_action('uninstall_' . plugin_basename($this->__FILE__), array($this, '_uninstall'), 0);
			return;
		}
		
		# update from private repository
		if (isset($this->repo))
			add_filter('transient_update_plugins', array($this, '_check_4_updates'));
		
		register_activation_hook( $this->__FILE__, 		array($this, '_activation_operations') );
		register_deactivation_hook( $this->__FILE__, 	array($this, '_deactivation_operations') );
		
		do_action("i8_{$this->namespace}initialized");
    }
	
	
	function _activation_operations()
	{
		$this->i8_data = get_plugin_data($this->__FILE__, false, false);
		parent::_activation_operations();
		
		register_uninstall_hook($this->__FILE__, '_uninstall');
		
		if (method_exists($this, 'on_activate'))
			$this->on_activate($this->i8_data);
	}
	
	
	function _deactivate()
	{
		unset($_GET['activate']);
		deactivate_plugins(plugin_basename($this->__FILE__));	
	}
	
	
	
	// Uninstall logic
	
	function _uninstalling()
	{
		/* we could use WP_UNINSTALL_PLUGIN, but it's not being defined for uninstall triggered by
		register_uninstall_hook, hence this workaround */ 
		return  $_POST['action'] == 'delete-selected' 	&& 
				$_POST['verify-delete'] == 1			&& 
				in_array(plugin_basename($this->__FILE__), $_POST['checked']); 
	}
	
	
	function _uninstall()
	{
		// remove dummy callback
		remove_action('uninstall_' . plugin_basename($this->__FILE__), '_uninstall');
		
		if (!current_user_can('delete_plugins'))
			return;
						 
		# delete tables
		if (!empty($this->tables))
		{
			global $wpdb;	
			foreach ($this->tables as $table => $sql)
			{
				$table_name = strtolower($wpdb->prefix . $this->prefix . $table);
				$wpdb->query("DROP TABLE `{$table_name}`");
			}
		}
		
		# delete options
		delete_option("{$this->namespace}options");
		delete_option("{$this->namespace}version");
	}


}