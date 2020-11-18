<?php
/**
 * Plugin name: MyOptimizer
 * Author: Riding Hood Media
 * Version: 1.0.0
 * TODO: This is a proof of concept, not yet ready for production
 */
require 'src/MyOptimizer.php';

function test_function()
{
    return "Hello world";
}

function autoptimize_incompatible_admin_notice() {
    echo '<div class="error"><p>' . __( 'Hello World' ) . '</p></div>';
    
}
function example_callback( $string, $arg1, $arg2, $arg3 ) {
    // (maybe) modify $string.
    return $arg3;
}
add_filter( 'example_filter', 'example_callback', 10, 5);
$arg1 = 54;
$arg2 = 234;
$value = apply_filters( 'filter_doesnt_exists', $arg1);
$value = apply_filters( 'example_filter', 'filter me', $arg1, $arg2, 'Check1', 'Check2' );
echo $value;

add_shortcode('example', 'test_function');

add_action( 'admin_notices', 'autoptimize_incompatible_admin_notice' );


/**
 * Styles to dequeue globally
 * TODO: This array doesn't belong here, just here for testing.
 */
$styles = ['elementor-animations',
    'elementor-icons',
    'elementor-icons-fa-brands',
    'elementor-icons-fa-regular',
    'elementor-icons-fa-solid',
    'elementor-icons-shared-0',
    'google-fonts-1',
    'google-fonts-2',
    'search-filter-plugin-styles',
    'wp-block-library'
];

/**
 * Styles to dequeue globally
 * TODO: This array doesn't belong here, just here for testing.
 */
$scripts = [
    'comment-reply',
    'astra-flexibility',
    'astra-theme-js',
    'backbone',
    'backbone-marionette',
    'backbone-radio',
    'comment-reply',
    'elementor-app-loader',
    'elementor-dialog',
    'elementor-frontend',
    'elementor-frontend-modules',
    'elementor-pro-frontend',
    'elementor-recaptcha_v3-api',
    'elementor-sticky',
    'elementor-waypoints',
    'fitty',
    'jquery',
    'jquery-core',
    'jquery-migrate',
    'jquery-ui-core',
    'jquery-ui-datepicker',
    'jquery-ui-draggable',
    'jquery-ui-mouse',
    'jquery-ui-position',
    'jquery-ui-widget',
    'search-filter-elementor',
    'search-filter-plugin-build',
    'search-filter-plugin-chosen',
    'share-link',
    'smartmenus',
    'swiper',
    'underscore',
    'wp-api-request',
    'wp-embed',
];

$optimizer = new Optimizer("wordpress");

/**
 * Earliest point where WordPress knows what page is being loaded
 * Dequeue all scripts
 * TODO: This breaks a lot of things including our menu, it would be good to group the
 * TODO: scripts in terms of functionality they relate to
 */
add_action('template_redirect', function () use ($scripts, $styles, $optimizer) {
    if ( !is_page('blog') ) {
        $optimizer->optimizeScripts();
        $optimizer->removeScripts($scripts)->removeStyles($styles);
    }
});