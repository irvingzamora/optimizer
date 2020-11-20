<?php
/**
 * Plugin name: MyOptimizer
 * Author: Riding Hood Media
 * Version: 1.0.0
 * TODO: This is a proof of concept, not yet ready for production
 */
require 'src/MyOptimizer.php';


/**
 * Loop through all the files under the theme folder
 * Was exploring this option but realized I dont have access to the css files
 */
$theme_url = get_template_directory_uri();
$theme_dir = get_template_directory();
$wd = getcwd();
$files1 = scandir($theme_dir);
foreach ($files1 as $value) {
    $newstring = substr($value, -3);
    // var_dump($newstring);
    if($newstring === "php"){
        // var_dump($value);

        $section = file_get_contents($theme_dir .'/'. $value);
        // if(strpos($value, 'header.php') !== false){
        //     var_dump($section);
        // }
        $html = $section;
        $needle = '.css';
        $lastPos = 0;
        $positions = array();

        while (($lastPos = strpos($html, $needle, $lastPos))!== false) {
            $positions[] = $lastPos;
            $lastPos = $lastPos + strlen($needle);
        }
        // var_dump($positions);
        // Displays 3 and 10
        // foreach ($positions as $value) {
        //     var_dump( $value ."<br />");
        // }  
        // var_dump($section);
    }
}
/**
 * End Loop through all the files
 */


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
$optimizer->optimize();

/**
 * Earliest point where WordPress knows what page is being loaded
 * Dequeue all scripts
 * TODO: This breaks a lot of things including our menu, it would be good to group the
 * TODO: scripts in terms of functionality they relate to
 */
add_action('template_redirect', function () use ($scripts, $styles, $optimizer) {
    if ( !is_page('blog') ) {
        $optimizer->optimizeScripts();
        $optimizer->optimize(['test']);
        $optimizer->removeScripts($scripts)->removeStyles($styles);
    }
});
