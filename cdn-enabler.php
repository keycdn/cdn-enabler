<?php
/*
   Plugin Name: CDN Enabler
   Text Domain: cdn-enabler
   Description: Simply integrate a Content Delivery Network (CDN) into your WordPress site.
   Author: KeyCDN
   Author URI: https://www.keycdn.com
   License: GPLv2 or later
   Version: 1.0.5
 */

/*
   Copyright (C)  2017 KeyCDN

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

/* Check & Quit */
defined('ABSPATH') OR exit;


/* constants */
define('CDN_ENABLER_FILE', __FILE__);
define('CDN_ENABLER_DIR', dirname(__FILE__));
define('CDN_ENABLER_BASE', plugin_basename(__FILE__));
define('CDN_ENABLER_MIN_WP', '3.8');


/* loader */
add_action(
    'plugins_loaded',
    [
        'CDN_Enabler',
        'instance',
    ]
);


/* uninstall */
register_uninstall_hook(
    __FILE__,
    [
        'CDN_Enabler',
        'handle_uninstall_hook',
    ]
);


/* activation */
register_activation_hook(
    __FILE__,
    [
        'CDN_Enabler',
        'handle_activation_hook',
    ]
);


/* autoload init */
spl_autoload_register('CDN_ENABLER_autoload');

/* autoload funktion */
function CDN_ENABLER_autoload($class) {
    if ( in_array($class, ['CDN_Enabler', 'CDN_Enabler_Rewriter', 'CDN_Enabler_Settings']) ) {
        require_once(
            sprintf(
                '%s/inc/%s.class.php',
                CDN_ENABLER_DIR,
                strtolower($class)
            )
        );
    }
}
