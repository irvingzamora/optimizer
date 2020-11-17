<?php
/**
 * Plugin name: MyOptimizer
 * Author: Riding Hood Media
 * Version: 1.0.0
 * TODO: This is a proof of concept, not yet ready for production
 */
require 'src/Optimizer.php';

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

$optimizer = new Optimizer();

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