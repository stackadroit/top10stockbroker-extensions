<?php

namespace Stackadroit\Topstockbroker\Modules;

use Stackadroit\Topstockbroker\NavWalkerEdit;

use function add_filter;
use function add_action;
use function get_post_meta;
use function update_post_meta;

/**
 * Admin edit
 */
class NavWalkerEditModule extends AbstractModule
{
    /**
     * Name of the module.
     *
     * @var string
     */
    protected $name = 'nav-walker-edit';

    /**
     * Module handle.
     *
     * @return void
     */
    public function handle()
    {
        add_action('wp_update_nav_menu_item', [$this, 'navUpdate'], 10, 3);
        $this->filter('wp_setup_nav_menu_item', 'navItem');
        $this->filter('wp_edit_nav_menu_walker', 'editWalker', 10, 2);

    }

    /**
     * @param $menu_id
     * @param $menu_item_db_id
     * @param $args
     */
    public function navUpdate($menu_id, $menu_item_db_id, $args)
    {
        $use_megamenu = isset($_POST['menu-item-use_megamenu'][$menu_item_db_id]) ? 1 : 0;
        update_post_meta($menu_item_db_id, '_menu_item_use_megamenu', $use_megamenu);

        $use_item_icon = isset($_POST['menu-item-use_list_icon'][$menu_item_db_id]) ? 1 : 0;
        update_post_meta($menu_item_db_id, '_menu_item_list_icon', $use_item_icon);

        if(isset($_POST['menu-item-panel_column'][$menu_item_db_id])) {
            update_post_meta($menu_item_db_id, '_menu_item_panel_column', $_POST['menu-item-panel_column'][$menu_item_db_id]);
        }

        if(isset($_POST['menu-item-mega_item_column'][$menu_item_db_id])) {
            update_post_meta($menu_item_db_id, '_menu_item_mega_item_column', $_POST['menu-item-mega_item_column'][$menu_item_db_id]);
        }

    }

    /**
     * Adds value of custom option to $item object that will be passed to NavWalkerEdit
     * @param $menu_item
     *
     * @return mixed
     */
    public function navItem($menu_item)
    {
        if (isset($menu_item->ID)) {
            $menu_item->use_megamenu = get_post_meta($menu_item->ID, '_menu_item_use_megamenu', true);
            $menu_item->use_list_icon = get_post_meta($menu_item->ID, '_menu_item_list_icon', true);
            $menu_item->panel_column = get_post_meta($menu_item->ID, '_menu_item_panel_column', true);
            $menu_item->mega_item_column = get_post_meta($menu_item->ID, '_menu_item_mega_item_column', true);
        }
        return $menu_item;
    }

    /**
     * @param $walker
     * @param $menu_id
     *
     * @return string
     */
    public function editWalker($walker, $menu_id)
    {
        return 'Stackadroit\\Topstockbroker\\NavWalkerEdit';
    }

    /**
     * Condition under which the module is loaded.
     *
     * @return bool
     */
    protected function condition()
    {
        return apply_filters(
            'topstockbroker/load-module/' . $this->provides(),
            $this->options->enabled
        );
    }

}
