<?php defined('ABSPATH') || die(-1);

/**
 * Plugin Name: Slide Custom Post Type
 * Description: Container type for related content.  NETWORK ACTIVATE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.1.0
 *
 * @package   Slideshow
 * @copyright BC Libraries Coop 2013
 **/

if (!class_exists('SlideCPT')) :
  class SlideCPT
  {
    private $slug = 'slide';

    public function __construct()
    {
      add_action('init', [&$this, '_init']);
      add_action('init', [&$this, 'create_slide_post_type']);
    }

    public function _init()
    {
      // error_log( __FUNCTION__ );
    }

    function create_slide_post_type()
    {
      // Register Custom Post Type

      $labels = [
        'name'                => 'Slides',
        'singular_name'       => 'Slide',
        'menu_name'           => 'Slides',
        'parent_item_colon'   => 'Parent Slide:',
        'all_items'           => 'All Slides',
        'view_item'           => 'View Slide',
        'add_new_item'        => 'Add New Slide',
        'add_new'             => 'New Slide',
        'edit_item'           => 'Edit Slide',
        'update_item'         => 'Update Slide',
        'search_items'        => 'Search Slides',
        'not_found'           => 'No slides found',
        'not_found_in_trash'  => 'No slides found in Trash',
      ];

      $args = [
        'label'               => 'slide',
        'description'         => 'Slide information pages',
        'labels'              => $labels,
        'supports'            => [],
        'taxonomies'          => [],
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => false,
        'menu_position'       => '4',
        'menu_icon'           => '',
        'can_export'          => false,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'capability_type'     => 'page',
      ];

      register_post_type($this->slug, $args);
    }
  }

  global $slidecpt;
  if (!isset($slidecpt)) {
    $slidecpt = new SlideCPT();
  }
endif; /* ! class_exists */
