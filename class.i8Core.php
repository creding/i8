<?php

class i8Core {
	
    var $prefix = 'i8_';

    var $namespace = 'i8core_';

    var $msgs = array();

	function __construct()
	{
		# activate debug if set
		if ($this->debug) {
			define('WP_DEBUG', false);
			ini_set('display_errors', 1);
			error_reporting(E_ALL & ~E_NOTICE);
		}
	
		# setting namespace for use in options, etc
		$parent_class = get_parent_class($this);
	
		# set instance type
		$this->i8 = strtolower($parent_class);
	
		$this->classname = get_class($this);
		$this->namespace = $this->i8 . '_' . $this->classname . '_';
	
	
		$upload_dir = wp_upload_dir();
		$this->upload_url 	= $upload_dir['baseurl'];
		$this->upload_path 	= $upload_dir['basedir'];
	
	
		# setting i8Core path
		$this->i8_path = dirname(__FILE__);
	
		# require useful functions
		require_once( $this->i8_path . '/functions.php' );
	
		# retrieve version
		$this->version = get_option("{$this->namespace}version");
	
	
		# add tables to global $wpdb object
		if (!empty($this->tables))
		{
			global $wpdb;
			foreach ($this->tables as $table => $sql)
				if (!isset($wpdb->$table) && !in_array($table, $wpdb->tables))
					$wpdb->$table = strtolower($wpdb->prefix . $this->prefix . $table);
		}
	
		# check for PHP5
		if ( version_compare(phpversion(), '5') == -1)
			$this->warn("<b>i8Core</b> requires PHP5. <b>$this->classname</b> plugin will deactivate <b>now</b>.");
	
	
		# initialize options
		$this->options_init();
	
	
		# handle hooks
		$this->hooks_define();
		do_action("i8_hooks_defined_{$this->classname}");
	}
	
	
	private function hooks_define()
	{
		# some inside actions
		add_action('init', array($this, '_register_routes'));
		add_action('init', array($this, '_unauth_wp_ajax'));
		add_action('admin_init', array($this, '_options_register'));
		add_action('admin_menu', array($this, '_pages_add'));
	
		add_action("i8_{$this->namespace}initialized", array($this, '_load_addons'));
	
		$this->hooks_register();
	}
	
	
	function _load_addons()
	{
		# include add-ons if available
		if ( !empty($this->addons) )
			foreach ($this->addons as $addon) {
	
				if (class_exists("{$this->prefix}$addon"))
					continue;
	
				$path = "$this->path/addons/class.$addon.php";
				if (!file_exists($path))
					$path = "$this->i8_path/addons/class.$addon.php";
	
				if (file_exists($path))
				{
					require_once( $path );
					//$fqdn_addon = "Plugino/$a";
					$addon_class = "{$this->prefix}$addon";
					$this->$addon = new $addon_class;
					$this->$addon->url = $this->url;
					$this->$addon->path = $this->path;
					$this->$addon->plugin = $this;
	
					// for the hook handlers that needs three variables defined above
					if (method_exists($this->$addon, 'hooks'))
						$this->$addon->hooks();
				}
			}
	
		do_action("i8_addons_loaded_{$this->classname}");
	}
	
	
	
