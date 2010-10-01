<?php

if (!function_exists('sanitize_with_underscores')) :
function sanitize_with_underscores($str)
{
	return preg_replace('|\W+|', '_', strtolower($str));
}
endif;


if (!function_exists('is_numeric_array')) :
function is_numeric_array(&$array)
{
	return array_values($array) === $array;
}
endif;


if (!function_exists('array_merge_numeric')):
function array_merge_numeric(&$args, &$defaults)
{
	foreach ($defaults as $key => $value)
		if (!isset($args[$key]))
			$args[$key] = $value;
			
	ksort($args, SORT_NUMERIC);
			
	return $args;
}
endif;


if (!function_exists('array_merge_better')) :
function array_merge_better($defaults, $r)
{	
	if (!empty($r)) {

		foreach ($r as $key => $value)
		{
			if (is_array($value) && is_array($defaults[$key]))
				$defaults[$key] = array_merge_better($defaults[$key], $value); 
			else 	
				$defaults[$key] = is_array($value) ? $value : trim($value);
		}
	}
		
	return $defaults;
}
endif;


if (!function_exists('array_search_better')):
function array_search_better($needle, &$array)
{
	foreach($array as $key => $value) 
	{
		if (is_array($value) && ($result = array_search_better($needle, $value)) !== false)
			return $result;
		if ($needle == $value) 
			return $key;
	}
	return false; 
}
endif;


if (!function_exists('array_unset_empty')) :
function array_unset_empty($array)
{
	if (is_array($array))
	{		
		$is_assoc = !is_numeric_array($array);
	
		foreach ($array as $key => $value)
			if ('' == trim($value))
				if ($is_assoc)
					unset($array[$key]);
				else
					array_splice($array, $key, 1);
					
		if ($is_assoc)
			$array =& array_merge($array);
	}
				
	return $array;
}
endif; 

if (!function_exists('strip_whitespace')):
function strip_whitespace($html, $echo = false)
{
	$stripped = preg_replace( "/(?:(?<=\>)|(?<=\/\>))(\s+)(?=\<\/?)/", "", preg_replace('|<!--[^>]*-->|', '', $html));
	$stripped = str_replace(array("\r\n", "\n", "\r"), "", $stripped);

	if ($echo)
		echo $stripped;
	else
		return $stripped;
}
endif;



if (!function_exists('level2role')) :
function level2role($level) {
	switch ($level) {
	case 10:
	case 9:
	case 8:
		return 'administrator';
	case 7:
	case 6:
	case 5:
		return 'editor';
	case 4:
	case 3:
	case 2:
		return 'author';
	case 1:
		return 'contributor';
	case 0:
		return 'subscriber';
	}
}
endif;


if (!function_exists('role2minlevel')) :
function role2minlevel($role) {
	$map = array(
		'administrator' => 8,
		'editor'		=> 5,
		'author'		=> 2,
		'contributor'	=> 1,
		'subscriber'	=> 0
	);
	return $map[strtolower($role)]; 
}
endif;


if (!function_exists('role2maxlevel')) :
function role2maxlevel($role) {
	$map = array(
		'administrator' => 10,
		'editor'		=> 7,
		'author'		=> 4,
		'contributor'	=> 1,
		'subscriber'	=> 0
	);
	return $map[strtolower($role)]; 
}
endif;


if (!function_exists('get_current_url')) :
function get_current_url()
{
	$url = 'http';
	if ($_SERVER["HTTPS"] == "on") {$url .= "s";}
		$url .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $url;
}
endif;


if (!function_exists('get_post_by_slug')) :
function get_post_by_slug($slug, $pages = false)
{
    if (empty($slug))
        return false;

    if (is_array($pages) && !empty($pages))
    {
        foreach ($pages as $page)
            if ($page->post_name == $slug)
                return $page;
    }

    global $wpdb;
    return $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE post_name = '{$wpdb->escape($slug)}'");
}
endif;


if (!function_exists('is_add_page_of')) :
function is_add_page_of($post_type)
{
    global $pagenow;

    if (!is_array($post_type))
        $post_type = array($post_type);

    if ($pagenow == 'post-new.php' && in_array($_GET['post_type'], $post_type))
        return true;

    if ($pagenow == 'post.php' && $_GET['action'] == 'edit' && is_numeric($_GET['post']))
    {
        $post = get_post($_GET['post']);
        if ($post && in_array($post->post_type, $post_type))
            return true;
    }

    return false;
}
endif;


if (!function_exists('add_caps2')) :
function add_caps2($roles, $caps)
{
    $r = new WP_Roles;

    if (!is_array($roles))
       $roles = array($roles);

    # add_cap() writes to database on every call if use_db is true, don't need that
    $r->use_db = false;

    foreach ($roles as $role)
        if ($r->is_role($role))
            foreach ($caps as $cap)
                $r->add_cap($role, $cap);

    $r->use_db = true;

    update_option( $r->role_key, $r->roles );
}
endif;

?>