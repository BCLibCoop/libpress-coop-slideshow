<?php

namespace BCLibCoop;

class SlideshowManager
{
    private static $instance;
    protected $slug = 'slideshow';
    protected $sprite = '';

    private static $media_sources = [
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
        add_action('wp_ajax_slideshow-fetch-img-meta', [&$this, 'fetchImageMetaCallback']);
        add_action('wp_ajax_slideshow-fetch-collection', [&$this, 'fetchCollection']);
        add_action('wp_ajax_slideshow-save-slide-collection', [&$this, 'saveCollectionHandler']);
        add_action('wp_ajax_slideshow-delete-slide-collection', [&$this, 'deleteCollectionHandler']);
    }

    public function adminEnqueueStylesScripts($hook)
    {
        if ('site-manager_page_top-slides' == $hook || 'site-manager_page_slides-manager' == $hook) {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

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
            wp_enqueue_style(
                'coop-slideshow-defaults-admin',
                plugins_url('/assets/css/slideshow-defaults-admin.css', dirname(__FILE__))
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
                    'jquery-chosen',
                    'coop-slideshow-defaults-js',
                ]
            );
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

    public static function fetchSlides($region = 'shared')
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
                $title = $r->post_title;

                $medium = wp_get_attachment_image_src($r->ID, 'medium');
                $dragslide = wp_get_attachment_image_src($r->ID, 'drag-slide');

                $slides[] = sprintf(
                    '<div class="draggable" data-img-id="%d" data-img-caption="%s"><img id="thumb%d" src="%s" '
                    . 'width="%d" height="%d" class="thumb"><p class="caption">%s</p>',
                    $r->ID,
                    $title,
                    $r->ID,
                    $medium[0],
                    $medium[1],
                    $medium[2],
                    $title
                );
                $slides[] = sprintf(
                    '<img id="slotview%d" src="%s" width="%d" height="%d" class="slotview"></div>',
                    $r->ID,
                    $dragslide[0],
                    $dragslide[1],
                    $dragslide[2]
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
        $res = $wpdb->get_results("SELECT * FROM `$table_name` WHERE `title` != '' ORDER BY `title`");

        $out = [];

        $out[] = '<select data-placeholder="... or choose a past slideshow to reload" name="slideshow_select" '
                 . 'id="slideshow-select" class="slideshow-select chosen-select">';

        $out[] = '<option value=""></option>';

        foreach ($res as $r) {
            if ($r->is_active === '1') {
                $out[] = '<option value="' . $r->id . '" selected="selected">' . $r->title . '</option>';
            } else {
                $out[] = '<option value="' . $r->id . '" >' . $r->title . '</option>';
            }
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
            $out[] = '<option value="' . $page->ID . '" class="' . $page->post_type . '" data-permalink="' . get_permalink($page) . '">'
                        . $page->post_title
                        . '</option>';
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

        $slideshow_title = sanitize_text_field($_POST['title']);

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
            ];
            $formats = [
                '%d'
            ];

            if ('image' === $type) {
                $data['post_id'] = (int) sanitize_text_field($s['post_id']);
                $formats[] = '%d';

                $data['text_title'] = sanitize_text_field($s['text_title']);
                $formats[] = '%s';
            } else {  // 'text' === $type
                $data['text_title'] = sanitize_text_field($s['text_title']);
                $formats[] = '%s';

                $data['text_content'] = sanitize_textarea_field($s['text_content']);
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
                    $data['slide_link'] = esc_url_raw($s['slide_link']);
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

    public function fetchCollection()
    {
        global $wpdb;

        $slideshow_id = empty($_POST['slideshow_id']) ? '' : (int) sanitize_text_field($_POST['slideshow_id']);

        if (empty($slideshow_id)) {
            wp_send_json(['result' => 'none']);
        }

        $table_name = $wpdb->prefix . 'slideshows';
        $show = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table_name` WHERE `id` = %d", $slideshow_id));

        $table_name = $wpdb->prefix . 'slideshow_slides';
        $slide_rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM `$table_name` WHERE `slideshow_id` = %d ORDER BY `ordering`", $slideshow_id)
        );

        $image_size = 'medium';
        $slides = [];

        foreach ($slide_rows as $s) {
            $slide = [
                'id' => $s->id, // Slide ID
                'slide_link' => $s->slide_link,
                'slide_permalink' => $s->slide_link,
                'text_title' => $s->text_title,
                'ordering' => $s->ordering,
            ];

            // Convert old-style querystring IDs to plain ID
            if (!empty($slide['slide_link'])) {
                $slide['slide_link'] = preg_replace('/^\/\?page=/', '', $slide['slide_link']);
            }

            if (is_numeric($slide['slide_link'])) {
                $slide['slide_permalink'] = get_permalink($slide['slide_link']);
            }

            if ($s->post_id) {
                // Image Slide
                $slide['post_id'] = $s->post_id; // Image attachment ID
                $slide['meta'] = [];

                if ($raw_meta = self::fetchImageMeta($s->post_id)) {
                    // Just the meta we need to keep the payload small
                    $slide['meta'] = [
                        'title' => $raw_meta['title'],
                        'src' => $raw_meta['folder'] . $raw_meta[$image_size]['file'],
                        'width' => $raw_meta[$image_size]['width'],
                        'height' => $raw_meta[$image_size]['height'],
                    ];
                }
            } else {
                // Text Slide
                $slide['text_content'] = $s->text_content;
            }

            $slides[] = $slide;
        }

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
    public static function fetchImageMeta($post_id = null, $source = 'local')
    {
        if ($post_id === null) {
            $post_id = sanitize_text_field($_POST['post_id']);
        }

        $post_id = (int) $post_id;

        if ($source === 'network') {
            switch_to_blog(1);
        }

        $attachment = get_post($post_id);

        // If we didn't find the image in the current blog, try the shared media blog
        if ((!$attachment || $attachment->post_type !== 'attachment') && !ms_is_switched()) {
            switch_to_blog(1);
            $source = 'network';
            $attachment = get_post($post_id);
        }

        // If there was still no image, return
        if (!$attachment || $attachment->post_type !== 'attachment') {
            restore_current_blog();
            return [];
        }

        $meta = wp_get_attachment_metadata($attachment->ID);
        $img_url = wp_get_attachment_url($attachment->ID);
        $img_url_basename = wp_basename($img_url);
        $folder = trailingslashit(str_replace($img_url_basename, '', $img_url));

        $postmeta = [
            'title' => $attachment->post_title,
            'source' => $source,
            'folder' => $folder,
            'file' => $img_url_basename,
            'width' => $meta['width'],
            'height' => $meta['height'],
        ];

        $postmeta['thumb'] = [
            'file' => $meta['sizes']['thumbnail']['file'],
            'width' => $meta['sizes']['thumbnail']['width'],
            'height' => $meta['sizes']['thumbnail']['height'],
        ];

        $postmeta['medium'] = [
            'file' => $meta['sizes']['medium']['file'],
            'width' => $meta['sizes']['medium']['width'],
            'height' => $meta['sizes']['medium']['height'],
        ];

        $postmeta['large'] = [
            'file' => $img_url_basename,
            'width' => $meta['width'],
            'height' => $meta['height'],
        ];

        $postmeta['drag-slide'] = [
            'file' => $meta['sizes']['drag-slide']['file'],
            'width' => $meta['sizes']['drag-slide']['width'],
            'height' => $meta['sizes']['drag-slide']['height'],
        ];

        // Always try and restore, does no harm if we never switched
        restore_current_blog();

        return $postmeta;
    }

    /**
     * Fetch image meta callback
     * wraps the call to get img meta data
     * returns it as JSON
     **/
    public function fetchImageMetaCallback()
    {
        $post_id = (int) sanitize_text_field($_POST['post_id']);

        $meta = self::fetchImageMeta($post_id);

        if ($meta) {
            wp_send_json([
                'result' => 'success',
                'meta' => $meta,
            ]);
        }

        wp_send_json([
            'result' => 'failed',
        ]);
    }
}
