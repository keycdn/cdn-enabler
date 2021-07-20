<?php
/**
 * CDN Enabler base
 *
 * @since  0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CDN_Enabler {

    /**
     * initialize plugin
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public static function init() {

        new self();
    }


    /**
     * constructor
     *
     * @since   0.0.1
     * @change  2.0.0
     */

    public function __construct() {

        // engine hook
        add_action( 'setup_theme', array( 'CDN_Enabler_Engine', 'start' ) );

        // init hooks
        add_action( 'init', array( __CLASS__, 'process_purge_cache_request' ) );
        add_action( 'init', array( __CLASS__, 'register_textdomain' ) );

        // multisite hook
        add_action( 'wp_initialize_site', array( __CLASS__, 'install_later' ) );

        // admin bar hook
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_items' ), 90 );

        // admin interface hooks
        if ( is_admin() ) {
            // settings
            add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
            add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_admin_resources' ) );
            // dashboard
            add_filter( 'plugin_action_links_' . CDN_ENABLER_BASE, array( __CLASS__, 'add_plugin_action_links' ) );
            add_filter( 'plugin_row_meta', array( __CLASS__, 'add_plugin_row_meta' ), 10, 2 );
            // notices
            add_action( 'admin_notices', array( __CLASS__, 'requirements_check' ) );
            add_action( 'admin_notices', array( __CLASS__, 'cache_purged_notice' ) );
            add_action( 'admin_notices', array( __CLASS__, 'config_validated_notice' ) );
        }
    }


    /**
     * activation hook
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   boolean  $network_wide  network activated
     */

    public static function on_activation( $network_wide ) {

        // add backend requirements
        self::each_site( $network_wide, 'self::update_backend' );
    }


    /**
     * uninstall hook
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public static function on_uninstall() {

        // uninstall backend requirements
        self::each_site( is_multisite(), 'self::uninstall_backend' );
    }


    /**
     * install on new site in multisite network
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   WP_Site  $new_site  new site instance
     */

    public static function install_later( $new_site ) {

        // check if network activated
        if ( ! is_plugin_active_for_network( CDN_ENABLER_BASE ) ) {
            return;
        }

        // switch to new site
        switch_to_blog( (int) $new_site->blog_id );

        // add backend requirements
        self::update_backend();

        // restore current blog from before new site
        restore_current_blog();
    }


    /**
     * add or update backend requirements
     *
     * @since   2.0.0
     * @change  2.0.4
     *
     * @return  array  $new_option_value  new or current database option value
     */

    public static function update_backend() {

        // get defined settings, fall back to empty array if not found
        $old_option_value = get_option( 'cdn_enabler', array() );

        // maybe convert old settings to new settings
        $new_option_value = self::convert_settings( $old_option_value );

        // update default system settings
        $new_option_value = wp_parse_args( self::get_default_settings( 'system' ), $new_option_value );

        // merge defined settings into default settings
        $new_option_value = wp_parse_args( $new_option_value, self::get_default_settings() );

        // validate settings
        $new_option_value = self::validate_settings( $new_option_value );

        // add or update database option
        update_option( 'cdn_enabler', $new_option_value );

        return $new_option_value;
    }


    /**
     * uninstall backend requirements
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    private static function uninstall_backend() {

        // delete database option
        delete_option( 'cdn_enabler' );
    }


    /**
     * enter each site
     *
     * @since   2.0.0
     * @change  2.0.4
     *
     * @param   boolean  $network          whether or not each site in network
     * @param   string   $callback         callback function
     * @param   array    $callback_params  callback function parameters
     * @return  array    $callback_return  returned value(s) from callback function
     */

    private static function each_site( $network, $callback, $callback_params = array() ) {

        $callback_return = array();

        if ( $network ) {
            $blog_ids = self::get_blog_ids();

            // switch to each site in network
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                $callback_return[ $blog_id ] = call_user_func_array( $callback, $callback_params );
                restore_current_blog();
            }
        } else {
            $blog_id = 1;
            $callback_return[ $blog_id ] = call_user_func_array( $callback, $callback_params );
        }

        return $callback_return;
    }


    /**
     * get settings from database
     *
     * @since       0.0.1
     * @deprecated  2.0.0
     */

    public static function get_options() {

        return self::get_settings();
    }


    /**
     * get settings from database
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @return  array  $settings  current settings from database
     */

    public static function get_settings() {

        // get database option value
        $settings = get_option( 'cdn_enabler' );

        // if database option does not exist or settings are outdated
        if ( $settings === false || ! isset( $settings['version'] ) || $settings['version'] !== CDN_ENABLER_VERSION ) {
            $settings = self::update_backend();
        }

        return $settings;
    }


    /**
     * get blog IDs
     *
     * @since   2.0.0
     * @change  2.0.4
     *
     * @return  array  $blog_ids  blog IDs
     */

    private static function get_blog_ids() {

        $blog_ids = array( 1 );

        if ( is_multisite() ) {
            global $wpdb;

            $blog_ids = array_map( 'absint', $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) );
        }

        return $blog_ids;
    }


    /**
     * get the cache purged transient name used for the purge notice
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @return  string  $transient_name  transient name
     */

    private static function get_cache_purged_transient_name() {

        $transient_name = 'cdn_enabler_cache_purged_' . get_current_user_id();

        return $transient_name;
    }


    /**
     * get the configuration validated transient name used for the validation notice
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @return  string  $transient_name  transient name
     */

    private static function get_config_validated_transient_name() {

        $transient_name = 'cdn_enabler_config_validated_' . get_current_user_id();

        return $transient_name;
    }


    /**
     * get default settings
     *
     * @since   2.0.0
     * @change  2.0.3
     *
     * @param   string  $settings_type                              default `system` settings
     * @return  array   $system_default_settings|$default_settings  only default system settings or all default settings
     */

    private static function get_default_settings( $settings_type = null ) {

        $system_default_settings = array( 'version' => (string) CDN_ENABLER_VERSION );

        if ( $settings_type === 'system' ) {
            return $system_default_settings;
        }

        $user_default_settings = array(
            'cdn_hostname'             => '',
            'included_file_extensions' => implode( PHP_EOL, array(
                                              '.avif',
                                              '.css',
                                              '.gif',
                                              '.jpeg',
                                              '.jpg',
                                              '.js',
                                              '.json',
                                              '.mp3',
                                              '.mp4',
                                              '.pdf',
                                              '.png',
                                              '.svg',
                                              '.webp',
                                          ) ),
            'excluded_strings'         => '',
            'keycdn_api_key'           => '',
            'keycdn_zone_id'           => '',
        );

        // merge default settings
        $default_settings = wp_parse_args( $user_default_settings, $system_default_settings );

        return $default_settings;
    }


    /**
     * convert settings to new structure
     *
     * @since   2.0.0
     * @change  2.0.1
     *
     * @param   array  $settings  settings
     * @return  array  $settings  converted settings if applicable, unchanged otherwise
     */

    private static function convert_settings( $settings ) {

        // check if there are any settings to convert
        if ( empty( $settings ) ) {
            return $settings;
        }

        // updated settings
        if ( isset( $settings['url'] ) && is_string( $settings['url'] ) && substr_count( $settings['url'], '/' ) > 2 ) {
            $settings['url'] = '';
        }

        // reformatted settings
        if ( isset( $settings['excludes'] ) && is_string( $settings['excludes'] ) ) {
            $settings['excludes'] = str_replace( ',', PHP_EOL, $settings['excludes'] );
            $settings['excludes'] = str_replace( '.php', '', $settings['excludes'] );
        }

        // renamed or removed settings
        $settings_names = array(
            // 2.0.0
            'url'      => 'cdn_hostname',
            'dirs'     => '', // deprecated
            'excludes' => 'excluded_strings',
            'relative' => '', // deprecated
            'https'    => '', // deprecated
        );

        foreach ( $settings_names as $old_name => $new_name ) {
            if ( array_key_exists( $old_name, $settings ) ) {
                if ( ! empty( $new_name ) ) {
                    $settings[ $new_name ] = $settings[ $old_name ];
                }

                unset( $settings[ $old_name ] );
            }
        }

        return $settings;
    }


    /**
     * add plugin action links in the plugins list table
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   array  $action_links  action links
     * @return  array  $action_links  updated action links if applicable, unchanged otherwise
     */

    public static function add_plugin_action_links( $action_links ) {

        // check user role
        if ( ! current_user_can( 'manage_options' ) ) {
            return $action_links;
        }

        // prepend action link
        array_unshift( $action_links, sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'options-general.php?page=cdn-enabler' ),
            esc_html__( 'Settings', 'cdn-enabler' )
        ) );

        return $action_links;
    }


    /**
     * add plugin metadata in the plugins list table
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   array   $plugin_meta  plugin metadata, including the version, author, author URI, and plugin URI
     * @param   string  $plugin_file  path to the plugin file relative to the plugins directory
     * @return  array   $plugin_meta  updated action links if applicable, unchanged otherwise
     */

    public static function add_plugin_row_meta( $plugin_meta, $plugin_file ) {

        // check if CDN Enabler row
        if ( $plugin_file !== CDN_ENABLER_BASE ) {
            return $plugin_meta;
        }

        // append metadata
        $plugin_meta = wp_parse_args(
            array(
                '<a href="https://www.keycdn.com/support/wordpress-cdn-enabler-plugin" target="_blank" rel="nofollow noopener">Documentation</a>',
            ),
            $plugin_meta
        );

        return $plugin_meta;
    }


    /**
     * add admin bar items
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   object  $wp_admin_bar  menu properties
     */

    public static function add_admin_bar_items( $wp_admin_bar ) {

        // check user role
        if ( ! self::user_can_purge_cache() ) {
            return;
        }

        // check if KeyCDN API key is set
        if ( strlen( CDN_Enabler_Engine::$settings['keycdn_api_key'] ) < 20 ) {
            return;
        }

        // check if KeyCDN Zone ID is set
        if ( ! is_int( CDN_Enabler_Engine::$settings['keycdn_zone_id'] ) ) {
            return;
        }

        // add admin purge link
        if ( ! is_network_admin() ) {
            $wp_admin_bar->add_menu(
                array(
                    'id'     => 'cdn-enabler-purge-cache',
                    'href'   => wp_nonce_url( add_query_arg( array(
                                    '_cache' => 'cdn',
                                    '_action' => 'purge',
                                ) ), 'cdn_enabler_purge_cache_nonce' ),
                    'parent' => 'top-secondary',
                    'title'  => '<span class="ab-item">' . esc_html__( 'Purge CDN Cache', 'cdn-enabler' ) . '</span>',
                    'meta'   => array( 'title' => esc_html__('Purge CDN Cache', 'cdn-enabler') ),
                )
            );
        }
    }


    /**
     * enqueue styles and scripts
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public static function add_admin_resources( $hook ) {

        // settings page
        if ( $hook === 'settings_page_cdn-enabler' ) {
            wp_enqueue_style( 'cdn-enabler-settings', plugins_url( 'css/settings.min.css', CDN_ENABLER_FILE ), array(), CDN_ENABLER_VERSION );
        }
    }


    /**
     * add settings page
     *
     * @since   0.0.1
     * @change  2.0.0
     */

    public static function add_settings_page() {

        add_options_page(
            'CDN Enabler',
            'CDN Enabler',
            'manage_options',
            'cdn-enabler',
            array( __CLASS__, 'settings_page' )
        );
    }


    /**
     * check if user can purge cache
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @return  boolean  true if user can purge cache, false otherwise
     */

    private static function user_can_purge_cache() {

        if ( apply_filters( 'cdn_enabler_user_can_purge_cache', current_user_can( 'manage_options' ) ) ) {
            return true;
        }

        if ( apply_filters_deprecated( 'user_can_clear_cache', array( current_user_can( 'manage_options' ) ), '2.0.0', 'cdn_enabler_user_can_purge_cache' ) ) {
            return true;
        }

        return false;
    }


    /**
     * process purge cache request
     *
     * @since   2.0.0
     * @change  2.0.3
     */

    public static function process_purge_cache_request() {

        // check if purge cache request
        if ( empty( $_GET['_cache'] ) || empty( $_GET['_action'] ) || $_GET['_cache'] !== 'cdn' || ( $_GET['_action'] !== 'purge' ) ) {
            return;
        }

        // validate nonce
        if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'cdn_enabler_purge_cache_nonce' ) ) {
            return;
        }

        // check user role
        if ( ! self::user_can_purge_cache() ) {
            return;
        }

        // purge CDN cache
        $response = self::purge_cdn_cache();

        // redirect to same page
        wp_safe_redirect( remove_query_arg( array( '_cache', '_action', '_wpnonce' ) ) );

        // set transient for purge notice
        if ( is_admin() ) {
            set_transient( self::get_cache_purged_transient_name(), $response );
        }

        // purge cache request completed
        exit;
    }


    /**
     * admin notice after cache has been purged
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public static function cache_purged_notice() {

        // check user role
        if ( ! self::user_can_purge_cache() ) {
            return;
        }

        $response = get_transient( self::get_cache_purged_transient_name() );

        if ( is_array( $response ) ) {
            if ( ! empty( $response['subject'] ) ) {
                printf(
                    $response['wrapper'],
                    $response['subject'],
                    $response['message']
                );
            } else {
                printf(
                    $response['wrapper'],
                    $response['message']
                );
            }

            delete_transient( self::get_cache_purged_transient_name() );
        }
    }


    /**
     * admin notice after configuration has been validated
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public static function config_validated_notice() {

        // check user role
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $response = get_transient( self::get_config_validated_transient_name() );

        if ( is_array( $response ) ) {
            printf(
                $response['wrapper'],
                $response['subject'],
                $response['message']
            );

            delete_transient( self::get_config_validated_transient_name() );
        }
    }


    /**
     * purge CDN cache
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @return  array  $response  API call response
     */

    public static function purge_cdn_cache() {

        // purge CDN cache API call
        $response = wp_remote_get(
            'https://api.keycdn.com/zones/purge/' . CDN_Enabler_Engine::$settings['keycdn_zone_id'] . '.json',
            array(
                'timeout'     => 15,
                'httpversion' => '1.1',
                'headers'     => array( 'Authorization' => 'Basic ' . base64_encode( CDN_Enabler_Engine::$settings['keycdn_api_key'] . ':' ) ),
            )
        );

        // check if API call failed
        if ( is_wp_error( $response ) ) {
            $response = array(
                'wrapper' => '<div class="notice notice-error is-dismissible"><p><strong>%1$s</strong> %2$s</p></div>',
                'subject' => esc_html__( 'Purge CDN cache failed:', 'cdn-enabler' ),
                'message' => $response->get_error_message(),
            );
        // check API call response otherwise
        } else {
            $response_status_code = wp_remote_retrieve_response_code( $response );

            if ( $response_status_code === 200 ) {
                $response = array(
                    'wrapper' => '<div class="notice notice-success is-dismissible"><p><strong>%s</strong></p></div>',
                    'message' => esc_html__( 'CDN cache purged.', 'cdn-enabler' ),
                );
            } elseif ( $response_status_code >= 400 && $response_status_code <= 499 ) {
                $error_messages = array(
                    401 => esc_html__( 'Invalid API key.', 'cdn-enabler' ),
                    403 => esc_html__( 'Invalid Zone ID.', 'cdn-enabler' ),
                    429 => esc_html__( 'API rate limit exceeded.', 'cdn-enabler' ),
                    451 => esc_html__( 'Too many failed attempts.', 'cdn-enabler' ),
                );

                if ( array_key_exists( $response_status_code, $error_messages ) ) {
                    $message = $error_messages[ $response_status_code ];
                } else {
                    $message = sprintf(
                        // translators: %s: HTTP status code (e.g. 200)
                        esc_html__( '%s status code returned.', 'cdn-enabler' ),
                        '<code>' . $response_status_code . '</code>'
                    );
                }

                $response = array(
                    'wrapper' => '<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
                    'subject' => esc_html__( 'Purge CDN cache failed:', 'cdn-enabler' ),
                    'message' => $message,
                );
            } elseif ( $response_status_code >= 500 && $response_status_code <= 599 ) {
                $response = array(
                    'wrapper' => '<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
                    'subject' => esc_html__( 'Purge CDN cache failed:', 'cdn-enabler' ),
                    'message' => esc_html__( 'API temporarily unavailable.', 'cdn-enabler' ),
                );
            }
        }

        return $response;
    }


    /**
     * check plugin requirements
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public static function requirements_check() {

        // check user role
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // check PHP version
        if ( version_compare( PHP_VERSION, CDN_ENABLER_MIN_PHP, '<' ) ) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    // translators: 1. CDN Enabler 2. PHP version (e.g. 5.6)
                    esc_html__( '%1$s requires PHP %2$s or higher to function properly. Please update PHP or disable the plugin.', 'cdn-enabler' ),
                    '<strong>CDN Enabler</strong>',
                    CDN_ENABLER_MIN_PHP
                )
            );
        }

        // check WordPress version
        if ( version_compare( $GLOBALS['wp_version'], CDN_ENABLER_MIN_WP . 'alpha', '<' ) ) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                sprintf(
                    // translators: 1. CDN Enabler 2. WordPress version (e.g. 5.1)
                    esc_html__( '%1$s requires WordPress %2$s or higher to function properly. Please update WordPress or disable the plugin.', 'cdn-enabler' ),
                    '<strong>CDN Enabler</strong>',
                    CDN_ENABLER_MIN_WP
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

        // load translated strings
        load_plugin_textdomain( 'cdn-enabler', false, 'cdn-enabler/lang' );
    }


    /**
     * register settings
     *
     * @since   0.0.1
     * @change  0.0.1
     */

    public static function register_settings() {

        register_setting( 'cdn_enabler', 'cdn_enabler', array( __CLASS__, 'validate_settings', ) );
    }


    /**
     * validate CDN hostname
     *
     * @since   2.0.0
     * @change  2.0.4
     *
     * @param   string  $cdn_hostname            CDN hostname
     * @return  string  $validated_cdn_hostname  validated CDN hostname
     */

    private static function validate_cdn_hostname( $cdn_hostname ) {

        $cdn_url = esc_url_raw( trim( $cdn_hostname ), array( 'http', 'https' ) );
        $validated_cdn_hostname = strtolower( (string) parse_url( $cdn_url, PHP_URL_HOST ) );

        return $validated_cdn_hostname;
    }


    /**
     * validate textarea
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   string   $textarea            textarea
     * @param   boolean  $file_extension      whether or not textarea requires file extension validation
     * @return  string   $validated_textarea  validated textarea
     */

    private static function validate_textarea( $textarea, $file_extension = false ) {

        $textarea = sanitize_textarea_field( $textarea );
        $lines = explode( PHP_EOL, trim( $textarea ) );
        $validated_textarea = array();

        foreach ( $lines as $line ) {
            $line = trim( $line );

            if ( $line !== '' ) {
                if ( ! $file_extension ) {
                    $validated_textarea[] = $line;
                } elseif ( preg_match( '/^\.\w{1,10}$/', $line ) ) {
                    $validated_textarea[] = $line;
                }
            }
        }

        $validated_textarea = implode( PHP_EOL, $validated_textarea );

        return $validated_textarea;
    }


    /**
     * validate KeyCDN Zone ID
     *
     * @since   2.0.0
     * @change  2.0.4
     *
     * @param   string   $zone_id            KeyCDN Zone ID
     * @return  integer  $validated_zone_id  validated KeyCDN Zone ID
     */

    private static function validate_zone_id( $zone_id ) {

        $zone_id = sanitize_text_field( $zone_id );
        $zone_id = absint( $zone_id );
        $validated_zone_id = ( $zone_id === 0 ) ? '' : $zone_id;

        return $validated_zone_id;
    }


    /**
     * validate configuration
     *
     * @since   2.0.0
     * @change  2.0.4
     *
     * @param   array  $validated_settings  validated settings
     * @return  array  $validated_settings  validated settings
     */

    private static function validate_config( $validated_settings ) {

        if ( empty( $validated_settings['cdn_hostname'] ) ) {
            return $validated_settings;
        }

        // get validation request URL
        CDN_Enabler_Engine::$settings['cdn_hostname'] = $validated_settings['cdn_hostname'];
        CDN_Enabler_Engine::$settings['included_file_extensions'] = '.css';
        $validation_request_url = CDN_Enabler_Engine::rewriter( plugins_url( 'css/settings.min.css', CDN_ENABLER_FILE ) );

        // validation request
        $response = wp_remote_get(
            $validation_request_url,
            array(
                'method'      => 'HEAD',
                'timeout'     => 15,
                'httpversion' => '1.1',
                'headers'     => array( 'Referer' => home_url() ),
            )
        );

        // check if validation failed
        if ( is_wp_error( $response ) ) {
            $response = array(
                'wrapper' => '<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
                'subject' => esc_html__( 'Invalid CDN Hostname:', 'cdn-enabler' ),
                'message' => $response->get_error_message(),
            );
        // check validation response otherwise
        } else {
            $response_status_code = wp_remote_retrieve_response_code( $response );

            if ( $response_status_code === 200 ) {
                $response = array(
                    'wrapper' => '<div class="notice notice-success is-dismissible"><p><strong>%s</strong> %s</p></div>',
                    'subject' => esc_html__( 'Valid CDN Hostname:', 'cdn-enabler' ),
                    'message' => sprintf(
                                     // translators: 1. CDN Hostname (e.g. cdn.example.com) 2. HTTP status code (e.g. 200)
                                     esc_html__( '%1$s returned a %2$s status code.', 'cdn-enabler' ),
                                     '<code>' . $validated_settings['cdn_hostname'] . '</code>',
                                     '<code>' . $response_status_code . '</code>'
                                 ),
                );
            } else {
                $response = array(
                    'wrapper' => '<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
                    'subject' => esc_html__( 'Invalid CDN Hostname:', 'cdn-enabler' ),
                    'message' => sprintf(
                                     // translators: 1. CDN Hostname (e.g. cdn.example.com) 2. HTTP status code (e.g. 200)
                                     esc_html__( '%1$s returned a %2$s status code.', 'cdn-enabler' ),
                                     '<code>' . $validated_settings['cdn_hostname'] . '</code>',
                                     '<code>' . $response_status_code . '</code>'
                                 ),
                );
            }
        }

        // set transient for config validation notice
        set_transient( self::get_config_validated_transient_name(), $response );

        // validate config
        if ( strpos( $response['wrapper'], 'success' ) === false ) {
            $validated_settings['cdn_hostname'] = '';
        }

        return $validated_settings;
    }


    /**
     * validate settings
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   array  $settings            user defined settings
     * @return  array  $validated_settings  validated settings
     */

    public static function validate_settings( $settings ) {

        $validated_settings = array(
            'cdn_hostname'             => self::validate_cdn_hostname( $settings['cdn_hostname'] ),
            'included_file_extensions' => self::validate_textarea( $settings['included_file_extensions'], true ),
            'excluded_strings'         => self::validate_textarea( $settings['excluded_strings'] ),
            'keycdn_api_key'           => (string) sanitize_text_field( $settings['keycdn_api_key'] ),
            'keycdn_zone_id'           => self::validate_zone_id( $settings['keycdn_zone_id'] ),
        );

        // add default system settings
        $validated_settings = wp_parse_args( $validated_settings, self::get_default_settings( 'system' ) );

        // check if configuration should be validated
        if ( ! empty( $settings['validate_config'] ) ) {
            $validated_settings = self::validate_config( $validated_settings );
        }

        return $validated_settings;
    }


    /**
     * settings page
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public static function settings_page() {

        ?>

        <div class="wrap">
            <h1><?php esc_html_e( 'CDN Enabler Settings', 'cdn-enabler' ); ?></h1>

            <?php
            if ( strpos( CDN_Enabler_Engine::$settings['cdn_hostname'], '.kxcdn.com' ) === false && ( strlen( CDN_Enabler_Engine::$settings['keycdn_api_key'] ) < 20 || ! is_int( CDN_Enabler_Engine::$settings['keycdn_zone_id'] ) ) ) {
                printf(
                    '<div class="notice notice-info"><p>%s</p></div>',
                    sprintf(
                        // translators: %s: KeyCDN
                        esc_html__( 'Combine CDN Enabler with %s for even better WordPress performance.', 'cdn-enabler' ),
                        '<strong><a href="https://www.keycdn.com?utm_source=wp-admin&utm_medium=plugins&utm_campaign=cdn-enabler" target="_blank" rel="nofollow noopener">KeyCDN</a></strong>'
                    )
                );
            }
            ?>

            <form method="post" action="options.php">
                <?php settings_fields('cdn_enabler') ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'CDN Hostname', 'cdn-enabler' ); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="cdn_enabler_cdn_hostname">
                                    <input name="cdn_enabler[cdn_hostname]" type="text" id="cdn_enabler_cdn_hostname" value="<?php echo esc_attr( CDN_Enabler_Engine::$settings['cdn_hostname'] ); ?>" class="regular-text code" />
                                </label>
                                <p class="description"><?php esc_html_e( 'URLs will be rewritten using this CDN hostname.', 'cdn-enabler' ) ?></p>
                                <p>
                                    <?php
                                    // translators: 1. cdn.example.com 2. example-1a2b.kxcdn.com
                                    printf(
                                        esc_html__( 'Example: %1$s or %2$s', 'cdn-enabler' ),
                                        '<code class="code--form-control">cdn.example.com</code>',
                                        '<code class="code--form-control">example-1a2b.kxcdn.com</code>'
                                    );
                                    ?>
                                </p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'CDN Inclusions', 'cdn-enabler' ); ?>
                        </th>
                        <td>
                            <fieldset>
                                <p class="subheading"><?php esc_html_e( 'File Extensions', 'cdn-enabler' ); ?></p>
                                <label for="cdn_enabler_included_file_extensions">
                                    <textarea name="cdn_enabler[included_file_extensions]" type="text" id="cdn_enabler_included_file_extensions" class="regular-text code" rows="5" cols="40"><?php echo esc_textarea( CDN_Enabler_Engine::$settings['included_file_extensions'] ) ?></textarea>
                                    <p class="description"><?php esc_html_e( 'Files with these extensions will be served by the CDN. One file extension per line.', 'cdn-enabler' ); ?></p>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'CDN Exclusions', 'cdn-enabler' ); ?>
                        </th>
                        <td>
                            <fieldset>
                                <p class="subheading"><?php esc_html_e( 'Strings', 'cdn-enabler' ); ?></p>
                                <label for="cdn_enabler_excluded_strings">
                                    <textarea name="cdn_enabler[excluded_strings]" type="text" id="cdn_enabler_excluded_strings" class="regular-text code" rows="5" cols="40"><?php echo esc_textarea( CDN_Enabler_Engine::$settings['excluded_strings'] ) ?></textarea>
                                    <p class="description"><?php esc_html_e( 'URLs containing these strings will not be served by the CDN. One string per line.', 'cdn-enabler' ) ?></p>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <h2 class="title">Purge CDN Cache</h2>
                <p><?php esc_html_e( 'If you like, you may connect your KeyCDN account to be able to purge the CDN cache from the WordPress admin bar.', 'cdn-enabler' ) ?></p>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'KeyCDN API Key', 'cdn-enabler' ); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="cdn_enabler_api_key">
                                    <input name="cdn_enabler[keycdn_api_key]" type="password" id="cdn_enabler_api_key" value="<?php echo esc_attr( CDN_Enabler_Engine::$settings['keycdn_api_key'] ); ?>" class="regular-text code" />
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'KeyCDN Zone ID', 'cdn-enabler' ); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="cdn_enabler_zone_id">
                                    <input name="cdn_enabler[keycdn_zone_id]" type="text" id="cdn_enabler_zone_id" value="<?php echo esc_attr( CDN_Enabler_Engine::$settings['keycdn_zone_id'] ); ?>" class="regular-text code" />
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" class="button-secondary" value="<?php esc_html_e( 'Save Changes', 'cdn-enabler' ); ?>" />
                    <input name="cdn_enabler[validate_config]" type="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes and Validate Configuration', 'cdn-enabler' ); ?>" />
                </p>
            </form>
        </div>

        <?php

    }
}
