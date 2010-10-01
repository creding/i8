<?php

/* Credits for this thing goes to Mike Schinkel: https://gist.github.com/792b7aa5b695d1092520 */

class i8_Menu {


    function delete_section( $menu, $args = array() )
    {
        $section = $this -> get_section( $menu, $args );
        $section -> delete();
    }

    function rename_section( $section, $new_title, $args = array() )
    {
        $section = $this -> get_section( $section, $args );
        $section -> set_title( $new_title );
    }

    function swap_sections( $from_menu, $to_menu, $args = array() )
    {
        $args = wp_parse_args( $args, array( 'find_from_by' => 'title', 'find_to_by' => 'title', ) );
        extract( $args );
        $section = $this -> get_section( $from_menu, array( 'find_by' => $find_from_by ) );
        $section -> swap_with( $to_menu, array( 'find_by' => $find_to_by ) );
    }

    function pause_section()
    {
        WP_AdminMenuSection :: set_refresh( false );
    }

    function resume_section()
    {
        WP_AdminMenuSection :: set_refresh( false );
    }

    function update_section( $menu, $actions = array(), $args = array() )
    {
        $this -> pause_section();
        $section = $this -> get_section( $menu, $args );
        extract( $args );

        foreach( $actions as $action )
        {
            $action = wp_parse_args( $action, array( 'item' => false, 'find_by' => 'title', 'new_title' => 'Did Not Set Title During Menu Item Rename!' ) );

            switch( isset( $action[ 'action' ] )?$action[ 'action' ]:$action[ 0 ] )
            {

            case 'copy-item':
                $section -> copy_item( $section, $action['item'], $action[ 'find_by' ] );
                break;


            case 'rename-item':
                $section -> rename_item( $action[ 'item' ], $action[ 'new_title' ], $action[ 'find_by' ] );
                break;
            case 'delete-item':
                $section -> delete_item( $action[ 'item' ], $action[ 'find_by' ] );
                break;
            }
        }

        WP_AdminMenuSection :: reset_refresh();
    }

    function get_section( $section, $args = array() )
    {
        return( is_a( $section, 'WP_AdminMenuSection' ) ? $section : new WP_AdminMenuSection( $section, $args = array() ) );
    }

    function get_item_array( $section, $item, $args = array() )
    {
        global $submenu;
        $item = $this -> get_item( $section, $item, $args );
        return $submenu[ $item -> parent_slug ][ $item -> index ];
    }

    function get_item( $section, $item, $args = array() )
    {
        $section = $this -> get_section( $section, $args );

        if( !is_a( $section, 'WP_AdminMenuItems' ) || $item -> parent_slug != $section -> get_slug() )
        {
            $args = wp_parse_args( $args, array( 'find_by' => 'title', 'find_item_by' => 'title', ) );
            $submenus = $section -> get_submenus( $section );
            $args[ 'find_by' ] = $args[ 'find_item_by' ];
            $item = $section -> find_item( $item, $args );
        }

        return $item;
    }

    function copy_item( $section, $section_item, $args = array() )
    {
        if( !is_a( $section, 'WP_AdminMenuItem' ) )
        {
            if( is_array( $section_item ) && count( $section_item ) == 2 )
            {
                $item_to_copy = array_pop( $section_item );
                $section_to_copy_to = $this -> get_section( array_pop( $section_item ), $args );
                $section_item = $this -> get_item( $section_to_copy_to, $item_to_copy, $args );
            }
            else
            {
                echo 'ERROR: copy_item() was passed an unexpected value item as it\'s second parameter. Expected either an instance of WP_AdminMenuItem or a two element array containing section title and item title. Got this instead:';
                print_r( $section_item );
                die();
            }

            $this -> add_item( $section, $section_item );
        }
    }


    function rename_item($section, $item, $new_title)
    {
        $section = $this -> get_section( $section );
        $section->rename_item($item, $new_title);

    }


    function add_item( $section, $item, $args = array() )
    {
        $section = $this -> get_section( $section, $args );
        $section -> add_item( $item, $args );
    }



}


/*
Classes to encapsulate access to admin menu global arrays $menu and $submenu

See:
  -- http://core.trac.wordpress.org/ticket/12718
	-- http://core.trac.wordpress.org/ticket/11517

*/

if (!class_exists('WP_AdminMenuSection')):
class WP_AdminMenuSection
{
	static $refresh;
	var $index;
	var $submenus = array();

	function is_item_array( $item )
	{
        if (!is_array($item))
            return false;

		$is_item_array = true;

		foreach( array_keys( $item ) as $key )
		{
			if( !is_numeric( $key ) )
			{
				$is_item_array = false;
				break;
			}
		}

		return $is_item_array;
	}