	/* credits for this goes to: Kaspars Dambis (http://konstruktors.com/blog/) */
	function _check_4_updates($checked_data)
	{
		return $checked_data;
	}
	
	
	private function hooks_register($obj = false)
	{
		if (!$obj || !is_object($obj))
			$obj = $this;
	
		$methods = get_class_methods(get_class($obj));
	
		foreach ((array)$methods as $method)
			$this->hook_register($method);
	}
	
	
	private function hook_register($method, $override = false)
	{
		# extract hook type and handler
		if (!$pos = strpos($method, '__')) // not false and not on zero position
				return;
	
		list($handle, $priority, $accepted_args) = explode('_', substr($method, 0, $pos));
		$hook = substr($method, $pos + 2);
	
		$priority = is_numeric($priority) ? $priority : 10;
		$accepted_args = is_numeric($accepted_args) ? $accepted_args : 1;
	
		if ($override) // lets you define your own hook handler
				$method = $override;
	
		switch ( $handle ) :
			case 'a':
			case 'pa':
			case 'action':
					add_action( $hook, array($this, $method), $priority, $accepted_args );
					break;
			case 'f':
			case 'pf':
			case 'filter':
					add_filter( $hook, array($this, $method), $priority, $accepted_args );
					break;
			case 'sc':
			case 'psc':
			case 'shortcode':
					add_shortcode( $hook, array($this, $method) );
					break;
		endswitch;
	}
	
	
	/* add routes */
	function _register_routes()
	{
		if (!empty($this->__routes))
			foreach ($this->__routes as $method => $handle)
				$this->hook_register($method, 'pre_route2');
		
	}
	
	
	/* add support for hooked ajax calls from unathenticated users */
	function _unauth_wp_ajax() // on admin_init
	{
		if (!empty($this->wp_ajax_) && defined('DOING_AJAX') && DOING_AJAX)
		{
			if (in_array($_REQUEST['action'], $this->wp_ajax_) && wp_verify_nonce($_REQUEST['_wpnonce'], $_REQUEST['action']))
			{
				do_action('wp_ajax_' . $_REQUEST['action']);
				exit;
			}
		}
	}
	
	
	/* is used by router to prepare proper route2 calls, is meant to be called as a callback 4 actions/filters only */
	function pre_route2()
	{
		foreach ($this->__routes as $hook => $handle)
			if (preg_match("#".func_num_args()."?__".current_filter()."$#i", $hook))
			{
	
				$args = func_get_args();
				array_unshift($args, $handle);
	
				call_user_func_array(array($this, 'route2'), $args); // first match triggered
				break;
			}
	}
	
	
	/* is meant to route internal or external calls to MTB engine */
	function route2($handle = false)
	{
		if (!$handle)
			$handle = $_GET['page'];
	
		if ( false === strpos($handle, '/') )
			return;
	
		# define Ctrl class if not yet defined
		if ( !class_exists("{$this->prefix}_Ctrl") )
			$this->load("{$this->i8_path}/base.Ctrl.php");
	
		list($ctrl, $action) = explode('/', strtolower($handle));
	
		$this->load("{$this->path}/_ctrls/$ctrl.php");
		$ctrl_class = ucfirst($ctrl) . 'Ctrl';
	
	
		$args = func_get_args();
		array_shift($args); // shift off handle
	
		$this->ctrls[$ctrl] = new $ctrl_class($this, $action);
		return call_user_func_array(array($this->ctrls[$ctrl], $action), $args);
	}
	
	
	function _page_output($handle = false)
	{
		if (!$handle)
			$handle = $_GET['page'];
	
		if ( false === strpos($handle, '/') )
			return;
	
		list($ctrl, $action) = explode('/', strtolower($handle));
		if (!isset($this->ctrls[$ctrl]))
			wp_die("$ctrl::$action is not available!");
	
		$this->ctrls[$ctrl]->_output();
	}
	
	
	function _pages_add() // on admin_menu
	{
		$this->pages = apply_filters("i8_pages_{$this->classname}", $this->pages);
	
		if ( empty($this->pages) )
			return;
	
		for ( $i = 0, $max = sizeof($this->pages); $i < $max; $i++ ) :
	
			if ( isset($this->pages[$i]['page_title']) )
				$page_title = $menu_title = $this->pages[$i]['page_title'];
			else
				continue;
	
			$title_sanitized = sanitize_with_underscores($page_title);
	
			$defaults = array(
				'handle' => "page_$title_sanitized",
				'callback' => array(&$this, "page_$title_sanitized"),
				'capability' => 10,
				'icon_url' => ''
			);
			extract($this->pages[$i] = wp_parse_args($this->pages[$i], $defaults));
	
	
			# handle page parents and create them
			if ( isset($parent) )
			{
				if ( is_numeric($parent) )
					$parent = $this->pages[$parent]['handle'];
				else {
					$predefined = array(
						'management' => 'tools.php',
						'options' => 'options-general.php',
						'theme'	=> 'themes.php',
						'users'	=> current_user_can('edit_users') ? 'users.php' : 'profile.php',
						'dashboard'	=> 'index.php',
						'posts'	=> 'edit.php',
						'media'	=> 'upload.php',
						'links'	=> 'link-manager.php',
						'pages'	=> 'edit-pages.php',
						'comments' => 'edit-comments.php'
					);
					$parent = isset($predefined[$parent]) ? $predefined[$parent] : 'page_' . sanitize_with_underscores($parent);
				}
				# hack to avoid main title duplication as submenu
				if (!isset($GLOBALS['submenu'][$parent])) {
					$handle = $parent;
					$callback = $this->pages[$parent]['callback'];
				}
	
				$hook = add_submenu_page( $parent, $page_title, $menu_title, $capability, $handle, $callback );
			}
			else
			{
				//if ( isset($insert_after) )
	
				$hook = add_menu_page( $page_title, $menu_title, $capability, $handle, $callback, $icon_url );
			}
	
	
			# activate CRT engine if needed
			if (strpos($handle, '/') !== false)  // must be slash separated Ctrl/Action pair
			{
				/* controller action should be called before output is started (usually by admin-head.php), so page
				generation runs on load-$page_hook action (before output, @see: wp-admin/admin.php), which among other
				things let's it to load specific scripts, styles and do redirects. And then on usual page action buffered
				stuff is outputted */
				add_action("load-$hook", array($this, "route2"));
	
				remove_action($hook, $callback);  // for MTB we need to replace default action with our own
				add_action($hook, array($this, "_page_output"));
			}
	
	
			# save end values, just to keep it consisstent
			$this->pages[$i] = compact('parent','page_title','menu_title','capability','handle','callback','icon_url','hook');
	
		endfor;
	}
	
	
	function _activation_operations()
	{
		$version = $this->i8_data['Version'];
	
		# check if upgrade needed
		$prev_version = get_option("{$this->namespace}version");
		$upgrade_needed = !$prev_version || -1 == version_compare($prev_version, $version);
	
		# create tables and add their names to env
		if (!empty($this->tables))
		{
			global $wpdb;
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
			$existing_tables = $wpdb->get_col("SHOW TABLES;");
	
			foreach ($this->tables as $table => $sql)
			{
				$table_exists = true;
	
				# table should already be defined (see: __construct())
				if (!isset($wpdb->$table) || in_array($table, $wpdb->tables))
					continue;
	
				# check whether table already exists...
				if (!in_array($wpdb->$table, $existing_tables))
					$table_exists = false;
	
				# ...and create it, if it's - not, or if upgrade needed
				if (!$table_exists  || $upgrade_needed)
				{
					$sql = preg_replace("#^CREATE TABLE[^\n]+\n#i", "CREATE TABLE `{$wpdb->$table}` (\n", trim($sql));
					dbDelta($sql);
				}
			}
		}
	
		if ($upgrade_needed)
		{
			update_option("{$this->namespace}options", apply_filters("i8_options_4_upgrade_{$this->classname}", $this->defaults, $prev_version, $version));
			update_option("{$this->namespace}version", $version);
		}
	
	
		do_action("i8_{$this->namespace}activated");
	}
	
	
	function _deactivation_operations() {}
	
	
	function deactivate() {}
	
