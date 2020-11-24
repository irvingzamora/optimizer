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
        // add_action('template_redirect', [$this, 'readBuffer']);
        //exec('uncss '. 'http://localhost:8000/' . ' > ' . getcwd() . '/wordpress/wp-content/cache/newcss.css'); 
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

        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        ); 
        $pages = get_pages($args); // get all pages based on supplied args
        foreach($pages as $page){ // $pages is array of object
            // var_dump( $page);
           $page_template = get_post_meta($page->ID, '_wp_page_template', true); // Page template stored in "_wp_page_template"
        
        //    echo $page_template;
        }

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

        $this->removePageStyles();
        
        return $this;
    }

    /**
     * Remove styles registered in the page
     */
    
    private function removePageStyles()
    {
        // add_action('wp_print_styles', 'remove_page_styles');
        function remove_page_styles()
        {
            
            global $wp_styles;
            
            $array = array();
            // Runs through the queue styles
            foreach ($wp_styles->queue as $handle) :
                $array[] = $handle;
            endforeach;
            
            wp_dequeue_style($array);
            wp_deregister_style($array);
        }
        // add_action('wp_print_styles', 'add_uncssed_style');
        function add_uncssed_style()
        {
            wp_register_style('myownstyle', content_url() . '/cache/newcss.css');
            wp_enqueue_style('myownstyle', content_url() . '/cache/newcss.css');
            
        }
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