	function add_item( $item, $args = array() )
	{
		global $submenu;
		$args = wp_parse_args( $args, array( 'where' => 'bottom',// start, top, bottom, end, before, after
		'before' => false, 'after' => false, ) );
		extract( $args );
		$item_list = &$submenu[ $this -> get_slug() ];

        if (!is_array($item_list))
            return false;

		if( is_a( $item, 'WP_AdminMenuItem' ) )
		{
			$item_type = 'object';
			$item_array = $submenu[ $item -> parent_slug ][ $item -> index ];
		}
		elseif( $this -> is_item_array( $item ) )
		{
			$item_type = 'item_array';
			$item_array = $item;
		}
		else
		{
			$item_type = 'assoc_array';
			$item = wp_parse_args( $item, array( 'title' => 'Need to add a title via add_admin_menu_item()', 'slug' => 'need-to-add-a-slug-via-add_admin_menu_item', 'page_title' => false, 'capability' => 'edit_posts', 'function' => '', ) );

			if( !$item[ 'page_title' ] )
			{
				$item[ 'page_title' ] = $item[ 'title' ];
			}

			add_submenu_page( $this -> get_slug(), $item[ 'page_title' ], $item[ 'title' ], $item[ 'capability' ], $item[ 'slug' ], $item[ 'function' ] );

			if( $where != 'end' )
			{// If 'end', do nothing more./
                $item_array = array_pop( $item_list );
			}
		}

		switch( $where )
		{
		case 'bottom':
		case 'end':

			if( $item_type != 'assoc_array' )
			{
				$last_index = $this -> get_last_item_index();
				$item_list[ $last_index+5 ] = $item_array;
			}

			break;
		case 'start':
		case 'top':// No, array_unshift() won't do this instead.
			$last_index = $this -> get_last_item_index()+5;// Menus typically go in increments of 5.
			$item_list[ $last_index ] = null;// Create a placeholder at end to allow us to shift them all up
			$item_indexes = array_keys( $item_list );
			$new_item_list = array();
			$new_item_list[ $item_indexes[ 0 ] ] = $item_array;// Finally add the item array to the beginning.
			for( $i = 1; $i<count( $item_indexes ); $i++ )
			{
				$new_item_list[ $item_indexes[ $i ] ] = $item_list[ $item_indexes[ $i-1 ] ];
			}

			$item_list = $new_item_list;
			break;
		}

		$this -> refresh_submenus();
	}

	function get_last_item_index()
	{
		global $submenu;
		return end( array_keys( $submenu[ $this -> get_slug() ] ) );
	}

	static function get_refresh()
	{
		return self :: $refresh;
	}

	static function set_refresh_on()
	{
		self :: $refresh = true;
	}

	static function set_refresh_off()
	{
		self :: $refresh = false;
	}

	static function set_refresh( $new_refresh )
	{
		self :: $refresh = $new_refresh;
	}

	static function reset_refresh()
	{
		self :: $refresh = null;
	}

	function __construct( $section, $args = array() )
	{
		$this -> WP_AdminMenuSection( $section, $args );
	}

	function WP_AdminMenuSection( $section, $args = array() )
	{
		global $menu;
		$args = wp_parse_args( $args, array( 'find_by' => 'title', 'refresh' => false, ) );
		extract( $args );
		$section = strtolower( $section );

		foreach( $menu as $index => $section_array )
		{
			switch( $find_by )
			{
			case 'title':

				if( $section == strtolower( $section_array[ 0 ] ) )
				{
					$found = $index;
				}

				break;
			case 'file':
			case 'slug':

				if( $section == strtolower( $section_array[ 2 ] ) )
				{
					$found = $index;
				}

				break;
			}

			if( isset( $found ) )
			{
				break;
			}
		}

		$this -> index = $index = ( isset( $found ) ? $found : false );

		if( $this -> index )
		{
			$this -> refresh_submenus();
		}
	}

	function rename_item( $item, $new_title, $args = array() )
	{
		$item = $this -> find_item( $item, $args );
        if ($item)
            $item -> set_title( $new_title );
	}

	function swap_with( $section, $args = array() )
	{
		$with = get_admin_menu_section( $section, $args );
		global $menu;
		$temp = $menu[ $this -> index ];
		$menu[ $this -> index ] = $menu[ $with -> index ];
		$menu[ $with -> index ] = $temp;
		$temp = $this -> index;
		$this -> index = $with -> index;
		$with -> index = $temp;
		$temp = $this -> submenus;
		$this -> submenus = $with -> submenus;
		$with -> submenus = $temp;
	}

	function delete()
	{
		global $submenu;
		unset( $submenu[ $this -> get_slug() ] );
		global $menu;
		unset( $menu[ $this -> index ] );
	}

	function delete_item( $item, $args = array() )
	{
		$item = $this -> find_item( $item, $args );
		global $submenu;
		unset( $submenu[ $item -> parent_slug ][ $item -> index ] );
		unset( $this -> submenus[ $item -> index ] );
	}