	protected function register_activation_deactivation_hooks() {}
	
	
	// Uninstall logic
	
	function _uninstalling() {}
	
	
	
	//	Notices Management
	
	function warn($msg)
	{
		if ( is_string($msg) )
			$params['message'] = $msg;
		else
			$params =& $msg;
	
		$defaults = array(
			'critical' 	=> true,
			'class'		=> 'error'
		);
		$this->msgs[] = wp_parse_args($params, $defaults);
	}
	
	function note($params)
	{
		if ( is_string($msg) )
			$params['message'] = $msg;
		else
			$params =& $msg;
	
		$defaults = array(
			'critical' 	=> false,
			'class'		=> 'updated'
		);
		$this->msgs[] = wp_parse_args($params, $defaults);
	}
	
	function pa_admin_notices()
	{
		if ( empty($this->msgs) ) return;
		
		foreach ($this->msgs as $msg)  {
			?><div class="<?php echo $msg['class']; ?>"><p><?php echo $msg['message']; ?></p></div><?php
		
			if ($msg['critical'])
				$this->deactivate();
		}
	}
	
	
	// Options Management
	function _options_register()
	{
		register_setting($this->options_handle, $this->options_handle, array(&$this, 'options_validate'));
	}
	
	
	function o($name, $value = false)
	{
		if (func_num_args() == 1)
			return $this->options[$name];
		else
		{
			$this->options[$name] = $value;
			$this->options_update();
		}
	}
	
	
	function o_name($name)
	{
		echo "{$this->options_handle}[$name]";
	}
	
	
	function options_init()
	{
		$this->options_handle = "{$this->namespace}options";
		$this->options_get(true);
	}
	
	
	function options_validate($input)
	{
		# take care of password fields, which are emptied on show for security reasons
		foreach ($input as $name => $o)
			if (empty($o['value']) && 'password' == $this->options[$name]['type']) {
				$input[$name]['value'] = $this->options[$name]['value'];
			}
	
		return apply_filters("i8_options_validate_{$this->classname}", $input);
	}
	
	
	function options_get($from_db = false)
	{
		if (!$from_db && !empty($this->options))
			return $this->options;
	
		if (empty($this->defaults))
			$this->defaults = $this->options;
	
		return $this->options = array_merge_better($this->options, get_option($this->options_handle));
	}
	
	
	function options_update()
	{
		if (!empty($this->options))
			update_option($this->options_handle, $this->options);
	}
	
	
	function options_form()
	{
		if (empty($this->options))
			return;
		?>
		<form method="post" action="options.php">
		<?php settings_fields($this->options_handle); ?>
		
		<table class="form-table">
		<?php foreach ($this->options as $name => $o) :  ?>
		<tr valign="top">
			<th scope="row"><label><?php echo $o['label']; ?></label></th>
			<td>
			<?php if (!empty($o['type'])) {
				$method = "options_field_{$o['type']}";
				if (method_exists($this, $method))
					$this->$method($name, $o);
			} ?>
			</td>
		</tr>
		<?php endforeach; ?>
		</table>  
		
		<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="Save" />
		</p>
		
		</form>	
		<?php	
	}
	
	
	function options_field_text($name, &$o)
	{
		extract($o);
		?><input type="text" name="<?php echo $this->options_handle; ?>[<?php echo $name; ?>][value]" class="<?php echo $class; ?>" value="<?php echo $value; ?>" /> <?php echo $desc;
	}
	
