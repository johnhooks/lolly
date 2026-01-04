<?php

declare(strict_types=1);

/*
 * Plugin Name: Lolly Log
 * Plugin URI: http://lolly.okaywp.com/
 * Description: A logging plugin.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: bitmachina
 * Author URI: https://github.com/johnhooks
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lolly
 * Domain Path: /languages
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @package Lolly
 */

use Lolly\Lolly;
use Lolly\Plugin\Activator;
use Lolly\Plugin\Uninstaller;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/vendor-prefixed/autoload.php';

/**
 * Start Lolly
 *
 * The main function responsible for returning the one true Lolly instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @template Abstract
 *
 * @param class-string<Abstract>|null $abstract Selector for data to retrieve from the service container.
 *
 * @return ( $abstract is null ? Lolly : Abstract )
 *
 * @package Lolly
 * @since 0.1.0
 */
function lolly( ?string $abstract = null ): mixed {
    static $instance = null;

    if ( $instance === null ) {
        $instance = new Lolly();
    }

    if ( $abstract !== null ) {
        return $instance->make( $abstract );
    }

    return $instance;
}

lolly()->boot( __FILE__ );

register_activation_hook( __FILE__, Activator::callback() );
register_uninstall_hook( __FILE__, [ Uninstaller::class, 'uninstall' ] );
