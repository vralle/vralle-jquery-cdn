<?php
namespace Vralle\Jquery\Cdn;

/**
 * Plugin Name:         vralle.jQuery-CDN
 * Plugin URI:          https://github.com/vralle/vralle-jquery-cdn
 * Description:         A modern way to load jQuery from CDN with a local fallback
 * Version:             2019-01-21
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

function is_amp()
{
    return function_exists('is_amp_endpoint') && is_amp_endpoint();
}

\add_filter('wp_resource_hints', __NAMESPACE__  . '\\set_resource_hints', 10, 2);
function set_resource_hints($urls, $relation_type)
{
    if (is_admin() || is_amp()) {
        return $urls;
    }

    if ($relation_type === 'preconnect') {
        $urls[] = array(
            'href' => 'https://ajax.googleapis.com',
            'crossorigin' => 'anonymous',
        );
    }

    return $urls;
}

\add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts', 100);
function enqueue_scripts()
{
    $jquery_version = \wp_scripts()->registered['jquery']->ver;
    \wp_deregister_script('jquery');
    \wp_register_script(
        'jquery',
        'https://ajax.googleapis.com/ajax/libs/jquery/' . $jquery_version . '/jquery.min.js',
        [],
        null,
        true
    );

    $jquery_fallback = \sprintf(
        '(window.jQuery && jQuery.noConflict()) || document.write(\'<script src=\"%s\"><\/script>\')',
        \esc_url(\includes_url('/js/jquery/jquery.js'))
    );
    \wp_add_inline_script('jquery', $jquery_fallback);
}

\add_filter(
    'script_loader_tag',
    function ($tag, $handle, $src) {
        if (is_admin()) {
            return $tag;
        }
        if ('jquery' === $handle) {
            $tag = \str_replace(
                '></script>',
                ' crossorigin="anonymous"></script>',
                $tag
            );
        }

        return $tag;
    }
);
