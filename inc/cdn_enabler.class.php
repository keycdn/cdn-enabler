<?php

/**
 * CDN_Enabler
 *
 * @since 0.0.1
 */

class CDN_Enabler
{


    /**
     * pseudo-constructor
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function instance() {
        new self();
    }


    /**
     * constructor
     *
     * @since   0.0.1
     * @change  1.0.4
     */

    public function __construct() {
        /* CDN rewriter hook */
        add_action(
            'template_redirect',
            [
                __CLASS__,
                'handle_rewrite_hook',
            ]
        );

        /* Hooks */
        add_action(
            'admin_init',
            [
                __CLASS__,
                'register_textdomain',
            ]
        );
        add_action(
            'admin_init',
            [
                'CDN_Enabler_Settings',
                'register_settings',
            ]
        );
        add_action(
            'admin_menu',
            [
                'CDN_Enabler_Settings',
                'add_settings_page',
            ]
        );
        add_filter(
            'plugin_action_links_' .CDN_ENABLER_BASE,
            [
                __CLASS__,
                'add_action_link',
            ]
        );

        /* admin notices */
        add_action(
            'all_admin_notices',
            [
                __CLASS__,
                'cdn_enabler_requirements_check',
            ]
        );

        /* add admin purge link */
        add_action(
            'admin_bar_menu',
            [
                __CLASS__,
                'add_admin_links',
            ],
            90
        );
        /* process purge request */
        add_action(
            'init',
            [
                __CLASS__,
                'process_purge_request',
            ]
        );
    }


    /**
     * add Zone purge link
     *
     * @since   1.0.5
     * @change  1.0.6
     *
     * @hook    mixed
     *
     * @param   object  menu properties
     */

    public static function add_admin_links($wp_admin_bar) {
        $options = self::get_options();

        // check user role
        if ( ! is_admin_bar_showing() or ! apply_filters('user_can_clear_cache', current_user_can('manage_options')) ) {
            return;
        }

        // verify Zone settings are set
        if ( ! is_int($options['keycdn_zone_id'])
                or $options['keycdn_zone_id'] <= 0 ) {
            return;
        }
        if ( ! array_key_exists('keycdn_api_key', $options)
                or strlen($options['keycdn_api_key']) < 20 ) {
            return;
        }

        // add admin purge link
        $wp_admin_bar->add_menu(
            [
                'id'      => 'purge-cdn',
                'href'   => wp_nonce_url( add_query_arg('_cdn', 'purge'), '_cdn__purge_nonce'),
                'parent' => 'top-secondary',
                'title'     => '<span class="ab-item">'.esc_html__('Purge CDN', 'cdn-enabler').'</span>',
                'meta'   => ['title' => esc_html__('Purge CDN', 'cdn-enabler')],
            ]
        );

        if ( ! is_admin() ) {
            // add admin purge link
            $wp_admin_bar->add_menu(
                [
                    'id'      => 'purge-cdn',
                    'href'   => wp_nonce_url( add_query_arg('_cdn', 'purge'), '_cdn__purge_nonce'),
                    'parent' => 'top-secondary',
                    'title'     => '<span class="ab-item">'.esc_html__('Purge CDN', 'cdn-enabler').'</span>',
                    'meta'   => ['title' => esc_html__('Purge CDN', 'cdn-enabler')],
                ]
            );
        }
    }


