<?php

require_once(dirname(__FILE__) . '/class.i8Core.php');

class Themo extends i8Core {

	var $data = null;
	
	var $namespace = 'themo_';

	function __construct()
	{
        parent::__construct();

        # urls and paths
        $this->url	= get_bloginfo('template_directory');
        $this->path	= TEMPLATEPATH;

        $this->__file__ = "{$this->path}/style.css";

        do_action("i8_{$this->namespace}initialized");

        $this->_activation_operations();
	}


    function _activation_operations()
    {
        # this check will run only when this theme is activated
        global $pagenow;
        if ( is_admin() && isset($_GET['activated'] ) && $pagenow == "themes.php" )
        {
            $this->i8_data = get_theme_data($this->__file__);
            if (get_current_theme() == $this->i8_data['Name'])
            {
                parent::_activation_operations();

                if (method_exists($this, '_activate'))
                    $this->_activate();
            }
        }
        
    }   
	

}


?>