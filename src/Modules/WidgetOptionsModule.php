<?php

namespace Stackadroit\Topstockbroker\Modules;

use DOMDocument;
use Stackadroit\Topstockbroker\DOM;

use function add_action;
use function add_filter;
use function __;

/**
 *
 */
class WidgetOptionsModule extends AbstractModule
{
    /**
     * Name of the module.
     *
     * @var string
     */
    protected $name = 'widget-options';

    /**
     * Default options.
     *
     * @var array
     */
    protected $defaults = [
        /**
         * Enable this module.
         *
         * @var bool
         */
        'enabled' => true,
    ];

    /**
     * Module handle.
     *
     * @return void
     */
    public function handle()
    {
        add_action('init', array( $this, 'init' ));
    }

    /**
     *
     */
    public function init()
    {
        add_filter('in_widget_form', array( $this, 'add_widget_option' ), 10, 3);
        add_filter('widget_update_callback', array( $this, 'update_widget_option' ), 10, 3);

        add_filter('dynamic_sidebar_params', array( $this, 'add_widget_classes' ));
    }

    /**
     *
     */
    public static function add_widget_classes( $params ) {
        global $wp_registered_widgets;

        if ( ! isset( $params[0] ) ) {
            return $params;
        }

        $arr_registered_widgets = wp_get_sidebars_widgets(); // Get an array of ALL registered widgets
        $this_id                = $params[0]['id']; // Get the id for the current sidebar we're processing
        $widget_id              = $params[0]['widget_id'];
        $widget_obj             = $wp_registered_widgets[ $widget_id ];

        // Skip old single widget (not using WP_Widget).
        if ( ! isset( $widget_obj['params'][0]['number'] ) ) {
            return $params;
        }

        $widget_num = $widget_obj['params'][0]['number'];
        $widget_opt = get_option($widget_obj['callback'][0]->option_name);

        if ( isset($widget_opt[$widget_num]['fixed_widget']) && !empty($widget_opt[$widget_num]['fixed_widget']) ){
            $params[0]['before_widget'] = preg_replace( '/class="/', "class=\"fixed-widget ", $params[0]['before_widget'], 1 );
        }

       return $params;
    }

    /**
     *
     */
    public static function add_widget_option($widget, $return, $instance) {  
    
        if ( isset($instance['fixed_widget']) ) $iqfw = $instance['fixed_widget']; else $iqfw = 0;
        
        echo '<p>'.PHP_EOL;
        
        echo '<input type="checkbox" name="'. $widget->get_field_name('fixed_widget') .'" value="1" '. checked( $iqfw, 1, false ) .'/>'.PHP_EOL;
        
        echo '<label for="'. $widget->get_field_id('fixed_widget') .'">'. __('Fixed widget', 'top10stockbroker-extensions') .'</label>'.PHP_EOL;
    
        echo '</p>'.PHP_EOL;    

    }

    /**
     *
     */
    public static function update_widget_option($instance, $new_instance, $old_instance){
    
        if ( isset($new_instance['fixed_widget']) && $new_instance['fixed_widget'] ) {
            
            $instance['fixed_widget'] = 1;
    
        } else {
        
            $instance['fixed_widget'] = false;
        
        }
    
        return $instance;

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
