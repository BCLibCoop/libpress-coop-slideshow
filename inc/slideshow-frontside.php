<?php

/**
 * Plugin Name: Slideshow
 * Description: Slideshow frontside theme support script.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.3.2
 *
 * @package   Slideshow - frontside support
 * @copyright BC Libraries Coop 2013
 **/

namespace BCLibCoop;

class Slideshow
{
    private static $instance;
    private $slug = 'slideshow';
    public $show = null;

    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        global $wpdb;

        $table_name = $wpdb->prefix . 'slideshows';

        // Get the most recent active show by default
        $this->show = $wpdb->get_row("SELECT * FROM `$table_name` WHERE `is_active` = 1 ORDER BY `date` DESC");

        // Fall back to the most recent show even if not active
        if ($this->show == null) {
            $this->show = $wpdb->get_row("SELECT * FROM `$table_name` ORDER BY `date` DESC");
        }

        add_shortcode('coop-slideshow', [&$this, 'slideshowShortcode']);
        add_action('wp_enqueue_scripts', [&$this, 'enqueueAssets']);
    }

    /**
     * Right now just check for the front page. Should be expanded later to check
     * for the shortcode.
     */
    private function shouldEnqueueAssets()
    {
        return is_front_page();
    }

    public function enqueueAssets()
    {
        if ($this->shouldEnqueueAssets()) {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

            /* Get theme-specific CSS from plugin */
            $theme_styles = $this->fetchStylesUri();
            wp_enqueue_style('coop-slideshow-theme', $theme_styles['uri'], [], filemtime($theme_styles['path']));

            /* Global Slideshow Styling */
            wp_enqueue_style(
                'coop-slideshow',
                plugins_url('/assets/css/coop-slideshow.css', dirname(__FILE__)),
                [],
                filemtime(dirname(__FILE__) . '/../assets/css/coop-slideshow.css')
            );

            /* Script to resize text slide based on screen and layout width */
            wp_register_script(
                'bxslider-text-shim',
                plugins_url('/bxslider/plugins/text-slide-shim' . $suffix . '.js', dirname(__FILE__)),
                ['jquery'],
                '1.1',
                true
            );

            // wp_register_script(
            //     'jquery-easing',
            //     plugins_url('/bxslider/plugins/jquery.easing.1.3.js', dirname(__FILE__)),
            //     ['jquery'],
            //     '1.3',
            //     true
            // );

            // wp_register_script(
            //     'jquery-fitvids',
            //     plugins_url('/bxslider/plugins/jquery.fitvids.js', dirname(__FILE__)),
            //     ['jquery'],
            //     '1.1',
            //     true
            // );

            wp_enqueue_script(
                'bxslider',
                plugins_url('/bxslider/jquery.bxslider' . $suffix . '.js', dirname(__FILE__)),
                [
                    'jquery',
                    'bxslider-text-shim',
                    // 'jquery-easing', // For options not used
                    // 'jquery-fitvids', // For options not used
                ],
                '4.1.1',
                true
            );

            /* Attach global slideshow defaults */
            wp_add_inline_script('bxslider', SlideshowDefaults::defaultsPublishConfig(false));

            /* Attach per-slideshow settings and loader script */
            wp_add_inline_script('bxslider', $this->loaderScript(false));
        }
    }

    public function loaderScript($echo = true)
    {
        if ($this->show) {
            $layout = $this->show->layout;
            $transition = $this->show->transition;
            $captions  = $this->show->captions;

            $out = [];

            if ($echo) {
                $out[] = '<script type="text/javascript">';
            }

            $out[] = '';
            $out[] = '/* Per-show settings */';

            if ($layout == 'no-thumb') {
                $out[] = 'window.slideshow_custom_settings.pager = false;';
                $out[] = 'window.slideshow_custom_settings.controls = true;';
                $out[] = 'window.slideshow_custom_settings.nextSelector = null;';
                $out[] = 'window.slideshow_custom_settings.prevSelector = null;';
            } else {
                $out[] = 'window.slideshow_custom_settings.pager = true;';
                $out[] = 'window.slideshow_custom_settings.controls = false;';
            }

            $out[] = 'window.slideshow_custom_settings.autoPlay = true;';
            // $out[] = 'window.slideshow_custom_settings.easing = null;';

            $out[] = 'window.slideshow_custom_settings.captions = ' . $captions . ';';

            $out[] = 'window.slideshow_custom_settings.layout = "' . $layout . '";';
            $out[] = 'window.slideshow_custom_settings.mode = "' . $transition . '";';

            $out[] = '';
            $out[] = 'jQuery().ready(function() {';
            $out[] = '  jQuery(".slider").bxSlider(window.slideshow_custom_settings);';
            $out[] = '});';

            if ($echo) {
                $out[] = '</script>';
                echo implode("\n", $out);
                return;
            }

            return implode("\n", $out);
        }
    }

    public function fetchStylesUri()
    {
        $theme = get_option('_' . $this->slug . '_horizontalThumbsCSSFile');

        if (!empty($theme) && $this->show) {
            if ($this->show->layout == 'no-thumb') {
                $theme = get_option('_' . $this->slug . '_prevNextCSSFile');
            } elseif ($this->show->layout == 'vertical') {
                $theme = get_option('_' . $this->slug . '_verticalThumbsCSSFile');
            }

            $dir = str_replace('.css', '', $theme);
            $file_path = '/bxslider/themes/' . $dir . '/' . $theme;

            // Check if there's an override in the theme or the parent theme
            if (file_exists(get_stylesheet_directory() . $file_path)) {
                return [
                    'path' => get_stylesheet_directory() . $file_path,
                    'uri' => get_stylesheet_directory_uri() . $file_path,
                ];
            } elseif (file_exists(get_template_directory() . $file_path)) {
                return [
                    'path' => get_template_directory() . $file_path,
                    'uri' => get_template_directory_uri() . $file_path,
                ];
            }

            return [
                'path' => dirname(__FILE__) . $file_path,
                'uri' => plugins_url($file_path, dirname(__FILE__)),
            ];
        }
    }

    public function slideshowShortcode()
    {
        global $wpdb;

        $slides = [];
        $pager_class = str_replace('.', '', get_option('_slideshow_pagerCustom', ''));

        if ($this->show) {
            $slides = SlideshowManager::fetchSlides($this->show->id);
        }

        ob_start();

        require 'views/shortcode.php';

        return ob_get_clean();
    }
}
