<?php
/**
 * Interact with CDN Enabler.
 *
 * @since  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CDN_Enabler_CLI {

    /**
     * Purge the CDN cache.
     *
     * Returns an error if the purge CDN cache attempt failed.
     *
     * ## EXAMPLES
     *
     *    # Purge the CDN cache.
     *    $ wp cdn-enabler purge
     *    Success: CDN cache purged.
     *
     * @alias purge
     */

    public function purge() {

        $response = CDN_Enabler::purge_cdn_cache();

        if ( strpos( $response['wrapper'], 'success' ) !== false ) {
            return WP_CLI::success( $response['message'] );
        } else {
            return WP_CLI::error( $response['subject'] . ' ' . $response['message'] );
        }
    }
}

// add WP-CLI command
WP_CLI::add_command( 'cdn-enabler', 'CDN_Enabler_CLI' );
