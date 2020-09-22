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

class NavWalkerEdit extends Walker_Nav_Menu
{
    public function apr_get_megamenu_columns() {
        return array(
            '2' => esc_html__('2 columns', 'efarm'),
            '3' => esc_html__('3 columns', 'efarm'),
            '4' => esc_html__('4 columns', 'efarm'),
            '5' => esc_html__('5 columns', 'efarm'),
            '6' => esc_html__('6 columns', 'efarm'),
        );
    }

    public function start_lvl( &$output, $depth = 0, $args = array() ) {}

    public function end_lvl( &$output, $depth = 0, $args = array() ) {}

    public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        global $_wp_nav_menu_max_depth;
        $_wp_nav_menu_max_depth = $depth > $_wp_nav_menu_max_depth ? $depth : $_wp_nav_menu_max_depth;

        ob_start();
        $item_id = esc_attr( $item->ID );
        $removed_args = array(
            'action',
            'customlink-tab',
            'edit-menu-item',
            'menu-item',
            'page-tab',
            '_wpnonce',
        );

        $original_title = '';
        if ( 'taxonomy' == $item->type ) {
            $original_title = get_term_field( 'name', $item->object_id, $item->object, 'raw' );
            if ( is_wp_error( $original_title ) )
                $original_title = false;
        } elseif ( 'post_type' == $item->type ) {
            $original_object = get_post( $item->object_id );
            //$original_title = get_the_title( $original_object->ID );
            $original_title = isset( $original_object->post_title ) ? $original_object->post_title : '';
        }

        $classes = array(
            'menu-item menu-item-depth-' . $depth,
            'menu-item-' . esc_attr( $item->object ),
            'menu-item-edit-' . ( ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? 'active' : 'inactive'),
        );

        $title = $item->title;

        if ( ! empty( $item->_invalid ) ) {
            $classes[] = 'menu-item-invalid';
            /* translators: %s: title of menu item which is invalid */
            $title = sprintf( esc_html__( '%s (Invalid)', 'efarm' ), $item->title );
        } elseif ( isset( $item->post_status ) && 'draft' == $item->post_status ) {
            $classes[] = 'pending';
            /* translators: %s: title of menu item in draft status */
            $title = sprintf( esc_html__('%s (Pending)', 'efarm'), $item->title );
        }

        $title = ( ! isset( $item->label ) || '' == $item->label ) ? $title : $item->label;

        $submenu_text = '';
        if ( 0 == $depth )
            $submenu_text = 'style="display: none;"';

