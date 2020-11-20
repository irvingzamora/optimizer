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
  * UNCSS Test on template_redirect
  * Call a function on the template_redirect action
  * This way we have access to the html generated when the page is loaded
  * A new html file is generated, then uncss is runned againts that file
  * Currently it generates an html file ready to uncss.
  * Problem it doesnt run the command uncss programmatically
  * The good thing is you can run the command manually agains the generated file and successfully uncss
  */
add_action('template_redirect', 'read_buffer');
function read_buffer(){
    ob_start('read_buffer_callback');
}


// add_action('shutdown', 'foo_buffer_stop', 1000);
// function foo_buffer_stop(){
//     ob_end_flush();
// }
function read_buffer_callback($buffer){
    //Do something with the buffer (HTML)

    $html = $buffer;
    $needle = '<link rel="stylesheet';
    $lastPos = 0;
    $positions = array();
    $theme_uri = get_theme_file_uri();
            $wd = getcwd() . '/wordpress';
    $html = str_replace($theme_uri, '..', $html);
    // $html = str_replace('http://localhost:8000/wp-includes', '../../../../wp-includes', $html);
    
    //Remove google fonts
    $lastPos = 0;
    while (($lastPos = strpos($html, 'fonts.googleapis.com', $lastPos))!== false) {
        $lastPos = $lastPos - 100;//Return 100chars to find starting link tag
        $googlefont_start = strpos($html, "<link rel='stylesheet'", $lastPos);
        $googlefont_end = strpos($html, "/>", $googlefont_start) + strlen("/>");
        $googlefont = substr($html,$googlefont_start, ($googlefont_end - $googlefont_start));
        $html = str_replace($googlefont, '', $html);
        $lastPos = $googlefont_end;
    }

    //Remove js versioning
    $lastPos = 0;
    while (($lastPos = strpos($html, '?ver=', $lastPos))!== false) {
        
        $comma = strpos($html, "'", $lastPos);
        $jsver = substr($html,$lastPos, ($comma - $lastPos));
        $html = str_replace($jsver, '', $html);
    }

    $theme_dir = get_template_directory();

    //Create directory to store optimized files
    if(!file_exists($theme_dir .'/optimizedfiles')){
        mkdir($theme_dir .'/optimizedfiles', 0777, true);
    }
    $htmlfile = fopen($theme_dir ."/optimizedfiles/test.html", "w") or die("Unable to open file!");
    fwrite($htmlfile, $html);
    fclose($htmlfile);
    chmod($theme_dir ."/optimizedfiles/test.html", 0777);
    $cssfile = fopen($theme_dir ."/optimizedfiles/newcss.css", "w") or die("Unable to open file!");
    fwrite($cssfile, 'uncss '.$theme_dir .'/optimizedfiles/test.html'.' > '. $theme_dir.'/optimizedfiles/newcss.css');
    fclose($cssfile);
    chmod($theme_dir ."/optimizedfiles/newcss.css", 0777);
    exec('uncss '.$theme_dir .'/optimizedfiles/test.html'.' > '. $theme_dir.'/optimizedfiles/newcss.css'); 

    //$position = strpos($string, 'a');



    // var_dump($buffer);
    // var_dump($positions);

    $buffer = $buffer . "textend";
    return $buffer;
}

/**
 * End UNCSS Test on template_redirect
 * 
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
