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
        $slides = [];
        $text_thumb = plugins_url('/assets/imgs/info-thumb.png', dirname(__FILE__));

        if ($this->show) {
            $slides = SlideshowManager::fetchSlides($this->show->id);
        }

        // TODO: Figure out if we're going to keep any "default options" around
        // or if we should just move pause/delay/autoplay to a per-show option

        $flickity_options = json_encode([
            'autoPlay' => (int) get_option('_slideshow_pause', '4000'),
            'wrapAround' => true,
            'pageDots' => ($this->show->layout === 'no-thumb'),
            'fade' => ($this->show->transition === 'fade' ? true : false),
            // 'watchCSS' => true,
        ]);

        $flickity_pager_options = json_encode([
            'asNavFor' => '.hero-carousel',
            'contain' => true,
            'pageDots' => false,
            'prevNextButtons' => false,
            'draggable' => false,
        ]);

        ob_start();
        require 'views/shortcode.php';
        return ob_get_clean();
    }
}
