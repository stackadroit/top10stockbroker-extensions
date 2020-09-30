<?php

namespace Stackadroit\Topstockbroker;

use Walker_Nav_Menu;

use function get_post_type;
use function get_post_types;
use function get_post_type_archive_link;
use function is_search;
use function sanitize_title;
use function add_filter;
use function remove_filter;
use function Stackadroit\Topstockbroker\compare_base_url;

/**
 * Cleaner navigation walker.
 *
 * Walker_Nav_Menu (WordPress default) example output:
 *   <li id="menu-item-8" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-8"><a href="/">Home</a></li>
 *   <li id="menu-item-9" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-9"><a href="/sample-page/">Sample Page</a></li>
 *
 * NavWalker example output:
 *   <li class="menu-home"><a href="/">Home</a></li>
 *   <li class="menu-sample-page"><a href="/sample-page/">Sample Page</a></li>
 *
 * @package Stackadroit\Topstockbroker
 */
class NavWalker extends Walker_Nav_Menu
{
    /**
     * Is current post a custom post type?
     *
     * @var bool
     */
    protected $is_cpt;

    /**
     * Archive page for current URL.
     *
     * @var string
     */
    protected $archive;

    public function __construct()
    {
        $cpt              = get_post_type();

        $this->is_cpt     = in_array($cpt, get_post_types(array('_builtin' => false)));
        $this->archive    = get_post_type_archive_link($cpt);
        $this->is_search  = is_search();
    }

    public function checkCurrent($classes)
    {
        return preg_match('/(current[-_])|active/', $classes);
    }

    public function displayElement($element, &$children_elements, $max_depth, $depth, $args, &$output)
    {
        $element->is_subitem = ((!empty($children_elements[$element->ID]) && (($depth + 1) < $max_depth || ($max_depth === 0))));

        if ($element->is_subitem) {
            foreach ($children_elements[$element->ID] as $child) {
                if ($child->current_item_parent || compare_base_url($this->archive, $child->url)) {
                    $element->classes[] = 'active';
                }
            }
        }

        $element->is_active = (!empty($element->url) && strpos($this->archive, $element->url));

        if ($element->is_active && !$this->is_search) {
            $element->classes[] = 'active';
        }

        parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
    }

    public function cssClasses($classes, $item, $args, $depth)
    {
        $slug = sanitize_title($item->title);

        // Fix core `active` behavior for custom post types
        if ($this->is_cpt) {
            $classes = str_replace('current_page_parent', '', $classes);

            if ($this->archive && !$this->is_search) {
                if (compare_base_url($this->archive, $item->url)) {
                    $classes[] = 'active';
                }
            }
        }

        // Remove most core classes
        $classes = preg_replace('/(current(-menu-|[-_]page[-_])(item|parent|ancestor))/', 'active', $classes);
        $classes = preg_replace('/^((menu|page)[-_\w+]+)+/', '', $classes);

        // Re-add core `menu-item` class
        $classes[] = 'menu-item';

        // Re-add core `menu-item-has-children` class on parent elements
        if ($item->is_subitem && $depth == 0) {
            $classes[] = 'menu-item-has-children';
            //$classes[] = 'dropdown';
        }

        if ($item->is_subitem && $depth == 1) {
            $classes[] = 'menu-item-has-children';
            //$classes[] = 'dropdown-submenu';
        }

        // Add `menu-<slug>` class
        $classes[] = 'menu-' . $slug;

        //add megamenu class if it's selected
        if($item->use_megamenu && $depth == 0) {
            $classes[] = 'megamenu';
        }

        //Add column to mega menu elements
        if($depth == 1) {
            if($item->menu_item_parent) {
                $parent_use_megamenu = get_post_meta($item->menu_item_parent, '_menu_item_use_megamenu', true);
                $parent_panel_column = get_post_meta($item->menu_item_parent, '_menu_item_panel_column', true);
                if($parent_use_megamenu) {
                    $total_col = $parent_panel_column ? $parent_panel_column : 4;
                    $num_col = $item->mega_item_column && $item->mega_item_column > 0 ? $item->mega_item_column : 1;
                    $classes[] = 'col-md-'.$this->megaMenuItemColumnClass($total_col, $num_col).' col-sm-6 col-xs-12';
                }
            }
        }

        $classes = array_unique($classes);
        $classes = array_map('trim', $classes);

        return array_filter($classes);
    }

    public function cssSubClasses($classes, $args, $depth)
    {

        // add clearfix for clear float
        $classes[] = 'clearfix';

        // drop down menu
        //$classes[] = 'dropdown-menu';


        $classes = array_unique($classes);
        $classes = array_map('trim', $classes);

        return array_filter($classes);
    }
    
    public function itemTitle($title, $item, $args, $depth){
        $item_output = '';
        
        $item_output .= $title;

        $parent_use_megamenu =get_post_meta($item->menu_item_parent, '_menu_item_use_megamenu', true);
        if($args->walker->has_children && ($depth == 0)) {
                $item_output .= '<span class="open-submenu"><i class="fa fa-angle-down"></i></span></a>';
        }elseif($args->walker->has_children && (($depth == 1 && !$parent_use_megamenu))){
                $item_output .= '<span class="open-submenu"><i class="fa fa-angle-right"></i></span></a>';
        }

        
            
        return $item_output;
    }

    public function navMenuStartEl( $item_output, $item, $depth, $args ){

        if($args->walker->has_children && ($depth == 0 || ($depth == 1))) {
            $item_output .= '<span class="caret-submenu"><i class="fa fa-angle-down" aria-hidden="true"></i></span>';
        }

        return $item_output;
    }

    protected function megaMenuItemColumnClass($total = 4, $col = 1) {
        $col = $col > $total ? $total : $col;
        if($total == 5) {
            return '15';
        }
        return 12/$total*$col;
    }

    public function walk($elements, $max_depth, ...$args)
    {
        // Add filters
        add_filter('nav_menu_css_class', array($this, 'cssClasses'), 10, 4);
        add_filter('nav_menu_submenu_css_class', array($this, 'cssSubClasses'), 10, 4);
        add_filter('nav_menu_item_title', array($this, 'itemTitle'), 10, 4);
        add_filter('walker_nav_menu_start_el', array($this, 'navMenuStartEl'), 10, 4);
        add_filter('nav_menu_item_id', '__return_null');

        // Perform usual walk
        $output = call_user_func_array(['parent', 'walk'], func_get_args());

        // Unregister filters
        remove_filter('nav_menu_css_class', [$this, 'cssClasses']);
        remove_filter('nav_menu_submenu_css_class', [$this, 'cssSubClasses']);
        remove_filter('nav_menu_item_title', [$this, 'itemTitle']);
        remove_filter('walker_nav_menu_start_el', [$this, 'navMenuStartEl']);
        remove_filter('nav_menu_item_id', '__return_null');

        // Return result
        return $output;
    }

    /**
     * Everything below this line is passthrus for WordPress.
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output)
    {
        return $this->displayElement($element, $children_elements, $max_depth, $depth, $args, $output);
    }
}
