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
     * end output buffering and rewrite URLs if applicable
     *
     * @since   2.0.0
     * @change  2.0.0
     *
     * @param   string   $page_contents  contents of a page from the output buffer
     * @param   integer  $phase          bitmask of PHP_OUTPUT_HANDLER_* constants
     * @return  string   $page_contents  maybe rewritten contents of a page from the output buffer
     */

    private static function end_buffering( $page_contents, $phase ) {

        if ( $phase & PHP_OUTPUT_HANDLER_FINAL || $phase & PHP_OUTPUT_HANDLER_END ) {
            if ( self::bypass_rewrite() ) {
                return $page_contents;
            }

            $page_contents = apply_filters( 'cdn_enabler_page_contents_before_rewrite', $page_contents );

            $page_contents = self::rewriter( $page_contents );

            return $page_contents;
        }
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
     * check if rewrite should be bypassed
     *
     * @since   2.0.0
     * @change  2.0.0
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
        if ( is_admin() || is_trackback() || is_robots() || is_preview() ) {
            return true;
        }

        return false;
    }


    /**
     * rewrite URL to use CDN hostname
     *
     * @since   0.0.1
     * @change  2.0.0
     *
     * @param   array   $matches   pattern matches from parsed file contents
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
            if ( stripos( $file_url, '/' . $site_hostname ) !== false ) {
                return str_replace( $site_hostname, $cdn_hostname, $file_url );
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
     * rewrite file contents
     *
     * @since   0.0.1
     * @change  2.0.0
     *
     * @param   string  $contents                      contents of file
     * @return  string  $contents|$rewritten_contents  rewritten file contents if applicable, unchanged otherwise
     */

    public static function rewriter( $contents ) {

        // check rewrite requirements
        if ( ! is_string( $contents ) || empty( self::$settings['cdn_hostname'] ) || empty( self::$settings['included_file_extensions'] ) ) {
            return $contents;
        }

        $included_file_extensions_regex = quotemeta( implode( '|', explode( PHP_EOL, self::$settings['included_file_extensions'] ) ) );

        $urls_regex = '#[^\"\'\s=>(,]+(' . $included_file_extensions_regex . ')(?=\/?[?\\\"\'\s)>])#i';

        $rewritten_contents = preg_replace_callback( $urls_regex, 'self::rewrite_url', $contents );

        return $rewritten_contents;
    }
}