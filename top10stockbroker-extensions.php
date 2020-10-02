<?php
/**
 * Plugin Name:    	Top10StockBroker Extensions
 * Plugin URI:     	https://top10stockbroker.com/
 * Description:    	A collection of modules to apply theme-agnostic front-end modifications to WordPress.
 * Author:         	Praneeth Polu
 * Author URL:     	http://polupraneeth.me/
 * Version:        	1.0.0
 * Text Domain: 	top10stockbroker-ext
 * Domain Path: 	/languages
 *
 * License:            MIT License
 * License URI:        https://opensource.org/licenses/MIT
 * WC tested up to: 4.4.1
 */

namespace Stackadroit\Topstockbroker;

/**
 * Current ThemeMove Core version
 */
if ( ! defined( 'TOP10STOCKBROKER_EXTENSIONS_VERSION' ) ) {
    define( 'TOP10STOCKBROKER_EXTENSIONS_VERSION', '1.0.0' );
}

$theme = wp_get_theme();
if ( ! empty( $theme['Template'] ) ) {
    $theme = wp_get_theme( $theme['Template'] );
}
define( 'TOP10STOCKBROKER_EXTENSIONS_NAME', $theme['Name'] );
define( 'TOP10STOCKBROKER_EXTENSIONS_SLUG', $theme['Template'] );
define( 'TOP10STOCKBROKER_EXTENSIONS_THEME_VERSION', $theme['Version'] );
define( 'TOP10STOCKBROKER_EXTENSIONS_SITE_URI', site_url() );

add_action('plugins_loaded', function () {
    if (!class_exists(Topstockbroker::class)) {
        require_once file_exists($autoloader = __DIR__ . '/vendor/autoload.php')
            ? $autoloader
            : __DIR__ . '/src/autoload.php';
    }

    $modules = Topstockbroker::discoverModules();

    add_action('after_setup_theme', new Topstockbroker($modules), 100);
});