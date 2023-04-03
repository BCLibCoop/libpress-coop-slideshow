<?php

namespace BCLibCoop;

class SlideshowAdmin
{
    private static $instance;
    protected $slug = 'slideshow';
    protected $sprite = '';

    private static $region_metakey = 'slide_region';

    public static $media_sources = [
        'local' => 'Your Slide Images',
        'shared' => 'Shared Slide Images',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
    ];

    public static $show_options = [
        'is_active' => [
            'label' => 'This is the active slideshow',
            'options' => [
                [
                    'value' => true,
                    'label' => 'This is the active slideshow',
                ],
            ],
            'extended' => false,
            'hide' => true,
        ],
        'captions' => [
            'label' => 'Display Captions',
            'options' => [
                [
                    'value' => true,
                    'label' => 'Enable caption display for slideshow',
                ],
            ],
            'extended' => false,
        ],
        'layout' => [
            'label' => 'Slideshow Layout',
            'options' => [
                [
                    'value' => 'no-thumb',
                    'label' => 'No Thumbnails',
                    'description' => 'Previous/Next arrows',
                    'image' => 'NoThumbnails.png',
                    'default' => true,
                ],
                [
                    'value' => 'horizontal',
                    'label' => 'Horizontal Thumbnails',
                    'description' => 'Clickable thumbnails displayed horizontally below the slideshow',
                    'image' => 'HorizontalThumbnails.png',
                ],
            ],
            'extended' => false,
        ],
        'transition' => [
            'label' => 'Transitions',
            'options' => [
                [
                    'value' => 'horizontal',
                    'label' => 'Slide Horizontal',
                    'description' => 'Slides enter from the right and exit to the left',
                    'image' => 'HorizontalSlide.png',
                    'default' => true,
                ],
                [
                    'value' => 'fade',
                    'label' => 'Cross-fade',
                    'description' => 'One slide dissolves into the next',
                    'image' => 'Fade.png',
                ],
            ],
            'extended' => false,
        ],
        'coop_pause' => [
            'label' => 'Pause Time',
            'description' => 'How long long should the show pause on each slide',
            'options' => [
                [
                    'value' => 'short',
                    'label' => 'Short',
                    'description' => '',
                    'image' => '',
                    'default' => false,
                ],
                [
                    'value' => 'medium',
                    'label' => 'Medium',
                    'description' => '',
                    'image' => '',
                    'default' => true,
                ],
                [
                    'value' => 'long',
                    'label' => 'Long',
                    'description' => '',
                    'image' => '',
                    'default' => false,
                ],
            ],
            'extended' => true,
        ],
        'coop_transition_time' => [
            'label' => 'Transition Time',
            'description' => 'How quickly should slides transition from one to the next',
            'options' => [
                [
                    'value' => 'short',
                    'label' => 'Short',
                    'description' => '',
                    'image' => '',
                    'default' => false,
                ],
                [
                    'value' => 'medium',
                    'label' => 'Medium',
                    'description' => '',
                    'image' => '',
                    'default' => true,
                ],
                [
                    'value' => 'long',
                    'label' => 'Long',
                    'description' => '',
                    'image' => '',
                    'default' => false,
                ],
            ],
            'extended' => true,
        ],
    ];

    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        $this->sprite = plugins_url('/assets/imgs/signal-sprite.png', dirname(__FILE__));

