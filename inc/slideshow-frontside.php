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
        $this->show = $wpdb->get_row("SELECT * FROM `$table_name` WHERE `is_active` = 1 ORDER BY `date` DESC");

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
            /* Get theme-specific CSS from plugin */
            wp_enqueue_style('coop-slideshow-theme', $this->fetchStylesUri(), [], null);

            /* Script to resize text slide based on screen and layout width */
            wp_enqueue_script(
                'bxslider-text-shim',
                plugins_url('/bxslider/plugins/text-slide-shim.js', dirname(__FILE__)),
                ['jquery'],
                null,
                true
            );

            wp_enqueue_script(
                'bxslider-jquery-easing',
                plugins_url('/bxslider/plugins/jquery.easing.1.3.js', dirname(__FILE__)),
                ['jquery'],
                null,
                true
            );

            wp_enqueue_script(
                'bxslider-jquery-fitvids',
                plugins_url('/bxslider/plugins/jquery.fitvids.js', dirname(__FILE__)),
                ['jquery'],
                null,
                true
            );

            wp_enqueue_script(
                'bxslider',
                plugins_url('/bxslider/jquery.bxslider.min.js', dirname(__FILE__)),
                ['jquery', 'bxslider-text-shim', 'bxslider-jquery-easing', 'bxslider-jquery-fitvids'],
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
                return get_stylesheet_directory_uri() . $file_path;
            } elseif (file_exists(get_template_directory() . $file_path)) {
                return get_template_directory_uri() . $file_path;
            }

            return plugins_url($file_path, dirname(__FILE__));
        }
    }

    public function slideshowShortcode()
    {
        global $wpdb;

        $out = [];
        $slide_ml = [];
        $pager_ml = [];

        if ($this->show) {
            $out[] = '<div class="hero row ' . $this->show->layout . '" role="banner">';
            $out[] = '<div id="slider" class="slider">';

            if ($this->show->layout !== 'no-thumb') {
                $pager_class = get_option('_slideshow_pagerCustom');
                $pager_class = str_replace('.', '', $pager_class);
                $pager_ml[] = '<div class="row ' . $pager_class . ' ' . $this->show->layout . '">';
            }

            $id = $this->show->id;
            $table_name =  $wpdb->prefix . 'slideshow_slides';
            $slides = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM `$table_name` WHERE `slideshow_id` = %d ORDER BY `ordering`", $id)
            );
            foreach ($slides as $slide) {
                if ($slide->post_id != null) {
                    $meta = SlideShowManager::fetchImageMeta($slide->post_id);

                    if ($meta) {
                        $this->buildImageSlide($this->show, $slide, $meta, $slide_ml, $pager_ml);
                    }
                } else {
                    $this->buildTextSlide($this->show, $slide, $slide_ml, $pager_ml);
                }
            }

            if ($this->show->layout !== 'no-thumb') {
                $pager_ml[] = '</div><!-- end of pager -->';
            }

            $slide_ml[] = '</div><!-- #slider.row.slider -->';

            $out = array_merge($out, $slide_ml, $pager_ml);
            $out[] = '</div><!-- .hero.row -->';
        } else {
            $out[] = '<!-- No Slideshow Found -->';
        }

        return implode("\n", $out);
    }

    private function buildImageSlide($show, $slide, $meta, &$slide_ml, &$pager_ml)
    {
        $slide_ml[] = '<div class="slide image">';

        if ($slide->slide_link != null) {
            $slide_ml[] = '<a href="' . $slide->slide_link . '">';
        }

        $url = $meta['folder'] . $meta['large']['file'];
        $title = htmlspecialchars(stripslashes($slide->text_title));

        $slide_ml[] = '<img src="' . $url . '"  alt="' . $title . '" title="' . $title . '" >';

        if ($slide->slide_link != null) {
            $slide_ml[] = '</a>';
        }

        $slide_ml[] = '</div><!-- .slide.image -->';

        if ($show->layout !== 'no-thumb') {
            $url = $meta['folder'] . $meta['thumb']['file'];

            $pager_ml[] = '<div class="pager-box slide-index-' . $slide->ordering . '">';
            $pager_ml[] = '<a href="" data-slide-index="' . $slide->ordering . '">';
            $pager_ml[] = '<div class="thumb image">';
            $pager_ml[] = '<img class="pager-thumb" src="' . $url . '" alt="' . $title . '" >';
            $pager_ml[] = '</div></a></div><!-- .pager-box -->';
        }
    }

    private function buildTextSlide($show, $slide, &$slide_ml, &$pager_ml)
    {
        $slide_ml[] = '<div class="slide text">';

        if ($slide->slide_link != null) {
            $slide_ml[] = '<a href="' . $slide->slide_link . '">';
        }

        $title = stripslashes($slide->text_title);
        $content = stripslashes($slide->text_content);
        $slide_ml[] = '<h2>' . $title . '</h2><p>' . $content . '</p>';

        if ($slide->slide_link != null) {
            $slide_ml[] = '</a>';
        }

        $slide_ml[] = '</div><!-- .slide.text -->';

        if ($show->layout !== 'no-thumb') {
            $pager_ml[] = '<div class="pager-box slide-index-' . $slide->ordering . '">';
            $pager_ml[] = '<a href="" data-slide-index="' . $slide->ordering . '">';
            $pager_ml[] = '<div class="thumb text">';
            $pager_ml[] = '<div class="pager-thumb text-thumb">T</div>';
            $pager_ml[] = '</div></a></div><!-- .pager-box -->';
        }
    }
}
