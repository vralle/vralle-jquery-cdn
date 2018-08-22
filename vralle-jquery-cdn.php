<?php
namespace Vralle\Jquery\Cdn;

/**
 * Plugin Name: vralle.jQuery.CDN
 * Plugin URI: https://github.com/vralle/vralle-jquery-cdn
 * Description: Load jQuery from jQuery's CDN with a local fallback
 * Version: 2018-08-22
 * Author: V.Ralle
 * Author URI: https://github.com/vralle/
 * License: MIT
 * GitHub Plugin URI: https://github.com/vralle/vralle-jquery-cdn.git
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

function registerJquery()
{
    $jquery_version = \wp_scripts()->registered['jquery']->ver;
    \wp_deregister_script('jquery');
    \wp_register_script(
        'jquery',
        'https://code.jquery.com/jquery-' . $jquery_version . '.min.js',
        [],
        null,
        true
    );

    \add_filter('script_loader_src', __NAMESPACE__ . '\\localFallback', 10, 2);
}
\add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\registerJquery', 100);

function addResourceHints($urls, $relation_type)
{
    if ($relation_type === 'dns-prefetch') {
        $urls[] = 'code.jquery.com';
    }
    return $urls;
}
\add_filter('wp_resource_hints', __NAMESPACE__ . '\\addResourceHints', 10, 2);

/**
 * Output the local fallback immediately after jQuery's <script>
 *
 * @link http://wordpress.stackexchange.com/a/12450
 */
function localFallback($src, $handle = null)
{
    static $add_jquery_fallback = false;
    if ($add_jquery_fallback) {
        echo '<script>(window.jQuery && jQuery.noConflict()) || document.write(\'<script src="' . $add_jquery_fallback .'"><\/script>\')</script>' . "\n";
        $add_jquery_fallback = false;
    }
    if ($handle === 'jquery') {
        $add_jquery_fallback = \apply_filters(
            'script_loader_src',
            \includes_url('/js/jquery/jquery.js'),
            'jquery-fallback'
        );
    }
    return $src;
}
\add_action('wp_head', __NAMESPACE__ . '\\localFallback');

function tagAttr($tag, $handle, $src)
{
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
}
\add_filter('script_loader_tag', __NAMESPACE__ . '\\tagAttr', 10, 3);

function getHash($src)
{
    $hash = \get_transient('jquery_hash');
    if (false === $hash) {
        $hash = \base64_encode(hash_file('sha384', $src, true));
        // If "allow_url_fopen=0", hash_file returns empty string.
        // ToDo: Admin Notice, if allow_url_fopen=0
        if ('' != $hash) {
            set_transient('jquery_hash', $hash, WEEK_IN_SECONDS);
        } else {
            $hash = false;
        }
    }

    return $hash;
}

function deleteHash()
{
    \delete_transient('jquery_hash');
}
\add_action('_core_updated_successfully', __NAMESPACE__ . '\\deleteHash');