        $this->init();
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueStylesScripts']);
        add_action('admin_menu', [$this, 'addSlideshowMenu']);
    }

    public function init()
    {
        add_filter('attachment_fields_to_edit', [&$this, 'addRegionField'], 10, 2);
        add_filter('attachment_fields_to_save', [&$this, 'regionFieldSave'], 10, 2);

        add_action('wp_ajax_slideshow-fetch-collection', [$this, 'fetchShowAjax']);
        add_action('wp_ajax_slideshow-save-slide-collection', [$this, 'saveCollectionHandler']);
        add_action('wp_ajax_slideshow-delete-slide-collection', [$this, 'deleteCollectionHandler']);
    }

    public function adminEnqueueStylesScripts($hook)
    {
        if ($hook === 'site-manager_page_top-slides') {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

            // Get WP included jquery-ui version to match with stylesheet
            $jqui_ver = wp_scripts()->query('jquery-ui-core')->ver;

            wp_enqueue_style(
                'jquery-ui-theme',
                'https://ajax.googleapis.com/ajax/libs/jqueryui/' . $jqui_ver . '/themes/smoothness/jquery-ui.css',
                [],
                null
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
            wp_enqueue_script(
                'coop-slideshow-admin-js',
                plugins_url('/assets/js/slideshow-admin.js', dirname(__FILE__)),
                [
                    'jquery',
                    'jquery-ui-draggable',
                    'jquery-ui-droppable',
                    'jquery-ui-tooltip',
                    'jquery-chosen',
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
            [$this, 'slideshowAdminPage']
        );
    }

    public function slideshowAdminPage()
    {
        require 'views/manager.php';
    }

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
    public static function fetchSlideImages($region = 'shared')
    {
        $slides = [];

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
                    'key' => static::$region_metakey,
                    'compare' => '=',
                    'value' => $region,
                ],
            ];

            // If the region is blank, aka "shared" without a province, also
            // allow for the meta to be entirely unset
            if ($region === '') {
                $args['meta_query']['relation'] = 'OR';
                $args['meta_query'][] = [
                    'key' => static::$region_metakey,
                    'compare' => 'NOT EXISTS',
                ];
            }
        }

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

        // Skip possible old shows without a title
        $res = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}slideshows` ORDER BY `title`");

        $out = [];

        $out[] = '<select data-placeholder="... or choose a past slideshow to reload" name="slideshow_select" '
                 . 'id="slideshow-select" class="slideshow-select chosen-select">';

        $out[] = '<option value=""></option>';

        foreach ($res as $r) {
            $r->title = empty($r->title) ? "Unnamed Slideshow {$r->id}" : $r->title;

            $out[] = sprintf(
                '<option value="%d"%s>%s</option>',
                $r->id,
                selected($r->is_active, 1, false),
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

        $existing_show = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}slideshows` WHERE `title` = %s", $slideshow_name)
        );

        if ($existing_show > 0) {
            return false;
        }

        $wpdb->insert(
            $wpdb->prefix . 'slideshows',
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

        // Unslash everything up front
        $post_data = wp_unslash($_POST);

        $show_settings = [
            'title' => sanitize_text_field($post_data['title']),
            'layout' => 'no-thumb',
            'transition' => 'horizontal',
            'date' => current_time('mysql'),
            'is_active' => 0,
            'captions' => 0,
            'options' => [],
        ];

        $slideshow_id = (int) sanitize_text_field($post_data['slideshow_id'] ?? 0);

        if (empty($slideshow_id)) {
            $slideshow_id = $this->createCollection($show_settings['title']);
        }

        if ($slideshow_id === false) {
            wp_send_json([
                'result' => 'failed',
                'feedback' => 'Unable to save new slideshow. Make sure it has a unique name.',
            ]);
        }

        // Sanitize data
        foreach (self::$show_options as $option_name => $option_details) {
            // Using empty, as all of these should be string values
            if (!empty($post_data[$option_name])) {
                if (count($option_details['options']) === 1) {
                    $value = filter_var(
                        $post_data[$option_name],
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    );
                } else {
                    $value = sanitize_text_field($post_data[$option_name]);
                }

                if (!$option_details['extended']) {
                    $show_settings[$option_name] = $value;
                } else {
                    $show_settings['options'][$option_name] = $value;
                }
            }
        }

        // Collection default/allowed extended options
        $default_extended_options = array_reduce(array_keys(self::$show_options), function ($carry, $option) {
            if (self::$show_options[$option]['extended']) {
                $default_key = array_search(true, array_column(self::$show_options[$option]['options'], 'default'));
                $carry[$option] = self::$show_options[$option]['options'][$default_key]['value'];
            }

            return $carry;
        }, []);

        // Only allow known values in the form
        $show_settings['options'] = shortcode_atts($default_extended_options, $show_settings['options']);

        // Get existing show to merge in options
        $show = self::fetchShow($slideshow_id);

        // Merge in existing extended options. Done after gating options from the form so as
        // to preserve options manually added to the array
        $show_settings['options'] = wp_parse_args($show_settings['options'], $show['options']);

        // Finally, serialize the array for storage in the DB
        $show_settings['options'] = maybe_serialize($show_settings['options']);

        if ($show_settings['is_active']) {
            /* before we are set to the active record */
            /* unmark any currently marked as active */
            $wpdb->update(
                $wpdb->prefix . 'slideshows',
                [
                    'is_active' => 0,
                ],
                [
                    'is_active' => 1,
                ]
            );
        }

        $wpdb->update(
            $wpdb->prefix . 'slideshows',
            $show_settings,
            [
                'id' => $slideshow_id,
            ],
            [
                '%s', // title
                '%s', // layout
                '%s', // transition
                '%s', // date
                '%d', // is_active
                '%d', // captions
                '%s', // options
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
        $ret = $wpdb->update(
            $wpdb->prefix . 'slideshow_slides',
            [
                'slideshow_id' => null,
            ],
            [
                'slideshow_id' => $slideshow_id,
            ],
            [
                '%d',
            ],
            [
                '%d',
            ]
        );
        // error_log( 'Releasing slides: updated '.$ret .' where slideshow_id = '.$slideshow_id);

        /**
         * Build the update/insert statement foreach
         *
         * Iterates the slides collection, builds appropraite query
         * Some slides already exist: update; others are new, insert.
         **/
        $slides = $post_data['slides'] ?? [];

        foreach ($slides as $s) {
            $type = sanitize_text_field($s['type']) === 'image' ? 'image' : 'text';
            $slide_id = (int) sanitize_text_field($s['slide_id'] ?? 0);

            $data = [
                'slideshow_id' => $slideshow_id,
                'text_title' => sanitize_text_field($s['text_title'] ?? ''),
                'ordering' => (int) sanitize_text_field($s['ordering'] ?? 0),
                'slide_link' => null, // slide_link may have been deleted - always set to empty if not present
            ];
            $formats = [
                '%d',
                '%s',
                '%d',
                '%s',
            ];

            if ('image' === $type) {
                $data['post_id'] = (int) sanitize_text_field($s['post_id'] ?? 0);
                $formats[] = '%d';
            } else {
                $data['text_content'] = sanitize_textarea_field($s['text_content'] ?? '');
                $formats[] = '%s';
            }

            if (is_numeric(sanitize_text_field($s['slide_link'] ?? ''))) {
                $data['slide_link'] = (int) sanitize_text_field($s['slide_link'] ?? 0);
            } else {
                $data['slide_link'] = esc_url_raw($s['slide_link'] ?? '');
            }

            if (!empty($slide_id)) {
                // pre-existing slide - update, do not create
                $wpdb->update(
                    $wpdb->prefix . 'slideshow_slides',
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
                    $wpdb->prefix . 'slideshow_slides',
                    $data,
                    $formats
                );
            }
        }

        // Clean up any orphaned slides
        $wpdb->query("DELETE FROM `{$wpdb->prefix}slideshow_slides` WHERE `slideshow_id` IS NULL");

        wp_send_json([
            'result' => 'success',
            'slideshow_id' => $slideshow_id,
            'feedback' => 'Collection saved',
        ]);
    }

    public static function fetchSlides($slideshow_id, $image_size = null)
    {
        global $wpdb;

        $slide_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}slideshow_slides` WHERE `slideshow_id` = %d ORDER BY `ordering`",
                $slideshow_id
            )
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

    public static function fetchShow($slideshow_id, $image_size = null)
    {
        global $wpdb;

        $show = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}slideshows` WHERE `id` = %d",
            $slideshow_id
        ));

        if (empty($show)) {
            return null;
        }

        return [
            'id' => (int) $show->id,
            'title' => $show->title,
            'layout' => $show->layout,
            'transition' => $show->transition,
            'is_active' => (int) $show->is_active,
            'captions' => (int) $show->captions,
            'options' => maybe_unserialize($show->options ?? []),
            'slides' => self::fetchSlides($slideshow_id, $image_size),
        ];
    }

    public function fetchShowAjax()
    {
        if (check_ajax_referer($this->slug, false, false) === false) {
            wp_send_json([
                'result' => 'failed',
                'feedback' => 'Invalid security token, please reload and try again',
            ]);
        }

        $slideshow_id = empty($_POST['slideshow_id']) ? 0 : (int) sanitize_text_field($_POST['slideshow_id']);

        $show = self::fetchShow($slideshow_id, 'medium');

        if (empty($show)) {
            wp_send_json(['result' => 'none']);
        }

        wp_send_json($show);
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

        $wpdb->delete($wpdb->prefix . 'slideshow_slides', ['slideshow_id' => $slideshow_id], ['%d']);
        $wpdb->delete($wpdb->prefix . 'slideshows', ['id' => $slideshow_id], ['%d']);

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

    /**
     * Set & saver defaults for custom attachment field slide_region
     * for Shared Media site (network root) only
     **/

    public function addRegionField($form_fields, $post)
    {
        if (get_current_blog_id() === 1) {
            $provinces = array_merge(['' => ''], static::$media_sources);
            unset($provinces['local'], $provinces['shared']);

            $selected = get_post_meta($post->ID, static::$region_metakey, true);

            $inputname = "attachments[{$post->ID}][" . static::$region_metakey . "]";

            $form_fields[static::$region_metakey] = [
                'label' => 'Slide Region',
                'input' => 'html',
                'html' => '<select name="' . $inputname . '" id="' . $inputname . '">',
                'helps' => 'Which province is this slide for?',
            ];

            foreach ($provinces as $slug => $province) {
                $form_fields[static::$region_metakey]['html'] .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    $slug,
                    selected($selected, $slug, false),
                    $province
                );
            }

            $form_fields[static::$region_metakey]['html'] .= '</select>';
        }

        return $form_fields;
    }

    public function regionFieldSave($post, $attachment)
    {
        $slide_region = sanitize_text_field(trim($attachment[static::$region_metakey]));

        if (in_array($slide_region, array_keys(static::$media_sources))) {
            // Update the region if it's allowed
            update_post_meta($post['ID'], static::$region_metakey, $slide_region);
        } elseif (empty($slide_region)) {
            // Delete the metakey if it's empty
            delete_post_meta($post['ID'], static::$region_metakey);
        }

        return $post;
    }
}
