<?php defined('ABSPATH') || die(-1);

/**
 * @package Slideshow - Admin wrapper
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Slideshow Admin 
 * Description: Slideshow setup configurator. MUST USE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Author URI: http://roaringsky.ca/plugins/coop-slideshow/
 * Version: 0.2.0
 **/
 
 
if ( ! class_exists( 'SlideshowAdmin' )) :

class SlideshowAdmin {

	var $slug = 'slideshow';
	
	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
	
		if( is_admin() ) {
		
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles_scripts' ));
			
			add_action( 'admin_menu', array( &$this,'add_slideshow_menu' ));
			
			// conditionally ensures that the slideshow table is present
			add_action( 'wp_loaded', array( &$this, 'slideshow_create_db_table_handler'));
		}
		else {
			add_action( 'wp_enqueue_scripts', array( &$this, 'frontside_enqueue_styles_scripts' ));
		}	
	}
	
	public function frontside_enqueue_styles_scripts() {
	
		global $slideshow_defaults;
	
		// echos the current slideshow settings file into JS, frontside
		$slideshow_defaults::slideshow_defaults_publish_config();
	}
	
	public function admin_enqueue_styles_scripts($hook) {
	
	//	error_log($hook);
	
		if( 'site-manager_page_top-slides' == $hook || 'site-manager_page_slides-manager' == $hook ) {
		
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-droppable' );
			
			wp_register_style( 'coop-chosen', plugins_url( '/css/chosen.min.css', __FILE__ ), false );
			wp_enqueue_style( 'coop-chosen' );
			
			wp_register_script( 'coop-chosen-jq-min-js', plugins_url( '/js/chosen.jquery.min.js',__FILE__));
			wp_enqueue_script( 'coop-chosen-jq-min-js' );
			
			wp_register_script( 'coop-slideshow-defaults-js', plugins_url( '/inc/default-settings.js',__FILE__));
			wp_enqueue_script( 'coop-slideshow-defaults-js' );	//  template of bxSlider's default values.
			
			wp_register_style( 'coop-slideshow-settings-admin', plugins_url( '/css/slideshow-settings-admin.css', __FILE__ ), false );
			wp_enqueue_style( 'coop-slideshow-settings-admin' );
			
			wp_register_style( 'coop-slideshow-setup-admin', plugins_url( '/css/slideshow-setup-admin.css', __FILE__ ), false );
			wp_enqueue_style( 'coop-slideshow-setup-admin' );
			
			wp_register_script( 'coop-slideshow-admin-js', plugins_url( '/js/slideshow-admin.js',__FILE__), array('jquery'));
			wp_enqueue_script( 'coop-slideshow-admin-js' );
			
			wp_register_style( 'coop-signals', plugins_url( '/css/signals.css', __FILE__ ), false );
			wp_enqueue_style( 'coop-signals' );
		}
			
		return;
	}
	
	
	
	public function add_slideshow_menu() {
	
		global $slideshow_manager, $slideshow_defaults;
	
		$plugin_page = add_submenu_page( 'site-manager', 'Slideshow Manager', 'Slideshow Manager', 'manage_local_site', 'top-slides', array(&$slideshow_manager,'slideshow_manager_page'));
		
		add_submenu_page( 'site-manager', 'Slideshow Defaults', 'Slideshow Defaults', 'manage_local_site','slides-manager', array( &$slideshow_defaults,'slideshow_defaults_page'));
		
		error_log('plugin_page: '. $plugin_page );
		
		add_action( 'admin_footer-'.$plugin_page, array(&$slideshow_manager,'slideshow_footer' ));
		
	}
	

	
	/**
	*	Create slideshow-related tables any time a blog
	*	loads this plugin and that blog does _not_already_
	*	have the necessary tables.
	**/
	
	public function slideshow_create_db_table_handler() {
		global $wpdb;
		

	/*	MAINTENANCE utility 
		$del = "DELETE FROM $wpdb->options WHERE option_name='_slideshow_db_version'";
		$wpdb->query($del);
	*/
		$slideshow_db_version = get_option('_slideshow_db_version');
		if( FALSE !== $slideshow_db_version ) {
		
			// return or run an update ...
	//		error_log( '_slideshow_db_version: ' . $slideshow_db_version );
			return;
		}
		
	//	error_log( 'creating the slideshow table' );
		
		
		$table_name = $wpdb->prefix . 'slideshows';
		$sql = "CREATE TABLE $table_name ("
				." id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, "
				." title varchar(60) NOT NULL, "
				." layout varchar(20) NOT NULL DEFAULT 'no-thumb', "
				." transition varchar(20) NOT NULL DEFAULT 'horizontal',"
				." date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL ,"
				." is_active tinyint NOT NULL DEFAULT 0 );";
		$wpdb->query($sql);

		
		
		$table_name = $wpdb->prefix . 'slideshow_slides';
		$sql = "CREATE TABLE $table_name ("
				." id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, "
				." slideshow_id int(11)  NOT NULL, "
				." post_id int(11), "
				." slide_link varchar(250), "
				." ordering tinyint DEFAULT 0 NOT NULL, "
				." text_title varchar(250), "
				." text_content text "
				." );";
		$wpdb->query($sql);

		
		update_option('_slideshow_db_version', '1.0');
		
	}
	
}

if ( ! isset($slideshow_admin) ) {

	require_once( 'inc/slideshow-defaults.php' );
	require_once( 'inc/slideshow-manager.php' );
	require_once( 'inc/slideshow-frontside.php' );
	
	$slideshow_admin = new SlideshowAdmin();
}
	
endif; /* ! class_exists */