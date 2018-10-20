<?php
namespace Vralle\Jquery\Cdn;

/**
 * Plugin Name:         vralle.jQuery-CDN
 * Plugin URI:          https://github.com/vralle/vralle-jquery-cdn
 * Description:         A modern way to load jQuery from CDN with a local fallback
 * Version:             2018-10-20
 * Author:              V.Ralle
 * Author URI:          https://github.com/vralle/
 * License:             MIT
 * GitHub Plugin URI:   https://github.com/vralle/vralle-jquery-cdn.git
 * Requires WP:         4.6
 * Requires PHP:        5.6
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

\add_filter('wp_resource_hints', function ($urls, $relation_type) {
    if ($relation_type === 'preconnect') {
        $urls[] = 'ajax.googleapis.com';
    }
    return $urls;
}, 10, 2);

\add_action('wp_enqueue_scripts', function () {
    $jquery_version = \wp_scripts()->registered['jquery']->ver;
    \wp_deregister_script('jquery');
    \wp_register_script(
        'jquery',
        'https://ajax.googleapis.com/ajax/libs/jquery/' . $jquery_version . '/jquery.min.js',
        [],
        null,
        true
    );

    $jquery_fallback = sprintf(
        '(window.jQuery && jQuery.noConflict()) || document.write(\'<script src=\"%s\"><\/script>\')',
        \esc_url(\includes_url('/js/jquery/jquery.js'))
    );
    \wp_add_inline_script('jquery', $jquery_fallback);
}, 100);

\add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if ('jquery' === $handle) {
        $hash = getHash($src);

        if ($hash) {
            $tag = \str_replace(
                '></script>',
                ' integrity="sha384-' . $hash . '" crossorigin="anonymous"></script>',
                $tag
            );
        }
    }

    return $tag;
}, 10, 3);

function getHash($src)
{
    $hash = \get_transient('jquery_hash');
    if (false === $hash) {
        $hash = \base64_encode(hash_file('sha384', $src, true));
        // If "allow_url_fopen=0", hash_file returns empty string.
        // ToDo: Admin Notice, if allow_url_fopen=0
        if ('' != $hash) {
            \set_transient('jquery_hash', $hash, \WEEK_IN_SECONDS);
        } else {
            $hash = false;
        }
    }

    return $hash;
}

\add_action('_core_updated_successfully', function () {
    \delete_transient('jquery_hash');
});
