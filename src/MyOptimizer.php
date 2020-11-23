<?php


include_once 'OptimizerInterface.php';
include_once 'CSSCleanerInterface.php';
include_once 'CSSMinimizerInterface.php';
include_once 'JSMinimizerInterface.php';
include_once 'UnCSSProxy.php';
include_once 'CSSMinimizerProxy.php';
include_once 'JSMinimizerProxy.php';

class WPOptimizer implements OptimizerInterface{

    private $cssCleaner;
    private $cssMinimizer;
    private $jSMinimizer;

    public function __construct( CSSCleanerInterface $cssCleaner, CSSMinimizerInterface $cssMinimizer, JSMinimizerInterface $jSMinimizer ) {
        $this->cssCleaner = $cssCleaner;
        $this->cssMinimizer = $cssMinimizer;
        $this->jSMinimizer = $jSMinimizer;
    }

    public function getCSSCleaner()
    {
        return $this->cssCleaner;
    }

    public function getCSSMinimizer()
    {
        return $this->cssMinimizer;
    }

    public function getJSMinimizer()
    {
        return $this->jSMinimizer;
    }

    public function optimizeCurrentPage()
    {
        add_action('template_redirect', [$this, 'readBuffer']);
        
    }

    public function readBuffer(){
        ob_start('read_buffer_callback');
    }

    
    public function optimizeAllPages($filepathsArray)
    {
        foreach ($filepathsArray as $filepath) {
            // $this->cssCleaner->removeUnusedCSS($inputfile_path, $outputfile_path)
            // $this->cssMinimizer->minifyCSS($inputfile_path, $outputfile_path)
            // $this->jSMinimizer->minifyJS($inputfile_path, $outputfile_path)
        }
    }

    

}

/**
 * Class Optimizer
 *
 * Optimized scripts and style assets loaded by the program
 * TODO: Implement automatic detection of assets for each page and cache them with UnCSS
 * TODO: https://github.com/uncss/uncss
 * TODO: This is currently a proof of concept, needs refactoring, interfaces, and unit tests
 */

class Optimizer
{

    private $wpOptimizer;

    public function __construct($type) {
        $cssCleaner = new UnCSSProxy();
        $cssMinimizer = new CSSMinimizerProxy();
        $jSMinimizer = new JSMinimizerProxy();
        if($type == "wordpress"){
            $this->wpOptimizer = new WPOptimizer($cssCleaner, $cssMinimizer, $jSMinimizer);
        }if ($type == "x") {
            
        } else {
            #Type not supported
        }
        
    }


    public function optimizeAll($filepathsArray)
    {
        $this->wpOptimizer->optimizeAllPages($filepathsArray);
    }
    
    public function optimize()
    {
        $this->wpOptimizer->optimizeCurrentPage();
    }

    public function optimizeScripts()
    {
        $this->disableWPOEmbed();

        add_filter('elementor/frontend/print_google_fonts', '__return_false');

        remove_action('wp_head', 'print_emoji_detection_script', 7);
        add_action('wp_enqueue_scripts', [$this, 'removeWPBlockLibCSS'], 100);
        return $this;
    }

    /**
     * Disable the WPOEmbed functionality that isn't used
     * TODO: We may want to expose this as an optional setting through the WP backend
     */
    private function disableWPOEmbed()
    {
        // Remove the REST API endpoint
        remove_action('rest_api_init', 'wp_oembed_register_route');

        // Turn off oEmbed auto discovery
        add_filter('embed_oembed_discover', '__return_false');

        // Don't filter oEmbed results
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

        // Remove oEmbed discovery links
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        // Remove oEmbed-specific JavaScript from the front-end and back-end
        remove_action('wp_head', 'wp_oembed_add_host_js');
        
        add_filter('tiny_mce_plugins', function ($plugins) {
            return array_diff($plugins, ['wpembed']);
        });

        // Remove all embeds rewrite rules
        add_filter('rewrite_rules_array', 'disableEmbedsRewriteRules');

        // Remove filter of the oEmbed result before any HTTP requests are made
        remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
    }

    /**
     * Part of disabling the WP OEmbed functionality
     * @param $rules
     * @return mixed
     */
    public function disableEmbedsRewriteRules($rules)
    {
        foreach ($rules as $rule => $rewrite) {
            if (false !== strpos($rewrite, 'embed=true')) {
                unset($rules[$rule]);
            }
        }
        return $rules;
    }
    
