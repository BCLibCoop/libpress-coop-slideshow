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

class SlideshowFrontend
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
        $slideshow_id = $wpdb->get_var("SELECT `id` FROM $table_name ORDER BY `is_active` DESC, `date` DESC LIMIT 1");

        if ($slideshow_id) {
            $this->show = (object) SlideshowAdmin::fetchShow((int) $slideshow_id, null);
        }

        add_shortcode('coop-slideshow', [$this, 'slideshowShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
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

            /**
             * All Coop plugins will include their own copy of flickity, but
             * only the first one actually enqued should be needed/registered.
             * Assuming we keep versions in sync, this shouldn't be an issue.
             */

            /* flickity */
            wp_enqueue_script(
                'flickity',
                plugins_url('/assets/js/flickity.pkgd' . $suffix . '.js', dirname(__FILE__)),
                [
                    'jquery',
                ],
                '2.3.0',
                true
            );

            wp_enqueue_script(
                'flickity-fade',
                plugins_url('/assets/js/flickity-fade.js', dirname(__FILE__)),
                [
                    'flickity',
                ],
                '1.0.0',
                true
            );

            wp_enqueue_script(
                'fitty',
                plugins_url('/assets/js/fitty' . $suffix . '.js', dirname(__FILE__)),
                [],
                '2.3.6',
                true
            );

            wp_enqueue_script(
                'coop-slideshow',
                plugins_url('/assets/js/coop-slideshow.js', dirname(__FILE__)),
                [
                    'flickity',
                    'fitty',
                ],
                filemtime(dirname(__FILE__) . '/../assets/js/coop-slideshow.js'),
                true
            );

            wp_register_style(
                'flickity',
                plugins_url('/assets/css/flickity' . $suffix . '.css', dirname(__FILE__)),
                [],
                '2.3.0'
            );

            wp_register_style(
                'flickity-fade',
                plugins_url('/assets/css/flickity-fade.css', dirname(__FILE__)),
                ['flickity'],
                '1.0.0'
            );

            /* Global Slideshow Styling */
            wp_enqueue_style(
                'coop-slideshow',
                plugins_url('/assets/css/coop-slideshow.css', dirname(__FILE__)),
                [
                    'flickity',
                    'flickity-fade',
                ],
                filemtime(dirname(__FILE__) . '/../assets/css/coop-slideshow.css')
            );
        }
    }

    public function slideshowShortcode()
    {
        if ($this->show) {
            $has_text_slides = in_array('text', array_column($this->show->slides, 'type'));

            $show_options = [
                'wrapAround' => true,
                // 'pageDots' => ($this->show->layout === 'no-thumb'),
                'pageDots' => false,
                'fade' => ($this->show->transition === 'fade'),
                'setGallerySize' => false,
            ];

            switch ($this->show->options['coop_pause'] ?? '') {
                case 'short':
                    $show_options['autoPlay'] = 1500;
                    break;
                case 'long':
                    $show_options['autoPlay'] = 9000;
                    break;
                case 'medium':
                default:
                    $show_options['autoPlay'] = 4000;
            }

            switch ($this->show->options['coop_transition_time'] ?? '') {
                case 'short':
                    $show_options['selectedAttraction'] = 0.2;
                    $show_options['friction'] = 0.8;
                    break;
                case 'long':
                    $show_options['selectedAttraction'] = 0.01;
                    $show_options['friction'] = 0.28;
                    break;
                case 'medium':
                default:
                    $show_options['selectedAttraction'] = 0.025;
                    $show_options['friction'] = 0.28;
            }

            // Strip out an `coop_` keys from the extended options
            $db_options = array_filter($this->show->options, function ($option) {
                return strpos($option, 'coop_') !== 0;
            }, ARRAY_FILTER_USE_KEY);

            // Merge defaults with anything still in the db
            $flickity_options = wp_parse_args($db_options, $show_options);

            // Not using wp_localize_script, as it casts everything to a string
            wp_add_inline_script(
                'coop-slideshow',
                'var coopSlideshowOptions = ' . json_encode($flickity_options),
                'before'
            );
        }

        ob_start();
        require 'views/shortcode.php';
        return ob_get_clean();
    }
}