        ?>
    <li id="menu-item-<?php echo esc_attr($item_id); ?>" class="<?php echo implode(' ', $classes ); ?>">
        <div class="menu-item-bar">
            <div class="menu-item-handle">
                <span class="item-title"><span class="menu-item-title"><?php echo esc_html( $title ); ?></span> <span class="is-submenu" <?php echo esc_attr($submenu_text); ?>><?php echo esc_html__( 'sub item','efarm' ); ?></span></span>
                <span class="item-controls">
						<span class="item-type"><?php echo esc_html( $item->type_label ); ?></span>
						<span class="item-order hide-if-js">
							<a href="<?php
                            echo wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'action' => 'move-up-menu-item',
                                        'menu-item' => $item_id,
                                    ),
                                    remove_query_arg($removed_args, admin_url( 'nav-menus.php' ) )
                                ),
                                'move-menu_item'
                            );
                            ?>" class="item-move-up"><abbr title="<?php esc_attr_e('Move up','efarm'); ?>">&#8593;</abbr></a>
							|
							<a href="<?php
                            echo wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'action' => 'move-down-menu-item',
                                        'menu-item' => $item_id,
                                    ),
                                    remove_query_arg($removed_args, admin_url( 'nav-menus.php' ) )
                                ),
                                'move-menu_item'
                            );
                            ?>" class="item-move-down"><abbr title="<?php esc_attr_e('Move down','efarm'); ?>">&#8595;</abbr></a>
						</span>
						<a class="item-edit" id="edit-<?php echo esc_attr($item_id); ?>" title="<?php esc_attr_e('Edit Menu Item','efarm'); ?>" href="<?php
                        echo ( isset( $_GET['edit-menu-item'] ) && $item_id == $_GET['edit-menu-item'] ) ? admin_url( 'nav-menus.php' ) : add_query_arg( 'edit-menu-item', $item_id, remove_query_arg( $removed_args, admin_url( 'nav-menus.php#menu-item-settings-' . $item_id ) ) );
                        ?>"><?php echo esc_html__( 'Edit Menu Item','efarm' ); ?></a>
					</span>
            </div>
        </div>

        <div class="menu-item-settings" id="menu-item-settings-<?php echo esc_attr($item_id); ?>">
            <?php if ( 'custom' == $item->type ) : ?>
                <p class="field-url description description-wide">
                    <label for="edit-menu-item-url-<?php echo esc_attr($item_id); ?>">
                        <?php echo esc_html__( 'URL','efarm' ); ?><br />
                        <input type="text" id="edit-menu-item-url-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-url" name="menu-item-url[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->url ); ?>" />
                    </label>
                </p>
            <?php endif; ?>
            <p class="description description-wide">
                <label for="edit-menu-item-title-<?php echo esc_attr($item_id); ?>">
                    <?php echo esc_html__( 'Navigation Label','efarm' ); ?><br />
                    <input type="text" id="edit-menu-item-title-<?php echo esc_attr($item_id); ?>" class="widefat edit-menu-item-title" name="menu-item-title[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->title ); ?>" />
                </label>
            </p>
            <p class="field-title-attribute description description-wide">
                <label for="edit-menu-item-attr-title-<?php echo esc_attr($item_id); ?>">
                    <?php echo esc_html__( 'Title Attribute','efarm' ); ?><br />
                    <input type="text" id="edit-menu-item-attr-title-<?php echo esc_attr($item_id); ?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->post_excerpt ); ?>" />
                </label>
            </p>
            <p class="field-link-target description">
                <label for="edit-menu-item-target-<?php echo esc_attr($item_id); ?>">
                    <input type="checkbox" id="edit-menu-item-target-<?php echo esc_attr($item_id); ?>" value="_blank" name="menu-item-target[<?php echo esc_attr($item_id); ?>]"<?php checked( $item->target, '_blank' ); ?> />
                    <?php echo esc_html__( 'Open link in a new window/tab','efarm' ); ?>
                </label>
            </p>
            <p class="description description-wide">
                <label for="edit-menu-item-icon-<?php echo esc_attr($item_id); ?>">
                    <?php echo 'Icon Class'; ?><br />
                    <input type="text" id="edit-menu-item-icon-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-icon"
                        <?php if (esc_attr( $item->icon )) : ?>
                            name="menu-item-icon[<?php echo esc_attr($item_id); ?>]"
                        <?php endif; ?>
                           data-name="menu-item-icon[<?php echo esc_attr($item_id); ?>]"
                           value="<?php echo esc_attr( $item->icon ); ?>" />
                    <span><?php echo wp_kses(__('Input icon class. You can see <a target="_blank" href="http://fortawesome.github.io/Font-Awesome/icons/">Font Awesome Icons</a>, <a target="_blank" href="https://linearicons.com/free">Linearicons </a>, <a target="_blank" href="http://themes-pixeden.com/font-demos/7-stroke/">Pe stroke icon7 </a>. For example: fa fa-picture-o', 'efarm'),array(
                            'a'=> array('href'=>array(), 'target' => array()
                            ),
                        )) ?></span>
                </label>
            </p>
            <p class="description">
                <label for="edit-menu-item-tip_label-<?php echo esc_attr($item_id); ?>">
                    <?php echo 'Tip Label'; ?><br />
                    <input type="text" id="edit-menu-item-tip_label-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-tip_label"
                        <?php if (esc_attr( $item->tip_label )) : ?>
                            name="menu-item-tip_label[<?php echo esc_attr($item_id); ?>]"
                        <?php endif; ?>
                           data-name="menu-item-tip_label[<?php echo esc_attr($item_id); ?>]"
                           value="<?php echo esc_attr( $item->tip_label ); ?>" />
                </label>
            </p>
            <p class="description">
                <label for="edit-menu-item-tip_color-<?php echo esc_attr($item_id); ?>">
                    <?php echo 'Tip Text Color'; ?><br />
                    <input type="text" id="edit-menu-item-tip_color-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-tip_color"
                        <?php if (esc_attr( $item->tip_color )) : ?>
                            name="menu-item-tip_color[<?php echo esc_attr($item_id); ?>]"
                        <?php endif; ?>
                           data-name="menu-item-tip_color[<?php echo esc_attr($item_id); ?>]"
                           value="<?php echo esc_attr( $item->tip_color ); ?>" />
                </label>
            </p>
            <p class="description">
                <label for="edit-menu-item-tip_bg-<?php echo esc_attr($item_id); ?>">
                    <?php echo 'Tip BG Color'; ?><br />
                    <input type="text" id="edit-menu-item-tip_bg-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-tip_bg"
                        <?php if (esc_attr( $item->tip_bg )) : ?>
                            name="menu-item-tip_bg[<?php echo esc_attr($item_id); ?>]"
                        <?php endif; ?>
                           data-name="menu-item-tip_bg[<?php echo esc_attr($item_id); ?>]"
                           value="<?php echo esc_attr( $item->tip_bg ); ?>" />
                </label>
            </p><br>
            <p class="field-css-classes description description-thin">
                <label for="edit-menu-item-classes-<?php echo esc_attr($item_id); ?>">
                    <?php echo esc_html__( 'CSS Classes (optional)','efarm' ); ?><br />
                    <input type="text" id="edit-menu-item-classes-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-classes" name="menu-item-classes[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( implode(' ', $item->classes ) ); ?>" />
                </label>
            </p>
            <p class="field-xfn description description-thin">
                <label for="edit-menu-item-xfn-<?php echo esc_attr($item_id); ?>">
                    <?php echo esc_html__( 'Link Relationship (XFN)','efarm' ); ?><br />
                    <input type="text" id="edit-menu-item-xfn-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->xfn ); ?>" />
                </label>
            </p>
            <p class="field-description description description-wide">
                <label for="edit-menu-item-description-<?php echo esc_attr($item_id); ?>">
                    <?php echo esc_html__( 'Description','efarm' ); ?><br />
                    <textarea id="edit-menu-item-description-<?php echo esc_attr($item_id); ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description[<?php echo esc_attr($item_id); ?>]"><?php echo esc_html( $item->description ); // textarea_escaped ?></textarea>
                    <span class="description"><?php echo esc_html__('The description will be displayed in the menu if the current theme supports it.','efarm'); ?></span>
                </label>
            </p>

            <?php
            /*
             * Add custom options
             */
            ?>
            <div class="wrap-custom-options-level0-<?php echo esc_attr($item_id); ?>" style="<?php echo esc_attr($depth == 0 ? 'display:block;' : 'display:none;') ?>">
                <p class="description">
                    <label for="edit-menu-item-use_megamenu-<?php echo esc_attr($item_id); ?>">
                        <input type="checkbox" id="edit-menu-item-use_megamenu-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-use_megamenu"
                            <?php if (esc_attr( $item->use_megamenu )) : ?>
                                name="menu-item-use_megamenu[<?php echo esc_attr($item_id); ?>]"
                            <?php endif; ?>
                               data-name="menu-item-use_megamenu[<?php echo esc_attr($item_id); ?>]"
                               value="1" <?php echo esc_attr($item->use_megamenu && $item->use_megamenu == 1 ? 'checked' : '') ?> />
                        <?php echo esc_html__('Mega menu', 'efarm'); ?>
                    </label>
                </p>
                <?php $panel_columns = $this->apr_get_megamenu_columns(); ?>
                <p class="description" id="wrap-edit-menu-item-panel_column-<?php echo esc_attr($item_id) ?>" style="<?php echo !($item->use_megamenu && $item->use_megamenu == 1) ? 'display:none;' : '' ?>">
                    <label for="edit-menu-item-panel_column-<?php echo esc_attr($item_id); ?>">
                        <?php echo esc_html__('Display number of panel columns', 'efarm'); ?>
                        <select id="edit-menu-item-panel_column-<?php echo esc_attr($item_id); ?>" class="edit-menu-item-panel_column"
                            <?php if (esc_attr( $item->panel_column )) : ?>
                                name="menu-item-panel_column[<?php echo esc_attr($item_id); ?>]"
                            <?php endif; ?>
                                data-name="menu-item-panel_column[<?php echo esc_attr($item_id); ?>]">
                            <?php foreach($panel_columns as $key => $_val): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php echo ($item->panel_column && $item->panel_column == $key) ? 'selected' : '' ?>><?php echo esc_attr($_val) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </p>
                <p class="description" style="<?php echo !($item->use_megamenu && $item->use_megamenu == 1) ? 'display:none;' : 'display:block;' ?>">
                    <label for="edit-menu-item-block1-<?php echo esc_attr($item_id); ?>">
                        <?php echo esc_html__('Block 1 Name','efarm'); ?><br />
                        <input type="text" id="edit-menu-item-poup_block1-<?php echo esc_attr($item_id); ?>" class="widefat edit-menu-item-block1"
                            <?php if (esc_attr( $item->block1 )) : ?>
                                name="menu-item-block1[<?php echo esc_attr($item_id); ?>]"
                            <?php endif; ?>
                               data-name="menu-item-block1[<?php echo esc_attr($item_id); ?>]"
                               value="<?php echo esc_attr( $item->block1 ); ?>"/>
                    </label>
                </p>
                <p class="description" style="<?php echo !($item->use_megamenu && $item->use_megamenu == 1) ? 'display:none;' : 'display:block;' ?>">
                    <label for="edit-menu-item-block2-<?php echo esc_attr($item_id); ?>">
                        <?php echo esc_html__('Block 2 Name ','efarm'); ?><br />
                        <input type="text" id="edit-menu-item-poup_block2-<?php echo esc_attr($item_id); ?>" class="widefat edit-menu-item-block2"
                            <?php if (esc_attr( $item->block2 )) : ?>
                                name="menu-item-block2[<?php echo esc_attr($item_id); ?>]"
                            <?php endif; ?>
                               data-name="menu-item-block2[<?php echo esc_attr($item_id); ?>]"
                               value="<?php echo esc_attr( $item->block2 ); ?>"/>
                    </label>
                </p>
            </div>
            <?php
            $parent_use_megamenu = 0;
            if($depth == 1) {
                if($item->menu_item_parent) {
                    $parent_item = get_post_meta($item->menu_item_parent, '_menu_item_use_megamenu', true);
                    $parent_use_megamenu = $parent_item ? $parent_item : 0;
                }
            }
            ?>
            <div class="wrap-custom-options-level1-<?php echo esc_attr($item_id); ?>" style="<?php echo esc_attr($depth == 1 ? 'display:block;' : 'display:none;') ?>">
                <p class="description wrap-edit-menu-item-mega_item_column" id="wrap-edit-menu-item-mega_item_column-<?php echo esc_attr($item_id) ?>" style="<?php echo !($parent_use_megamenu) ? 'display:none;' : '' ?>">
                    <label for="edit-menu-item-mega_item_column-<?php echo esc_attr($item_id); ?>">
                        <?php echo esc_html__('Item Columns(depend on parent panel columns)', 'efarm'); ?><br>
                        <input type="text" id="edit-menu-item-mega_item_column-<?php echo esc_attr($item_id); ?>" class="edit-menu-item-mega_item_column"
                            <?php if (esc_attr( $item->mega_item_column )) : ?>
                                name="menu-item-mega_item_column[<?php echo esc_attr($item_id); ?>]"
                            <?php endif; ?>
                               data-name="menu-item-mega_item_column[<?php echo esc_attr($item_id); ?>]"
                               value="<?php echo esc_attr( $item->mega_item_column ) ? esc_attr( $item->mega_item_column ) : 1 ?>" />
                    </label>
                </p>
            </div>
            <?php
            /*
             * end custom options
             */
            ?>

            <p class="field-move hide-if-no-js description description-wide">
                <label>
                    <span><?php echo esc_html__( 'Move', 'efarm' ); ?></span>
                    <a href="#" class="menus-move menus-move-up" data-dir="up"><?php echo esc_html__( 'Up one', 'efarm' ); ?></a>
                    <a href="#" class="menus-move menus-move-down" data-dir="down"><?php echo esc_html__( 'Down one', 'efarm' ); ?></a>
                    <a href="#" class="menus-move menus-move-left" data-dir="left"></a>
                    <a href="#" class="menus-move menus-move-right" data-dir="right"></a>
                    <a href="#" class="menus-move menus-move-top" data-dir="top"><?php echo esc_html__( 'To the top', 'efarm' ); ?></a>
                </label>
            </p>

            <div class="menu-item-actions description-wide submitbox">
                <?php if ( 'custom' != $item->type && $original_title !== false ) : ?>
                    <p class="link-to-original">
                        <?php printf( esc_html__('Original: %s', 'efarm'), '<a href="' . esc_attr( $item->url ) . '">' . esc_html( $original_title ) . '</a>' ); ?>
                    </p>
                <?php endif; ?>
                <a class="item-delete submitdelete deletion" id="delete-<?php echo esc_attr($item_id); ?>" href="<?php
                echo wp_nonce_url(
                    add_query_arg(
                        array(
                            'action' => 'delete-menu-item',
                            'menu-item' => $item_id,
                        ),
                        admin_url( 'nav-menus.php' )
                    ),
                    'delete-menu_item_' . $item_id
                ); ?>"><?php echo esc_html__( 'Remove', 'efarm' ); ?></a> <span class="meta-sep hide-if-no-js"> | </span> <a class="item-cancel submitcancel hide-if-no-js" id="cancel-<?php echo esc_attr($item_id); ?>" href="<?php echo esc_url( add_query_arg( array( 'edit-menu-item' => $item_id, 'cancel' => time() ), admin_url( 'nav-menus.php' ) ) );
                ?>#menu-item-settings-<?php echo esc_attr($item_id); ?>"><?php echo esc_html__('Cancel', 'efarm'); ?></a>
            </div>

            <input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($item_id); ?>" />
            <input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->object_id ); ?>" />
            <input class="menu-item-data-object" type="hidden" name="menu-item-object[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->object ); ?>" />
            <input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->menu_item_parent ); ?>" />
            <input class="menu-item-data-position" type="hidden" name="menu-item-position[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->menu_order ); ?>" />
            <input class="menu-item-data-type" type="hidden" name="menu-item-type[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr( $item->type ); ?>" />
        </div><!-- .menu-item-settings-->
        <ul class="menu-item-transport"></ul>
        <?php
        $output .= ob_get_clean();
    }
}
