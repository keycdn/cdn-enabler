<?php
/**
 * CDN Enabler engine
 *
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CDN_Enabler_Engine {

    /**
     * start engine
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public static function start() {

        new self();
    }


    /**
     * engine settings from database
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @var     array
     */

    public static $settings;


    /**
     * constructor
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    public function __construct() {

        // get settings from database
        self::$settings = CDN_Enabler::get_settings();

        if ( ! empty( self::$settings ) ) {
            self::start_buffering();
        }
    }


    /**
     * start output buffering
     *
     * @since   2.0.0
     * @change  2.0.0
     */

    private static function start_buffering() {

        ob_start( 'self::end_buffering' );
    }


    /**
     * end output buffering and rewrite contents if applicable
     *
     * @since   2.0.0
     * @change  2.0.3
     *
     * @param   string   $contents                      contents from the output buffer
     * @param   integer  $phase                         bitmask of PHP_OUTPUT_HANDLER_* constants
     * @return  string   $contents|$rewritten_contents  rewritten contents from the output buffer if applicable, unchanged otherwise
     */

    private static function end_buffering( $contents, $phase ) {

        if ( $phase & PHP_OUTPUT_HANDLER_FINAL || $phase & PHP_OUTPUT_HANDLER_END ) {
            if ( ! self::bypass_rewrite() ) {
                $rewritten_contents = self::rewriter( $contents );

                return $rewritten_contents;
            }
        }

        return $contents;
    }


    /**
     * check if file URL is excluded from rewrite
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   string   $file_url  full or relative URL to potentially exclude from being rewritten
     * @return  boolean             true if file URL is excluded from the rewrite, false otherwise
     */

    private static function is_excluded( $file_url ) {

        // if string excluded (case sensitive)
        if ( ! empty( self::$settings['excluded_strings'] ) ) {
            $excluded_strings = explode( PHP_EOL, self::$settings['excluded_strings'] );

            foreach ( $excluded_strings as $excluded_string ) {
                if ( strpos( $file_url, $excluded_string ) !== false ) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * check if administrative interface page
     *
     * @since   2.0.1
     * @change  2.0.1
     *
     * @return  boolean  true if administrative interface page, false otherwise
     */

    private static function is_admin() {

        if ( apply_filters( 'cdn_enabler_exclude_admin', is_admin() ) ) {
            return true;
        }

        return false;
    }


    /**
     * check if rewrite should be bypassed
     *
     * @since   2.0.0
     * @change  2.0.1
     *
     * @return  boolean  true if rewrite should be bypassed, false otherwise
     */

    private static function bypass_rewrite() {

        // bypass rewrite hook
        if ( apply_filters( 'cdn_enabler_bypass_rewrite', false ) ) {
            return true;
        }

        // check request method
        if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
            return true;
        }

        // check conditional tags
        if ( self::is_admin() || is_trackback() || is_robots() || is_preview() ) {
            return true;
        }

        return false;
    }


    /**
     * rewrite URL to use CDN hostname
     *
     * @since   2.0.0
     * @change  2.0.2
     *
     * @param   array   $matches   pattern matches from parsed contents
     * @return  string  $file_url  rewritten file URL if applicable, unchanged otherwise
     */

    private static function rewrite_url( $matches ) {

        $file_url       = $matches[0];
        $site_hostname  = ( ! empty( $_SERVER['HTTP_HOST'] ) ) ? $_SERVER['HTTP_HOST'] : parse_url( home_url(), PHP_URL_HOST );
        $site_hostnames = (array) apply_filters( 'cdn_enabler_site_hostnames', array( $site_hostname ) );
        $cdn_hostname   = self::$settings['cdn_hostname'];

        // if excluded or already using CDN hostname
        if ( self::is_excluded( $file_url ) || stripos( $file_url, $cdn_hostname ) !== false ) {
            return $file_url;
        }

        // rewrite full URL (e.g. https://www.example.com/wp..., https:\/\/www.example.com\/wp..., or //www.example.com/wp...)
        foreach ( $site_hostnames as $site_hostname ) {
            if ( stripos( $file_url, '//' . $site_hostname ) !== false || stripos( $file_url, '\/\/' . $site_hostname ) !== false ) {
                return substr_replace( $file_url, $cdn_hostname, stripos( $file_url, $site_hostname ), strlen( $site_hostname ) );
            }
        }

        // rewrite relative URLs hook
        if ( apply_filters( 'cdn_enabler_rewrite_relative_urls', true ) ) {
            // rewrite relative URL (e.g. /wp-content/uploads/example.jpg)
            if ( strpos( $file_url, '//' ) !== 0 && strpos( $file_url, '/' ) === 0 ) {
                return '//' . $cdn_hostname . $file_url;
            }

            // rewrite escaped relative URL (e.g. \/wp-content\/uploads\/example.jpg)
            if ( strpos( $file_url, '\/\/' ) !== 0 && strpos( $file_url, '\/' ) === 0 ) {
                return '\/\/' . $cdn_hostname . $file_url;
            }
        }

        return $file_url;
    }


    /**
     * rewrite contents
     *
     * @since   2.0.0
     * @change  2.0.4
     *
     * @param   string  $contents                      contents to parse
     * @return  string  $contents|$rewritten_contents  rewritten contents if applicable, unchanged otherwise
     */

    public static function rewriter( $contents ) {

        // check rewrite requirements
        if ( ! is_string( $contents ) || empty( self::$settings['cdn_hostname'] ) || empty( self::$settings['included_file_extensions'] ) ) {
            return $contents;
        }

        $contents = apply_filters( 'cdn_enabler_contents_before_rewrite', $contents );

        $included_file_extensions_regex = quotemeta( implode( '|', explode( PHP_EOL, self::$settings['included_file_extensions'] ) ) );

        $urls_regex = '#(?:(?:[\"\'\s=>,;]|url\()\K|^)[^\"\'\s(=>,;]+(' . $included_file_extensions_regex . ')(\?[^\/?\\\"\'\s)>,]+)?(?:(?=\/?[?\\\"\'\s)>,&])|$)#i';

        $rewritten_contents = apply_filters( 'cdn_enabler_contents_after_rewrite', preg_replace_callback( $urls_regex, 'self::rewrite_url', $contents ) );

        return $rewritten_contents;
    }
}
