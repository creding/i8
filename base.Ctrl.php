<?php

if (!class_exists('Ctrl')) :
class Ctrl {

	var $plugin;

	var $ctrl;
	var $action;

	function __construct($plugin, $action = false) 
	{	
		$this->plugin = $plugin;
					
		# what's the name of controller?
		$this->ctrl = strtolower(substr_replace(get_class($this), '', -4));	    
        
		# set path to related templates
        $this->tpls = $this->plugin->path . '/_tpls/' . $this->ctrl;
		$this->robots = $this->plugin->path . '/_robots';
		
		# if there's appropriate robot - load it up
		$plugin->load("{$this->plugin->i8_path}/base.Robot.php");
		$plugin->load("{$this->robots}/{$this->ctrl}_robot.php");
		
		$robot_class = $this->ctrl . "Robot";
		if (!class_exists($robot_class))
			$robot_class = "{$this->plugin->prefix}_Robot";
			
		$this->robot = new $robot_class($this->plugin);
		
		$this->action = $action; 
	}
	
	
	function __destruct()
	{		
		if (method_exists($this, '_destroy'))
			$this->_destroy();
	}
	
	
	function __call($action, $args)
	{
		wp_die(get_class($this) . "::$action is not defined!");
	}	
	
	
	/**
	 * Method to manually render and save arbitrary template into Ctr::$output variable. 
	 * If this method is called default include will be prevented.
	 *
	 * $path - Path to the template to render
	 */
	function _render($path, $vars = null)
	{
		$this->output = $this->plugin->load( "{$this->tpls}/{$this->action}.php", false, true, $vars );
	}
	
	
	/**
	 * Outputs either pre-rendered template ( @see Ctrl::_render() ) or default one.
	 */
	function _output()
	{	
		if (!empty($this->output)) {
			echo $this->output;
			return;
		}
		
		$this->plugin->load( "{$this->tpls}/{$this->action}.php", false );
	}


    /* dummy function for connection purposes only */
    function _connect()
    {

    }
	

}
endif;

?>