    /*
     * Remove Gutenberg Block Library CSS from loading on the frontend
     */
    public function removeWPBlockLibCSS()
    {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-block-style'); // Remove WooCommerce block CSS
    }

    /**
     * Prevent styles from being loaded based on an array of style handles.
     * When a style is registered or enqueued with WP, an official handle is
     * used to reference it. We're using that handle to disable the style.
     * @param $styles
     * @return $this
     */
    public function removeStyles($styles)
    {
        // Remove comment reply script
        add_action('wp_enqueue_scripts', function () use ($styles) {
            foreach ($styles as $style) {
                wp_dequeue_style($style);
                wp_deregister_style($style);
            }
        }, 999999);

        return $this;
    }

    /**
     * Prevent scripts from being loaded based on an array of script handles.
     * When a script is registered or enqueued with WP, an official handle is
     * used to reference it. We're using that handle to disable the script.
     * @param $scripts
     * @return $this
     */
    public function removeScripts($scripts)
    {
        // Remove comment reply script
        add_action('wp_enqueue_scripts', function () use ($scripts) {
            foreach ($scripts as $script) {
                wp_dequeue_script($script);
                wp_deregister_script($script);
            }
        }, 999999);

        return $this;
    }

    /**
     * Utility class to print a list of styles and scripts loaded on each page.
     * This code is from an online tutorial.
     * TODO: We can abstract this into another class and refactor the code to be more
     * TODO: efficient, and turn this functionality on/off with a setting
     */
    private function showLoadedScripts()
    {
        add_action('wp_print_scripts', 'cyb_list_scripts');
        function cyb_list_scripts()
        {
            global $wp_scripts;
            global $enqueued_scripts;
            $enqueued_scripts = array();
            foreach ($wp_scripts->queue as $handle) {
                $enqueued_scripts[] = [$handle]; // $wp_scripts->registered[$handle]->src;
            }
        }

        add_action('wp_print_styles', 'cyb_list_styles');
        function cyb_list_styles()
        {
            global $wp_styles;
            global $enqueued_styles;
            $enqueued_styles = array();
            foreach ($wp_styles->queue as $handle) {
                $enqueued_styles[] = [$handle]; //$wp_styles->registered[$handle]->src;
            }
        }

        add_action('wp_head', function () {
            global $enqueued_scripts;
            //var_dump( $enqueued_scripts );
            global $enqueued_styles;
            //var_dump( $enqueued_styles );
        });
    }
}

// function read_buffer(){
//     ob_start('read_buffer_callback');
// }

function read_buffer_callback($buffer){
    //Do something with the buffer (HTML)

    $cssCleaner = new UnCSSProxy();
    $cssMinimizer = new CSSMinimizerProxy();
    $jSMinimizer = new JSMinimizerProxy();
    $wpOptimizer = new WPOptimizer($cssCleaner, $cssMinimizer, $jSMinimizer);

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
    $htmlfile = fopen($theme_dir ."/optimizedfiles/temp.html", "w") or die("Unable to open file!");
    fwrite($htmlfile, $html);
    fclose($htmlfile);
    chmod($theme_dir ."/optimizedfiles/temp.html", 0777);
    $cssfile = fopen($theme_dir ."/optimizedfiles/newcss.css", "w") or die("Unable to open file!");
    fwrite($cssfile, 'uncss '.$theme_dir .'/optimizedfiles/temp.html'.' > '. $theme_dir.'/optimizedfiles/newcss.css');
    fclose($cssfile);
    chmod($theme_dir ."/optimizedfiles/newcss.css", 0777);
    $inputfile_path = $theme_dir .'/optimizedfiles/temp.html';
    $outputfile_path = $theme_dir.'/optimizedfiles/newcss.css';

    $wpOptimizer->getCSSCleaner()->removeUnusedCSS($inputfile_path, $outputfile_path);
    $wpOptimizer->getCSSMinimizer()->minifyCSS($inputfile_path, $outputfile_path);
    $wpOptimizer->getJSMinimizer()->minifyJS($inputfile_path, $outputfile_path);
    //$position = strpos($string, 'a');



    // var_dump($buffer);
    // var_dump($positions);

    $buffer = $buffer . "textend";
    return $buffer;
}
