<?php

interface CSSCleanerInterface
{
    // Returns path of optimized css file or empty string if optimization failed
    public function removeUnusedCSS($filepath) : string;
}

interface CSSMinimizerInterface {
    // Returns path of minimized css file or empty string is minimization failed
    public function minifyCSS($filepath);
}

interface JSMinimizerInterface
{
    // Returns path of minimized js file or empty string is minimization failed
    public function minifyJS($filepath) : string;
}

class UnCSSProxy implements CSSCleanerInterface {
    public function removeUnusedCSS($filepath) : string
    {
        // This is where the logic will be written
        // Check extension
        exec('uncss index.html > newcss.css'); 
        return "";
    }
}

class HeliumCSSProxy implements CSSCleanerInterface {
    public function removeUnusedCSS($filepath) : string
    {
        return "";
    }
}

class CSSMinimizerProxy implements CSSMinimizerInterface {
    
    public function minifyCSS($filepath) : string
    {
        return "";
    }
}

class JSMinimizerProxy implements JSMinimizerInterface {
    public function minifyJS($filepath) : string
    {
        return "";
    }
}

interface OptimizerInterface {
    public function optimizeAll(Array $filepathsArray);
    public function optimizeCSS(Array $filepathsArray);
    public function optimizeJS(Array $filepathsArray);
}

class WPOptimizer implements OptimizerInterface{

    private $cssCleaner;
    private $cssMinimizer;
    private $jSMinimizer;

    public function __construct( CSSCleanerInterface $cssCleaner, CSSMinimizerInterface $cssMinimizer, JSMinimizerInterface $jSMinimizer ) {
        $this->cssCleaner = $cssCleaner;
        $this->cssMinimizer = $cssMinimizer;
        $this->jSMinimizer = $jSMinimizer;
    }

    public function optimizeAll($filepathsArray)
    {
        foreach ($filepathsArray as $filepath) {
            $this->cssCleaner->removeUnusedCSS($filepath);
            $this->cssMinimizer->minifyCSS($filepath);
            $this->jSMinimizer->minifyJS($filepath);
        }
    }

    public function optimizeCSS($filepath)
    {
        $this->cssCleaner->removeUnusedCSS($filepath);
        $this->cssMinimizer->minifyCSS($filepath);
    }

    public function optimizeJS($filepath)
    {
        $this->jSMinimizer->minifyJS($filepath);
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
        }
    }

    public function optimize($filepathsArray)
    {
        $this->wpOptimizer->optimizeAll($filepathsArray);
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