	function options_field_password($name, &$o)
	{
		extract($o);
		?><input type="password" name="<?php echo $this->options_handle; ?>[<?php echo $name; ?>][value]" class="<?php echo $class; ?>" value="" /> <?php echo $desc;
	}
	
	
	// Scripts & Styles Management
	
	function script_add($params)
	{
		$this->script_style_add($params);
	}
	
	
	function pa_wp_print_scripts()
	{
		if (empty($this->scripts))
				return;
		
		foreach ($this->scripts as $script) {
			if ('1' == $script['admin'] && !is_admin() || '-1' == $script['admin'] && is_admin())
				continue;
			wp_enqueue_script($script['id'], $script['url'], $script['deps'], $script['ver'], $script['in_footer']);
		}
	}
	
	
	function style_add($params)
	{
		$this->script_style_add($params, '.css');
	}
	
	
	function pa_admin_print_styles()
	{
		if (!empty($this->styles))
			$this->wp_enqueue_styles();
	}
	
	
	function pa_wp_print_styles()
	{
		if (!empty($this->styles))
			$this->wp_enqueue_styles();
	}
	
	
	function wp_enqueue_styles()
	{
		foreach ($this->styles as $style) {
			if ('1' == $script['admin'] && !is_admin() || '-1' == $script['admin'] && is_admin())
				continue;
			wp_enqueue_style($style['id'], $style['url'], $style['deps'], $style['ver'], $style['media']);
		}
	}
	
	
	protected function script_style_add(&$params, $ext = '.js')
	{
		if (is_string($params))
			if ($ext == substr($params, -strlen($ext))) { // has .js extension
				$params = array(
					'url' => $params
				);
			} else
				$params = array(
					'id' => $params
				);
		
		if (empty($params['id']))
			$params['id'] = basename($params['url'], $ext);
		
		if ($ext == '.js')
			$this->scripts[] = $params;
		else
			$this->styles[] = $params;
	}
	
	
	// Output
	
	function json($output)
	{
		return $this->output($output, 'json');
	}
	
	
	function output($output, $format = '')
	{
		if ('json' == $format) {
			$header = '';
			$output = json_encode((array)$output);
		} elseif ('xml' == $format) {
			$header = '';
			$output = '';
		}
		
		if (DOING_AJAX) {
			echo $output;
			exit;
		} else
			return $output;
	}
	
	
	/* Helpers */
	function load($path, $once = true, $buffer = false, $vars = null)
	{
		if (file_exists($path))
		{
			if (!is_null($vars))
				extract($vars);
		
			if ($buffer) ob_start();
		
			$once ? require_once($path) : include($path);
		
			if ($buffer) return ob_get_clean();
		
			return;
		}
		wp_die("<strong>$path</strong> not found!");
	}
	
	
	/* CRT related */
	function the_base($ctrl, $action, $params = null)
	{
		echo $this->get_the_base($ctrl, $action, $params);
	}
	
	function get_the_base($ctrl, $action, $params = null)
	{
		$querystr = '';
		if (!empty($params) && is_array($params))
			$querystr = '&' . http_build_query($params);
		
		return admin_url('admin.php') . "?page=$ctrl/$action" . $querystr;
	}

	
}

?>