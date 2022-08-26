<?php

namespace BCLibCoop;

class SlideshowManager
{
    private static $instance;
    protected $slug = 'slideshow';
    protected $sprite = '';

    public static $media_sources = [
        'local' => 'Your Slide Images',
        'shared' => 'Shared Slide Images',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
    ];

    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        $this->sprite = plugins_url('/assets/imgs/signal-sprite.png', dirname(__FILE__));

        $this->init();
        add_action('admin_enqueue_scripts', [&$this, 'adminEnqueueStylesScripts']);
        add_action('admin_menu', [&$this, 'addSlideshowMenu']);
    }

    public function init()
    {
        add_action('wp_ajax_slideshow-fetch-collection', [&$this, 'fetchCollection']);
        add_action('wp_ajax_slideshow-save-slide-collection', [&$this, 'saveCollectionHandler']);
        add_action('wp_ajax_slideshow-delete-slide-collection', [&$this, 'deleteCollectionHandler']);
    }

    public function adminEnqueueStylesScripts($hook)
    {
        if ($hook === 'site-manager_page_top-slides') {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

            // Get WP included jquery-ui version to match with stylesheet
            $jquery_ui = wp_scripts()->query('jquery-ui-core');

            wp_enqueue_style(
                'jquery-ui-theme',
                'https://ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_ui->ver . '/themes/smoothness/jquery-ui.css',
                [],
                $jquery_ui->ver
            );

            wp_enqueue_style(
                'coop-chosen',
                plugins_url('/assets/css/chosen' . $suffix . '.css', dirname(__FILE__)),
                [],
                '1.8.7'
            );
            wp_enqueue_style(
                'coop-slideshow-manager-admin',
                plugins_url('/assets/css/slideshow-manager-admin.css', dirname(__FILE__))
            );
            wp_enqueue_style('coop-signals', plugins_url('/assets/css/signals.css', dirname(__FILE__)));

            wp_register_script(
                'jquery-chosen',
                plugins_url('/assets/js/chosen.jquery' . $suffix . '.js', dirname(__FILE__)),
                ['jquery'],
                '1.8.7'
            );
            wp_register_script(
                'coop-slideshow-defaults-js',
                plugins_url('/inc/default-settings.js', dirname(__FILE__))
            );
            wp_enqueue_script(
                'coop-slideshow-admin-js',
                plugins_url('/assets/js/slideshow-admin.js', dirname(__FILE__)),
                [
                    'jquery',
                    'jquery-ui-draggable',
                    'jquery-ui-droppable',
                    'jquery-ui-tooltip',
                    'jquery-chosen',
                    'coop-slideshow-defaults-js',
                ]
            );

            $ajax_nonce = wp_create_nonce($this->slug);
            wp_localize_script('coop-slideshow-admin-js', 'coop_slideshow', ['nonce' => $ajax_nonce]);
        }
    }

    public function addSlideshowMenu()
    {
        add_submenu_page(
            'site-manager',
            'Slideshow Manager',
            'Slideshow Manager',
            'manage_local_site',
            'top-slides',
            [&$this, 'slideshowManagerPage']
        );
    }

    public function slideshowManagerPage()
    {
        require 'views/manager.php';
    }

    public static function fetchSlideImages($region = 'shared')
    {
        /**
         * Fetch images with Media Tag: 'slide'
         *
         * This function is not multi-site aware, so blog should be switched
         * before calling this function to select either the share media instance (blog 1)
         * or not switched to use the current blog
         *
         * @param string $region value of slide_region post meta [default empty, BC, MB, or null to not match on region]
         *
         * @return array Returns array of markup-wrapped slide items to be appended to output
         **/
        $args = [
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'order_by' => 'date',
            'tax_query' => [
                [
                    'taxonomy' => 'media_tag',
                    'terms' => 'slide',
                    'field' => 'name',
                ],
            ],
        ];

        if ($region !== 'local') {
            switch_to_blog(1);
            $region = $region === 'shared' ? '' : $region;

            $args['meta_query'] = [
                [
                    'key' => 'slide_region',
                    'compare' => '=',
                    'value' => $region,
                ],
            ];
        }

        $slides = [];
        $get_slides = get_posts($args);

        if (empty($get_slides)) {
            $slides[] = '<div class="slide-no-results"><p>No slides</p></div>'; // we got nothing with the post meta
        } else {
            foreach ($get_slides as $r) {
                $title = get_the_title($r);
                $medium = wp_get_attachment_image_src($r->ID, 'medium');
                $large = wp_get_attachment_image_src($r->ID, 'large');

                $slides[] = sprintf(
                    '<div class="draggable" data-img-id="%d" data-img-caption="%s" '
                    . 'data-tooltip-src="%s" data-tooltip-w="%d" data-tooltip-h="%d">'
                    . '<img id="thumb%d" src="%s" width="%d" height="%d" class="thumb">'
                    . '<p class="caption">%s</p></div>',
                    $r->ID,
                    esc_attr($title),
                    $large[0],
                    $large[1],
                    $large[2],
                    $r->ID,
                    $medium[0],
                    $medium[1],
                    $medium[2],
                    $title
                );
            }
        }

        restore_current_blog();

        return $slides;
    }

    private function slideshowCollectionSelector()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'slideshows';

        // Skip possible old shows without a title
        $res = $wpdb->get_results("SELECT * FROM `$table_name` ORDER BY `title`");

        $out = [];

        $out[] = '<select data-placeholder="... or choose a past slideshow to reload" name="slideshow_select" '
                 . 'id="slideshow-select" class="slideshow-select chosen-select">';

        $out[] = '<option value=""></option>';

        foreach ($res as $r) {
            $r->title = empty($r->title) ? "Unnamed Slideshow {$r->id}" : $r->title;

            $out[] = sprintf(
                '<option value="%d"%s>%s</option>',
                $r->id,
                selected($r->is_active, '1', false),
                wp_unslash($r->title)
            );
        }

        $out[] = '</select>';

        return implode("\n", $out);
    }

    public function targetPagesSelector()
    {
        $pages = get_posts([
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'post_title',
            'order' => 'ASC',
        ]);

        $out = [];
        $out[] = '<select data-placeholder="Link to a post or page..." id="slideshow-page-selector" '
                 . 'name="slideshow_page_selector" class="slideshow-page-selector chosen-select">';
        $out[] = '<option value=""></option>';

        foreach ($pages as $page) {
            $out[] = sprintf(
                '<option value="%s" class="%s" data-permalink="%s">%s</option>',
                $page->ID,
                $page->post_type,
                get_permalink($page),
                get_the_title($page)
            );
        }

        $out[] = '</select>';

        return implode("\n", $out);
    }

    public function createCollection($slideshow_name = '')
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'slideshows';

        $existing_show = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE `title` = %s", $slideshow_name)
        );

        if ($existing_show > 0) {
            return false;
        }

        $wpdb->insert(
            $table_name,
            [
                'title' => $slideshow_name,
                'date' => current_time('mysql'),
            ],
            [
                '%s',
                '%s',
            ]
        );

        return $wpdb->insert_id;
    }

    public function saveCollectionHandler()
    {
        global $wpdb;

        if (check_ajax_referer($this->slug, false, false) === false) {
            wp_send_json([
                'result' => 'failed',
                'feedback' => 'Invalid security token, please reload and try again',
            ]);
        }

        $slideshow_title = sanitize_text_field(wp_unslash($_POST['title']));

        if (array_key_exists('slideshow_id', $_POST)) {
            $slideshow_id = (int) sanitize_text_field($_POST['slideshow_id']);
        }

        $captions = 0;
        if (array_key_exists('captions', $_POST)) {
            $captions = (int) sanitize_text_field($_POST['captions']);
        }

        $is_active = 0;
        if (array_key_exists('is_active', $_POST)) {
            $is_active = (int) sanitize_text_field($_POST['is_active']);
        }

        $layout = sanitize_text_field($_POST['layout']);
        $transition = sanitize_text_field($_POST['transition']);

        // error_log( 'layout: '.$layout .', transition: '.$transition);

        if (empty($slideshow_id)) {
            $slideshow_id = $this->createCollection($slideshow_title);
        }

        if ($slideshow_id === false) {
            wp_send_json([
                'result' => 'failed',
                'feedback' => 'Unable to save new slideshow. Make sure it has a unique name.',
            ]);
        }

        $table_name = $wpdb->prefix . 'slideshows';

        if ($is_active === 1) {
            /* before we are set to the active record */
            /* unmark any currently marked as active */
            $wpdb->update(
                $table_name,
                [
                    'is_active' => 0,
                ],
                [
                    'is_active' => 1,
                ]
            );
        }

        $wpdb->update(
            $table_name,
            [
                'title' => $slideshow_title,
                'layout' => $layout,
                'transition' => $transition,
                'date' => current_time('mysql'),
                'is_active' => $is_active,
                'captions' => $captions,
            ],
            [
                'id' => $slideshow_id,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d'
            ],
            [
                '%d',
            ]
        );

        /**
         * Release all slides currently associated with this slideshow_id
         *
         * We do this to accommodate deletions from the set.
         **/
        $table_name = $wpdb->prefix . 'slideshow_slides';
        $ret = $wpdb->update(
            $table_name,
            [
                'slideshow_id' => 0
            ],
            [
                'slideshow_id' => $slideshow_id
            ],
            [
                '%d'
            ],
            [
                '%d'
            ]
        );
        // error_log( 'Releasing slides: updated '.$ret .' where slideshow_id = '.$slideshow_id);

        /**
         * Build the update/insert statement foreach
         *
         * Iterates the slides collection, builds appropraite query
         * Some slides already exist: update; others are new, insert.
         **/
        $slides = [];

        if (array_key_exists('slides', $_POST)) {
            $slides = $_POST['slides'];
        }

        foreach ($slides as $s) {
            $type = sanitize_text_field($s['type']);
            $slide_id = 0;

            if (array_key_exists('slide_id', $s)) {
                // don't change the slide's id
                $slide_id = (int) sanitize_text_field($s['slide_id']);
            }

            $data = [
                'slideshow_id' => $slideshow_id,
                'text_title' => sanitize_text_field(wp_unslash($s['text_title']))
            ];
            $formats = [
                '%d',
                '%s',
            ];

            if ('image' === $type) {
                $data['post_id'] = (int) sanitize_text_field($s['post_id']);
                $formats[] = '%d';
            } else {  // 'text' === $type
                $data['text_content'] = sanitize_textarea_field(wp_unslash($s['text_content']));
                $formats[] = '%s';
            }

            if (array_key_exists('ordering', $s) && is_numeric(sanitize_text_field($s['ordering']))) {
                $data['ordering'] = (int) sanitize_text_field($s['ordering']);
                $formats[] = '%d';
            }

            // slide_link may have been deleted - always set to empty if not present
            $data['slide_link'] = null;
            $formats[] = '%s';

            if (array_key_exists('slide_link', $s)) {
                if (is_numeric(sanitize_text_field($s['slide_link']))) {
                    $data['slide_link'] = (int) sanitize_text_field($s['slide_link']);
                } else {
                    $data['slide_link'] = esc_url_raw(wp_unslash($s['slide_link']));
                }
            }

            $table_name = $wpdb->prefix . 'slideshow_slides';

            if (!empty($slide_id)) {
                // pre-existing slide - update, do not create
                $wpdb->update(
                    $table_name,
                    $data,
                    [
                        'id' => $slide_id,
                    ],
                    $formats,
                    [
                        '%d',
                    ]
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    $data,
                    $formats
                );
            }
        }

        // Clean up any orphaned slides
        $table_name = $wpdb->prefix . 'slideshow_slides';
        $wpdb->delete($table_name, ['slideshow_id' => 0]);

        wp_send_json([
            'result' => 'success',
            'slideshow_id' => $slideshow_id,
            'feedback' => 'Collection saved',
        ]);
    }

    public static function fetchSlides($slideshow_id, $image_size = null)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'slideshow_slides';
        $slide_rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM `$table_name` WHERE `slideshow_id` = %d ORDER BY `ordering`", $slideshow_id)
        );

        $slides = [];

        foreach ($slide_rows as $s) {
            $slide = [
                'id' => $s->id, // Slide ID
                'slide_link' => $s->slide_link,
                'slide_permalink' => $s->slide_link,
                'text_title' => wp_unslash($s->text_title),
                'ordering' => $s->ordering,
            ];

            // Convert old-style querystring IDs to plain ID
            if (!empty($slide['slide_link'])) {
                $slide['slide_link'] = preg_replace('/^\/\?page=/', '', $slide['slide_link']);
            }

            if (is_numeric($slide['slide_link'])) {
                $slide['slide_permalink'] = get_permalink($slide['slide_link']);
            }

            $slide['slide_permalink'] = esc_url($slide['slide_permalink']);

            if ($s->post_id && $raw_meta = self::fetchImageMeta($s->post_id)) {
                // Image Slide
                $slide['type'] = 'image';
                $slide['post_id'] = $s->post_id; // Image attachment ID
                $slide['meta'] = [];

                if ($image_size) {
                    // Just the meta we need to keep the payload small if a size was specified
                    $slide['meta'] = [
                        'title' => $raw_meta['title'],
                        'src' => $raw_meta['sizes'][$image_size]['src'],
                        'width' => $raw_meta['sizes'][$image_size]['width'],
                        'height' => $raw_meta['sizes'][$image_size]['height'],
                    ];
                } else {
                    // All sizes
                    $slide['meta'] = $raw_meta;
                }
            } elseif (!empty($s->text_content)) {
                // Text Slide
                $slide['type'] = 'text';
                $slide['text_content'] =  wp_unslash($s->text_content);
            }

            // Only add the slide if we successfully determined the type
            if (!empty($slide['type'])) {
                $slides[] = $slide;
            }
        }

        return $slides;
    }

    public function fetchCollection()
    {
        global $wpdb;

        if (check_ajax_referer($this->slug, false, false) === false) {
            wp_send_json([
                'result' => 'failed',
                'feedback' => 'Invalid security token, please reload and try again',
            ]);
        }

        $slideshow_id = empty($_POST['slideshow_id']) ? 0 : (int) sanitize_text_field($_POST['slideshow_id']);

        $table_name = $wpdb->prefix . 'slideshows';
        $show = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_name` WHERE `id` = %d", $slideshow_id));

        if (empty($show)) {
            wp_send_json(['result' => 'none']);
        }

        $slides = self::fetchSlides($slideshow_id, 'medium');

        wp_send_json([
            'slides' => $slides,
            'is_active' => $show->is_active,
            'captions' => $show->captions,
            'layout' => $show->layout,
            'transition' => $show->transition,
        ]);
    }

    public function deleteCollectionHandler()
    {
        global $wpdb;

        if (check_ajax_referer($this->slug, false, false) === false) {
            wp_send_json([
                'result' => 'failed',
                'feedback' => 'Invalid security token, please reload and try again',
            ]);
        }

        $slideshow_id = (int) sanitize_text_field($_POST['slideshow_id']);

        $table_name = $wpdb->prefix . 'slideshow_slides';
        $wpdb->delete($table_name, ['slideshow_id' => $slideshow_id], ['%d']);

        $table_name = $wpdb->prefix . 'slideshows';
        $wpdb->delete($table_name, ['id' => $slideshow_id], ['%d']);

        wp_send_json([
            'result' => 'success',
            'feedback' => 'Slideshow deleted.',
        ]);
    }

    /**
     * Build a simpler data structure for metadata
     *
     * This returns as a nested array
     **/
    public static function fetchImageMeta($post_id = 0, $library = 'local')
    {
        $image_sizes = [
            'thumbnail',
            'medium',
            'drag-slide',
            'full',
        ];

        $post_id = (int) $post_id;

        if (empty($post_id)) {
            return;
        }

        if ($library === 'network') {
            switch_to_blog(1);
        }

        $attachment = get_post($post_id);

        // If we didn't find the image in the current blog, try the shared media blog
        if ((!$attachment || $attachment->post_type !== 'attachment') && !ms_is_switched()) {
            switch_to_blog(1);
            $library = 'network';
            $attachment = get_post($post_id);
        }

        // If there was still no image, return
        if (!$attachment || $attachment->post_type !== 'attachment') {
            restore_current_blog();
            return [];
        }

        $postmeta = [
            'title' => get_the_title($attachment),
            'library' => $library,
            'sizes' => [],
        ];

        foreach ($image_sizes as $image_size) {
            $img_meta = wp_get_attachment_image_src($attachment->ID, $image_size);

            $postmeta['sizes'][$image_size] = [
                'src' => $img_meta[0],
                'width' => $img_meta[1],
                'height' => $img_meta[2],
            ];
        }

        // Always try and restore, does no harm if we never switched
        restore_current_blog();

        return $postmeta;
    }
}