    /**
     * process purge request
     *
     * @since   1.0.5
     * @change  1.0.6
     *
     * @param   array  $data  array of metadata
     */
    public static function process_purge_request($data) {
        $options = self::get_options();

        // check if clear request
        if ( empty($_GET['_cdn']) OR $_GET['_cdn'] !== 'purge' ) {
            return;
        }

        // validate nonce
        if ( empty($_GET['_wpnonce']) OR ! wp_verify_nonce($_GET['_wpnonce'], '_cdn__purge_nonce') ) {
            return;
        }

        // check user role
        if ( ! is_admin_bar_showing() ) {
            return;
        }

        // load if network
        if ( ! function_exists('is_plugin_active_for_network') ) {
            require_once( ABSPATH. 'wp-admin/includes/plugin.php' );
        }

        // API call to purge zone
        $response = wp_remote_get( 'https://api.keycdn.com/zones/purge/'. $options['keycdn_zone_id'] .'.json',
            [
                'timeout' => 20,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $options['keycdn_api_key'] . ':' ),
                ]
            ]
        );

        // check results - error connecting
        if ( is_wp_error( $response ) ) {
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html__('Error connecting to API - '. $response->get_error_message(), 'cdn-enabler')
            );

            return;
        }

        // check HTTP response
        if ( is_array( $response ) and is_admin_bar_showing()) {
            $json = json_decode($response['body'], true);

            // success
            if ( wp_remote_retrieve_response_code( $response ) == 200
                    and is_array($json)
                    and array_key_exists('description', $json) )
            {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html__($json['description'], 'cdn-enabler')
                );

                return;
            } elseif ( wp_remote_retrieve_response_code( $response ) == 200 ) {
                // return code 200 but no message
                printf(
                    '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                    esc_html__('HTTP returned 200 but no message received.')
                );

                return;
            }

            // API call returned != 200 and also a status message
            if ( is_array($json)
                    and array_key_exists('status', $json)
                    and $json['status'] != "" ) {
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html__('HTTP returned '. wp_remote_retrieve_response_code( $response ) .': '.$json['description'], 'cdn-enabler')
                );
            } else {
                // Something else went wrong - show HTTP error code
                printf(
                    '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    esc_html__('HTTP returned '. wp_remote_retrieve_response_code( $response ))
                );
            }
        }


        if ( ! is_admin() ) {
            wp_safe_redirect(
                remove_query_arg(
                    '_cache',
                    wp_get_referer()
                )
            );

            exit();
        }
    }



    /**
     * add action links
     *
     * @since   0.0.1
     * @change  0.0.1
     *
     * @param   array  $data  alreay existing links
     * @return  array  $data  extended array with links
     */

    public static function add_action_link($data) {
        // check permission
        if ( ! current_user_can('manage_options') ) {
            return $data;
        }

        return array_merge(
            $data,
            [
                sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        [
                            'page' => 'cdn_enabler',
                        ],
                        admin_url('options-general.php')
                    ),
                    __("Settings")
                ),
            ]
        );
    }


    /**
     * run uninstall hook
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function handle_uninstall_hook() {
        delete_option('cdn_enabler');
    }


    /**
     * run activation hook
     *
     * @since   0.0.1
     * @change  1.0.5
     */

    public static function handle_activation_hook() {
        add_option(
            'cdn_enabler',
            [
                'url'            => get_option('home'),
                'dirs'           => 'wp-content,wp-includes',
                'excludes'       => '.php',
                'relative'       => '1',
                'https'          => '',
                'keycdn_api_key' => '',
                'keycdn_zone_id' => '',
            ]
        );
    }


    /**
     * check plugin requirements
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function cdn_enabler_requirements_check() {
        // WordPress version check
        if ( version_compare($GLOBALS['wp_version'], CDN_ENABLER_MIN_WP.'alpha', '<') ) {
            show_message(
                sprintf(
                    '<div class="error"><p>%s</p></div>',
                    sprintf(
                        __("CDN Enabler is optimized for WordPress %s. Please disable the plugin or upgrade your WordPress installation (recommended).", "cdn-enabler"),
                        CDN_ENABLER_MIN_WP
                    )
                )
            );
        }
    }


    /**
     * register textdomain
     *
     * @since   1.0.3
     * @change  1.0.3
     */

    public static function register_textdomain() {
        load_plugin_textdomain(
            'cdn-enabler',
            false,
            'cdn-enabler/lang'
        );
    }


    /**
     * return plugin options
     *
     * @since   0.0.1
     * @change  1.0.5
     *
     * @return  array  $diff  data pairs
     */

    public static function get_options() {
        return wp_parse_args(
            get_option('cdn_enabler'),
            [
                'url'             => get_option('home'),
                'dirs'            => 'wp-content,wp-includes',
                'excludes'        => '.php',
                'relative'        => 1,
                'https'           => 0,
                'keycdn_api_key'  => '',
                'keycdn_zone_id'  => '',
            ]
        );
    }


    /**
     * run rewrite hook
     *
     * @since   0.0.1
     * @change  1.0.5
     */

    public static function handle_rewrite_hook() {
        $options = self::get_options();

        // check if origin equals cdn url
        if (get_option('home') == $options['url']) {
            return;
        }

        $excludes = array_map('trim', explode(',', $options['excludes']));

        $rewriter = new CDN_Enabler_Rewriter(
            get_option('home'),
            $options['url'],
            $options['dirs'],
            $excludes,
            $options['relative'],
            $options['https'],
            $options['keycdn_api_key'],
            $options['keycdn_zone_id']
        );
        ob_start(array(&$rewriter, 'rewrite'));
    }

}
