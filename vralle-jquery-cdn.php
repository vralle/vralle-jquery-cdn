<?php
/**
 * Plugin Name:         vralle.jQuery-CDN
 * Plugin URI:          https://github.com/vralle/vralle-jquery-cdn
 * Description:         A modern way to load jQuery from CDN with a local fallback
 * Version:             2019-12-27
 * Author:              V.Ralle
 * Author URI:          https://github.com/vralle/
 * License:             MIT
 * GitHub Plugin URI:   https://github.com/vralle/vralle-jquery-cdn
 * Requires WP:         4.6
 * Requires PHP:        5.6
 *
 * @package vralle-jqury-cdn
 */

namespace VralleJqueryCdn;

use function add_action;
use function add_filter;
use function apply_filters;
use function function_exists;
use function esc_url;
use function includes_url;
use function is_admin;
use function is_amp_endpoint;
use function is_customize_preview;
use function sprintf;
use function version_compare;
use function wp_add_inline_script;
use function wp_deregister_script;
use function wp_register_script;
use function wp_script_is;
use function wp_scripts;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

/**
 * Register all of the needed hooks and actions.
 */
function init() {
    add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts', 11 );
    add_filter( 'script_loader_tag', __NAMESPACE__ . '\\make_tag', 10, 3 );
}

/**
 * Load jQuery from Google CDN with a local fallback
 */
function enqueue_scripts() {
    if ( is_admin() || is_customize_preview() || is_preview() ) {
        return;
    }
    $jquery_script = wp_scripts()->registered['jquery-core'];
    $jquery_src    = $jquery_script->src;
    $jquery_ver    = $jquery_script->ver;

    /**
     * CDN version of jQuery
     *
     * @param string             Current jQuery version.
     * @param string $jquery_ver The jQuery version used by WordPress.
     */
    $cdn_ver = apply_filters( 'vralle_jquery_cdn_ver', '3.5.1', $jquery_ver );

    wp_deregister_script( 'jquery-core' );
    wp_deregister_script( 'jquery' );

    wp_register_script(
        'jquery-core',
        'https://ajax.googleapis.com/ajax/libs/jquery/' . $cdn_ver . '/jquery.min.js',
        array(),
        $cdn_ver,
        true
    );

    wp_register_script( 'jquery', false, array( 'jquery-core' ), $cdn_ver, true );

    $site_url = site_url();

    if ( ! $site_url ) {
        $site_url = wp_guess_url();
    }

    $fallback = sprintf(
        '(window.jQuery && jQuery.noConflict()) || document.write(\'<script src=\"%s\"><\/script>\')',
        esc_url( $site_url . $jquery_src )
    );
    wp_add_inline_script( 'jquery', $fallback );
}


/**
 * Make HTML tag
 *
 * @param string $tag    The <script> tag for the enqueued script.
 * @param string $handle The script's registered handle.
 * @param string $src    The script's source URL.
 *
 * @return string The script tag
 */
function make_tag( $tag, $handle, $src ) {
    if ( is_admin() || is_customize_preview() || is_preview() ) {
        return $tag;
    }
    if ( 'jquery-core' === $handle ) {
        $tag = sprintf(
            '<script src="%s" crossorigin="anonymous"></script>',
            esc_url( $src )
        );
    }

    return $tag;
}

/**
 * Check is AMP endpoint
 *
 * @return boolean
 */
function is_amp() {
    return function_exists( 'is_amp_endpoint' ) && is_amp_endpoint();
}
