<?php

/**
 * Optmizer must extend OptimizerEngine
 * OptimizerEngine must implement OptimizerEngineInterface
 * OptimizerEngineInterface must implement CSSOptimizer and JSOptimizer
 * CSSOptimizer must include methods optimizeCSS
 * JSOptimizer must include methods optimizejs
 * 
 * class UNCSS must implement CSSOptimizer
 * class XUNCSS must implement CSSOptimizer
 * class YUNCSS must implement CSSOptimizer
 * 
 * class OptimizerEngine must be able to select correct CSSOptimizer
 */

interface CSSOptimizerInterface
{
    public function removeUnusedCSS();
}

interface CSSMinimizerInterface
{
    public function minifyCSS();
}

/** 
 * Interface used to declare CSS Optimization methods
 */
interface CSSOptimizerEngineInterface extends CSSOptimizerInterface, CSSMinimizerInterface{
    public function getOptimizer($slug, $optimzer);
}

/**
 * Class that implements CSSOptimizerInterface, this is used to specifically used with UNCSS tool
 */
class UNCSSOptimizer implements CSSOptimizerInterface{

    public function optimizeCSS(CSSOptimizerInterface $cssOptimizer) {
        $cssOptimizer->removeUnusedCSS();
    }

    public function removeUnusedCSS() {

    }
}

class CSSOptimizerEngine implements CSSOptimizerEngineInterface {

    public function getOptimizer($lug, $optimzer) {
        //Return the correct optimizer based on parameters
    }

    public function removeUnusedCSS() {
        //Call selected optimiezer removeUnusedCSS
    }
    
    public function minifyCSS() {
        //Call selected optimiezer minifyCSS

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