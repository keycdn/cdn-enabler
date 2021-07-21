<?php
/*
Plugin Name: CDN Enabler
Text Domain: cdn-enabler
Description: Simple and fast WordPress content delivery network (CDN) integration plugin.
Author: KeyCDN
Author URI: https://www.keycdn.com
License: GPLv2 or later
Version: 2.0.4
*/

/*
Copyright (C) 2021 KeyCDN

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// constants
define( 'CDN_ENABLER_VERSION', '2.0.4' );
define( 'CDN_ENABLER_MIN_PHP', '5.6' );
define( 'CDN_ENABLER_MIN_WP', '5.1' );
define( 'CDN_ENABLER_FILE', __FILE__ );
define( 'CDN_ENABLER_BASE', plugin_basename( __FILE__ ) );
define( 'CDN_ENABLER_DIR', __DIR__ );

// hooks
add_action( 'plugins_loaded', array( 'CDN_Enabler', 'init' ) );
register_activation_hook( __FILE__, array( 'CDN_Enabler', 'on_activation' ) );
register_uninstall_hook( __FILE__, array( 'CDN_Enabler', 'on_uninstall' ) );

// register autoload
spl_autoload_register( 'cdn_enabler_autoload' );

// load required classes
function cdn_enabler_autoload( $class_name ) {
    if ( in_array( $class_name, array( 'CDN_Enabler', 'CDN_Enabler_Engine' ) ) ) {
        require_once sprintf(
            '%s/inc/%s.class.php',
            CDN_ENABLER_DIR,
            strtolower( $class_name )
        );
    }
}

// load WP-CLI command
if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
    require_once CDN_ENABLER_DIR . '/inc/cdn_enabler_cli.class.php';
}