	function find_item( $item, $args = array() )
	{
		if( is_a( $item, 'WP_AdminMenuItem' ) )
		{
			return $item;
		}

		$args = wp_parse_args( $args, array( 'find_by' => 'title', 'refresh' => true, ) );
		extract( $args );

		if( !is_null( self :: $refresh ) )
		{// If the global refresh setting
			if( self :: $refresh )// has been set
			$this -> refresh_submenus();
		}// honor it first.
		else// If the global refresh setting not set
		if( $refresh )
		{// and parameter refresh has been set
			$this -> refresh_submenus();
		}// honor it instead.

		foreach( $this -> submenus as $index => $item_obj )
		{
            $found = false;

			switch( $find_by )
			{
			case 'index':

				if( $item == $index )
				{
					$found = true;
				}

				break;
			case 'slug':
			case 'file':

				if( $item == $item_obj -> get_slug() )
				{
					$found = true;
				}

				break;
			case 'title':

				if( $item == $item_obj -> get_title() )
				{
					$found = true;
				}

				break;
			}


			if( $found )
			{
				return $this -> submenus[ $index ];
			}
		}

		return false;
	}

	function refresh_submenus()
	{// This in case something external changes the submenu indexes
		$this -> submenus = $this -> get_submenus( $this );
	}

	function get_submenus( $section )
	{
		if( is_a( $section, 'WP_AdminMenuSection' ) )
		{
			$slug = $section -> get_slug();
		}
		elseif( is_string( $section ) )
		{
			$slug = $section;
		}

		global $submenu;
		$submenus = array();

		if( isset( $submenu[ $slug ] ) )
		{
			foreach( $submenu[ $slug ] as $index => $item )
			{
				$submenus[ $index ] = new WP_AdminMenuItem( $slug, $index );
			}
		}

		return $submenus;
	}

	function get_title()
	{
		return $GLOBALS[ 'menu' ][ $this -> index ][ 0 ];
	}

	function set_title( $new_title )
	{
		$GLOBALS[ 'menu' ][ $this -> index ][ 0 ] = $new_title;
	}

	function get_capability()
	{
		return $GLOBALS[ 'menu' ][ $this -> index ][ 1 ];
	}

	function set_capability( $new_capability )
	{
		$GLOBALS[ 'menu' ][ $this -> index ][ 1 ] = $new_capability;
	}

	function get_file()
	{// 'slug' & 'file' are synonyms for admin menu
		return $GLOBALS[ 'menu' ][ $this -> index ][ 2 ];
	}

	function set_file( $new_file )
	{
		$GLOBALS[ 'menu' ][ $this -> index ][ 2 ] = $new_file;
	}

	function get_slug()
	{// 'slug' & 'file' are synonyms for admin menu
		return $this -> get_file();
	}

	function set_slug( $new_slug )
	{
		$this -> set_file( $new_slug );
	}

	function get_unused()
	{
		return $GLOBALS[ 'menu' ][ $this -> index ][ 3 ];
	}

	function set_unused( $new_unused )
	{
		$GLOBALS[ 'menu' ][ $this -> index ][ 3 ] = $new_unused;
	}

	function get_class()
	{
		return $GLOBALS[ 'menu' ][ $this -> index ][ 4 ];
	}

	function set_class( $new_class )
	{
		$GLOBALS[ 'menu' ][ $this -> index ][ 4 ] = $new_class;
	}

	function get_id()
	{
		return $GLOBALS[ 'menu' ][ $this -> index ][ 5 ];
	}

	function set_id( $new_id )
	{
		$GLOBALS[ 'menu' ][ $this -> index ][ 5 ] = $new_id;
	}

	function get_iconsrc()
	{
		return $GLOBALS[ 'menu' ][ $this -> index ][ 6 ];
	}

	function set_iconsrc( $new_iconsrc )
	{
		$GLOBALS[ 'menu' ][ $this -> index ][ 6 ] = $new_iconsrc;
	}
}
endif;


if (!class_exists('WP_AdminMenuItem')):
class WP_AdminMenuItem
{
	var $parent_slug;
	var $index;

	function __construct( $parent_slug, $index )
	{
		$this -> WP_AdminMenuItem( $parent_slug, $index );
	}

	function WP_AdminMenuItem( $parent_slug, $index )
	{
		$this -> parent_slug = $parent_slug;
		$this -> index = $index;
	}

	function get_title()
	{
		return $GLOBALS[ 'submenu' ][ $this -> parent_slug ][ $this -> index ][ 0 ];
	}

	function set_title( $new_title )
	{
		$GLOBALS[ 'submenu' ][ $this -> parent_slug ][ $this -> index ][ 0 ] = $new_title;
	}

	function get_capability()
	{
		return $GLOBALS[ 'submenu' ][ $this -> parent_slug ][ $this -> index ][ 1 ];
	}

	function set_capability( $new_capability )
	{
		$GLOBALS[ 'submenu' ][ $this -> parent_slug ][ $this -> index ][ 1 ] = $new_capability;
	}

	function get_file()
	{// 'slug' & 'file' are synonyms for admin menu
		return $GLOBALS[ 'submenu' ][ $this -> parent_slug ][ $this -> index ][ 2 ];
	}

	function set_file( $new_file )
	{
		$GLOBALS[ 'submenu' ][ $this -> parent_slug ][ $this -> index ][ 2 ] = $new_file;
	}

	function get_slug()
	{// 'slug' & 'file' are synonyms for admin menu
		return $this -> get_file();
	}

	function set_slug( $new_slug )
	{
		$this -> set_file( $new_slug );
	}
}
endif;




?>