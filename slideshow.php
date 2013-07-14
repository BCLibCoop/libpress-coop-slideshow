<?php defined('ABSPATH') || die(-1);

/**
 * @package Slideshow Setup
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Slideshow Setup
 * Description: Slideshow configurator.  NETWORK ACTIVATE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.1.0
 
 **/
 
//require_once( 'inc/slide-custom-post-type.php' );
 
if ( ! class_exists( 'Slideshow' )) :

class Slideshow {

	var $slug = 'slideshow';
	var $sprite = '';

	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
	
		$this->sprite = plugins_url('/imgs/signal-sprite.png',__FILE__);
		
		if( is_admin() ) {
		
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles_scripts' ));
			add_action( 'admin_menu', array( &$this,'add_slideshow_menu' ));
			add_action( 'wp_ajax_coop-save-slideshow-change', array( &$this, 'slideshow_settings_save_changes'));
			add_action( 'wp_ajax_slideshow_add_text_slide',array(&$this,'slideshow_add_text_slide'));
			add_action( 'wp_ajax_precheck_slideshow_collection_name',array(&$this,'slideshow_precheck_collection_name'));
			add_action( 'wp_ajax_slideshow-fetch-img-meta',array(&$this,'slideshow_fetch_img_meta_callback'));
			add_action( 'wp_ajax_slideshow-fetch-collection',array(&$this,'slideshow_fetch_collection'));
			add_action( 'wp_ajax_slideshow-save-slide-collection',array(&$this,'slideshow_save_collection_handler'));
			add_action( 'wp_ajax_slideshow-delete-slide-collection',array(&$this,'slideshow_delete_collection_handler'));
			
			// conditionally ensures that the slideshow table is present
			add_action( 'wp_loaded', array( &$this, 'slideshow_create_db_table_handler'));
		}
		else {
			add_action( 'wp_enqueue_scripts', array( &$this, 'frontside_enqueue_styles_scripts' ));
		}
	}
	
	public function frontside_enqueue_styles_scripts() {
	
		self::slideshow_settings_publish_config();
	
	//	wp_register_style( 'coop-slideshow', 	plugins_url( '/css/slideshow.css', __FILE__ ), false );
	//	wp_enqueue_style( 'coop-slideshow' );
	//	wp_enqueue_script( 'coop-slideshow-js' );
	}
	
	public function admin_enqueue_styles_scripts($hook) {
	
	//	error_log($hook);
	
		if( 'site-manager_page_top-slides' !== $hook && 'site-manager_page_slides-settings' !== $hook ) {
			return;
		}

		wp_register_style( 'coop-slideshow-admin', plugins_url( '/css/slideshow-admin.css', __FILE__ ), false );
		wp_register_style( 'coop-signals', plugins_url( '/css/signals.css', __FILE__ ), false );
		wp_register_style( 'coop-chosen', plugins_url( '/css/chosen.min.css', __FILE__ ), false );
		
		wp_register_script( 'coop-slideshow-admin-js', plugins_url( '/js/slideshow-admin.js',__FILE__), array('jquery'));
		wp_register_script( 'coop-slideshow-defaults-js', plugins_url( '/inc/default-settings.js',__FILE__));
		wp_register_script( 'coop-chosen-jq-min-js', plugins_url( '/js/chosen.jquery.min.js',__FILE__));
				
		wp_enqueue_style( 'coop-slideshow-admin' );
		wp_enqueue_style( 'coop-signals' );
		wp_enqueue_style( 'coop-chosen' );
	
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'coop-slideshow-admin-js' );
		wp_enqueue_script( 'coop-chosen-jq-min-js' );
		wp_enqueue_script( 'coop-slideshow-defaults-js' );	//  template of bxSlider's default values.
			// we use this to test whether the user has altered any settings to know what we have to save.
		
	}
	
	public function add_slideshow_menu() {
	
		$plugin_page = add_submenu_page( 'site-manager', 'Slideshow Admin','Slideshow Admin', 'edit_options', 'top-slides', array(&$this,'slideshow_setup_page'));
		add_submenu_page( 'site-manager', 'Slideshow Settings', 'Slideshow Settings', 'manage_network','slides-settings', array(&$this,'slideshow_settings_admin_page'));
		
		add_action( 'admin_footer-'.$plugin_page, array(&$this,'slideshow_footer' ));
		
	}
	
	
	
	public function slideshow_setup_page() {
	
		global $wpdb;
		
		
		$blog_details = get_blog_details();
		$siteurl = get_site_url($blog_details->site_id); 	// base site == the shared media host 
		/*
		foreach( $blog_details as $k => $v ) {
			error_log( "$k => $v" );
		}
		*/		
		
		$thumb_w = get_option('thumbnail_size_w',true);
		$thumb_h = get_option('thumbnail_size_h',true);
		
		
		$out = array();
		$out[] = '<div class="wrap">';
		
		$out[] = '<div id="icon-options-general" class="icon32">';
		$out[] = '<br>';
		$out[] = '</div>';
		
		$out[] = '<h2>Slideshow Setup</h2>';
		
		$out[] = '<p>&nbsp;</p>';
		
		$out[] = '<p>This page supports the creation of Slideshows: a series of images / text slides which rotate automatically from one to the next. A slideshow can comprise up to five slides (for best viewing effect). An image suitable for use in the slideshow is 1000 pixels wide x 300 pixels high. Images should be prepared under the Media menu, and must be given a Media Tag of: <b>slide</b>.</p>';
		
		$out[] = '<table class="slideshow-header-controls">';
		$out[] = '<tr><td class="slideshow-name">';
		
		$out[] = '<input type="text" class="slideshow-collection-name" name="slideshow-collection-name" value="" placeholder="Enter a name for a new slideshow">';
		
		$out[] = '</td><td class="slideshow-gutter">&nbsp;</td><td class="slideshow-controls">';
		
		$out[] = '<a href="" class="button button-primary slideshow-save-collection-btn">Save collection</a>';
		$out[] = '<a href="" class="button slideshow-delete-collection-btn">Delete the loaded slideshow</a>';
		
		$out[] = '</td></tr>';
		
		$out[] = '<tr><td class="slideshow-name">';

		$out[] = self::slideshow_collection_selector();
		
		$out[] = '</td><td class="slideshow-gutter">&nbsp;</td><td class="slideshow-signal-preload">';
		
		$out[] = '<div id="collection-name-signal" class="slideshow-signals"><img class="signals-sprite" src="'.$this->sprite.'"></div>';

		$out[] = '</td></tr>';
		$out[] = '</table>';
		
		
		// $sql = "SELECT * FROM $wpdb->posts WHERE post_type='attachment' ORDER BY post_title";
		$sql = "SELECT * FROM $wpdb->posts WHERE post_type='attachment' 
				AND ID IN (SELECT object_id FROM $wpdb->term_relationships tr JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id=tt.term_taxonomy_id WHERE tt.taxonomy = 'media_tag') ORDER BY post_title";
		$res = $wpdb->get_results($sql);
		
		
		$out[] = self::slideshow_droppable_table();
		
		$out[] = '<h3 class="slideshow-runtime-heading">Runtime information:</h3>';
		$out[] = '<div class="slideshow-runtime-information"></div>';
		
		$out[] = '</td><!-- .slideshow-dropzone -->';
		$out[] = '<td class="slideshow-gutter">&nbsp;</td>';
		$out[] = '<td class="slideshow-dragzone">';
		
		
		$out[] = '<table class="slideshow-drag-table">';
		$out[] = '<tr><th class="alignleft">Your Slide Images</th></tr>';
		$out[] = '<tr><td id="slide-remove-local" class="slideshow-draggable-items returnable local">';
		
		foreach( $res as $r ) {
		
			$title = $r->post_title;
			
			$file = get_post_meta($r->ID,'_wp_attached_file', true);
			
			$d = date_parse($r->post_date);
			$folder = sprintf('%4d/%02d/',$d['year'],$d['month']);
			
			$meta = get_post_meta($r->ID,'_wp_attachment_metadata');
			
			$thumbnail = $meta[0]['sizes']['thumbnail'];
			$medium = $meta[0]['sizes']['medium'];
			$large = $meta[0]['sizes']['large'];
			
		//	error_log( $large['width'] . ', '. $large['height'] );
			
			/*
			foreach( $meta[0] as $k => $v ) {						
				if( $k == 'image_meta' ) {
					// no op
				}
				else if( $k == 'sizes' ) {
					foreach( $v as $size ) {
						if( is_array($size) ) {
							foreach( $size as $z => $q ) {
								error_log( "\t" .$z .' => '. $q );
							}
						}
						else {
							error_log( 'offset: ' . $size );												
							$x = $v[$size];
							foreach( $x as $k2 => $v2 ) {
								error_log( '['.$SIZE.'] '. $k2 .' => '. $v2 );			
							}
						}
					}
				}
				else {
					error_log( $k .' => '. $v );
				}
			}
			*/
			
			$out[] = sprintf('<div class="draggable" data-img-id="%d" data-img-caption="%s"><img id="thumb%d" src="/files/%s%s" width="%d" height="%d" class="thumb">',$r->ID,$title,$r->ID,$folder, $thumbnail['file'], $thumb_w, $thumb_h);
			
			$out[] = sprintf('<img id="slotview%d" src="/files/%s%s" height="%d" class="slotview"></div>',$r->ID,$folder,$large['file'],$thumb_h);
	
		}
		
		$out[] = '</td></tr>';
		
		$out[] = '<tr><th class="alignleft">Shared Slide Images</th></tr>';
		$out[] = '<tr><td id="slide-remove-shared" class="slideshow-draggable-items returnable shared">';
		
		/*	fetch NSM images with Media Tag: 'slide' 	*/
		/**
		*	THIS IS HARDCODED TO FETCH FROM blog 1, 
		*	which is the designated Network Shared Media instance. 
		*
		*	The other significant difference is that the 
		*	image repos URL is distinct from the networked sites:
		*	/wp-uploads/ yadda yadda is the repository address:  sites use /files/ yadda yadda.
		**/
		
		$sql = "SELECT * FROM wp_posts WHERE post_type='attachment'
				AND ID IN (SELECT object_id FROM wp_term_relationships tr JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id=tt.term_taxonomy_id WHERE tt.taxonomy = 'media_tag') ORDER BY post_title";
		
		$res = $wpdb->get_results($sql);
		
		foreach( $res as $r ) {
		
			$title = $r->post_title;
		
			$select = "SELECT meta_value FROM wp_postmeta WHERE post_id = $r->ID AND meta_key = '_wp_attached_file'";
			$file = $wpdb->get_var($select);
			
			$d = date_parse($r->post_date);
			$folder = sprintf('%4d/%02d/',$d['year'],$d['month']);
			
			$sql = "SELECT meta_value FROM wp_postmeta WHERE post_id=$r->ID AND meta_key = '_wp_attachment_metadata'";
			
			$meta = $wpdb->get_var($sql);
			$meta = maybe_unserialize($meta);
			
/*
			echo '<pre>';
			var_dump($meta);
			echo '</pre>';
*/
						
			$thumbnail = $meta['sizes']['thumbnail'];
			$medium = $meta['sizes']['medium'];
			$large = $meta['sizes']['large'];
			
			$out[] = sprintf('<div class="draggable" data-img-id="%d" data-img-caption="%s"><img id="thumb%d" src="%s/wp-uploads/%s%s" width="%d" height="%d" class="thumb">',$r->ID,$title,$r->ID,$siteurl,$folder, $thumbnail['file'], $thumb_w, $thumb_h);
			
			$out[] = sprintf('<img id="slotview%d" src="%s/wp-uploads/%s%s" height="%d" class="slotview"></div>',$r->ID,$siteurl,$folder,$large['file'],$thumb_h);
	
		}
		
		
		$out[] = '</table>';
		$out[] = '</form><!-- .slideshow-definition-form -->';
		
		
		$out[] = '</td><!-- .slideshow-dragzone -->';
		$out[] = '</tr><!-- .master-row -->';
		$out[] = '</table><!-- .slideshow-drag-drop-layout -->';
		
		
		$out[] = '<div class="slideshow-signals-preload">';
		$out[] = '<img src="'.$this->sprite.'" width="362" height="96">';
		$out[] = '</div>';
		
		$out[] = self::text_slide_create_form();
		
		$out[] = self::quick_set_layout_controls();
		
		echo implode("\n",$out);
		
	}
	
	private function slideshow_collection_selector() {
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'slideshows';
		$sql = "SELECT * FROM $table_name ORDER BY title";
		$res = $wpdb->get_results($sql);
		
		$out = array();
		$out[] = '<select data-placeholder="... or choose a past slideshow to reload" name="slideshow_select" id="slideshow_select" class="slideshow_select chzn-select">';
		
		$out[] = '<option value=""></option>';

		foreach($res as $r) {
			$out[] = '<option value="'.$r->id .'" >'.$r->title.'</option>';
		}
		
		$out[] = '</select>';
		
		return implode( "\n", $out);
	}
	
	private function slideshow_droppable_table() {
		
		$out = array();
		
		$out[] = '<table class="slideshow-drag-drop-layout">';
		$out[] = '<tr class="master-row">';
		$out[] = '<td class="slideshow-dropzone">';
		
		$out[] = '<table class="slideshow-sortable-rows">';
		
		$out[] = '<tr class="head-row"><th></th><th>';
		
		$out[] = '<div class="slideshow-controls-right"><input type="checkbox" id="slideshow-is-active-collection" class="slideshow-is-active-collection" value="1"> <label for="slideshow-is-active-collection" class="slideshow-activate-collection">This is the active slideshow</label></div>';
		
		$out[] = 'Caption/Title<br/><span class="slideshow-slide-link-header">Slide Link</span>';
					
		$out[] = '</th></tr>';
					
		for( $i=0;$i<5;$i++) {
			$out[] = '<tr id="row'.$i.'" class="slideshow-collection-row draggable droppable" id="dropzone'.$i.'"><td class="thumbbox">&nbsp;</td>';
			$out[] = '<td class="slideshow-slide-title"><div class="slide-title">&nbsp;</div><div class="slide-link">&nbsp;</div></td></tr>';
		}
		
		$out[] = '</table><!-- .slideshow-droppable-rows -->';
		
		$out[] = '<div id="runtime-signal" class="slideshow-signals"><img src="'.$this->sprite.'" class="signals-sprite"></div>';
		
		return implode("\n",$out);
	}
	
	
	/*
	private function slideshow_droppable_table() {
		
		$out = array();
		
		$out[] = '<table class="slideshow-drag-drop-layout">';
		$out[] = '<tr class="master-row">';
		$out[] = '<td class="slideshow-dropzone">';
		
		$out[] = '<table class="slideshow-droppable-rows">';
		
		$out[] = '<tr class="head-row"><th></th><th>Caption/Title</th><th>Slide Link</th></tr>';
		
		for( $i=0;$i<=5;$i++) {
			$out[] = '<tr id="row'.$i.'" class="snaprow"><td id="dropzone'.$i.'" class="thumbbox droppable snappable">&nbsp;</td><td class="slideshow-caption-title">&nbsp;</td><td class="slideshow-slide-link">&nbsp;</td></tr>';
		}
		
		$out[] = '</table><!-- .slideshow-droppable-rows -->';
		
		return implode("\n",$out);
	}
	*/
	
	private function text_slide_create_form() {
	
		$out = array();
		$out[] = '<table class="slideshow-text-slide-create">';
		$out[] = '<tr>';
		$out[] = '<td class="slideshow-label"><h3>Add text-only slide</h3></td>';
		$out[] = '</tr>';

		$out[] = '<tr>';
		$out[] = '<td><input type="text" id="slideshow-text-slide-heading" class="slideshow-text-slide-heading" name="slideshow-text-slide-heading" value="" placeholder="Headline"></td>';
		$out[] = '</tr>';

		$out[] = '<tr>';
		$out[] = '<td><textarea id="slideshow-text-slide-content" class="slideshow-text-slide-content" name="slideshow-text-slide-content" placeholder="Message text"></textarea></td>';
		$out[] = '</tr>';
		
		$out[] = '<tr>';
		$out[] = '<td class="slideshow-text-slide-link-box">';
		$out[] = '<a href="" class="button slideshow-text-slide-link-btn">Link to ...</a>';
		$out[] = '<input type="text" class="hidden slideshow-text-slide-link-input" value="">';
		$out[] = '</td>';
		$out[] = '</tr>';
		
		$out[] = '<tr>';
		$out[] = '<td class="slideshow-text-slide-save-box">';
		$out[] = '<a href="" class="button slideshow-text-slide-cancel-btn">Cancel</a>';
		$out[] = '<a href="" class="button slideshow-text-slide-save-btn">Add the slide</a>';
		$out[] = '</td>';
		$out[] = '</tr>';

		$out[] = '</table><!-- .slideshow-text-slide-create -->';
		
		return implode("\n",$out);
	}
		
	private function quick_set_layout_controls() {
		
		$out = array();
		
		/**
		*	Quick set modes and effects
		*
		*	this is the matrix at the bottom of the form for setting 
		*	thumbnails style and slide transition direction/fade 
		**/
		
		
		$out[] = '<table class="slideshow-layout-controls">';
		$out[] = '<tr>';
		$out[] = '<td colspan="3">';
		$out[] = '<h3>Slideshow Layout</h3>';
		$out[] = '</td>';
		$out[] = '</tr>';
		
		$out[] = '<tr>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/NoThumbnails.png',__FILE__) .'" data-id="slideshow-control-1" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-1" value="no-thumb">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-1">No thumbnails</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Previous / Next arrows';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
		
		$out[] = '</td>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/VerticalThumbnails.png',__FILE__) .'" data-id="slideshow-control-2" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-2" value="vertical">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-2">Vertical thumbnails</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Clickable thumbnails displayed vertically on the left-hand side';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
			
		$out[] = '</td>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/HorizontalThumbnails.png',__FILE__) .'" data-id="slideshow-control-3" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-3" value="horizontal">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-3">Horizontal thumbnails</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Clickable thumbnails displayed horizontally below the slideshow';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
		
		$out[] = '</td>';
		$out[] = '</tr>';


		$out[] = '<tr>';
		$out[] = '<td colspan="3">';
		$out[] = '<h3>Transitions</h3>';
		$out[] = '</td>';
		$out[] = '</tr>';

		$out[] = '<tr>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/HorizontalSlide.png',__FILE__) .'" data-id="slideshow-control-4" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-4" value="horizontal">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-4">Slide Horizontal</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Slides enter from the right and exit to the left';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
		
		$out[] = '</td>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/VerticalSlide.png',__FILE__) .'" data-id="slideshow-control-5" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-5" value="vertical">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-5">Slide Vertical</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Slides enter below and exit above';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
			
		$out[] = '</td>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/Fade.png',__FILE__) .'" data-id="slideshow-control-6" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-6" value="fade">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-6">Cross-fade</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'One slide dissolves into the next';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
		
		$out[] = '</td>';
		$out[] = '</tr>';


		$out[] = '</table><!-- .slideshow-layout-controls -->';
		
		return implode("\n",$out);
	}
	
	public function slideshow_footer() {
	

		$out = array();
		$out[] = '<div class="alt-hover">&nbsp;</div>';
		
		echo implode( "\n", $out );

	
	}
	
	
	public function slideshow_delete_collection_handler() {
		
		global $wpdb;
		
		$slideshow_id = $_POST['slideshow_id'];
		
		$table_name = $wpdb->prefix . 'slideshow_slides';
		
		$ret = $wpdb->delete( $table_name, array('slideshow_id'=>$slideshow_id));
	//	error_log( 'remove from '.$table_name .': '. $ret .' for slideshow_id: '.  $slideshow_id );
		
		$table_name = $wpdb->prefix . 'slideshows';
		$ret = $wpdb->delete( $table_name, array('id'=>$slideshow_id));
		error_log( 'remove from '.$table_name .': '. $ret .' for slideshow_id: '.  $slideshow_id );
		
		echo '{"result":"success", "feedback":"Slideshow deleted."}';
		die();
	}

	
	public function slideshow_save_collection_handler() {
		
		global $wpdb;
				
		$title = $_POST['title'];
		$slideshow_id = $_POST['slideshow_id'];
		$is_active = $_POST['is_active'];
		
		if( empty($is_active) || $is_active == 'false' ) {
		//	error_log( 'is_active setting to zero' );
			$is_active = 0;
		}
		else {
			$is_active = 1;
		}
		
		$layout = $_POST['layout'];
		$transition = $_POST['transition'];
		
		$slides = array();
		if( array_key_exists('slides',$_POST) ) {
			$slides = $_POST['slides'];
		}
			
		if( empty($slideshow_id) ) {
			$slideshow_id = self::slideshow_create_collection( $title );
		}
		
		$table_name = $wpdb->prefix . 'slideshows';
		
		if( $is_active == 1 ) {
			/* erase any currently marked as active */
			$sql = "UPDATE $table_name SET is_active=0 WHERE is_active=1";
			$wpdb->query($sql);
		}
		
		$sql = "UPDATE $table_name SET layout='".$layout."', transition='".$transition."', date=now(), is_active=$is_active WHERE id = $slideshow_id";
		$wpdb->query($sql);
		
		
		/**
		*	Release all slides currently associated with this slideshow_id
		*
		*	We do this to accommodate deletions from the set.
		**/
		$table_name = $wpdb->prefix . 'slideshow_slides';
		$ret = $wpdb->update($table_name, array('slideshow_id'=>0),array('slideshow_id' => $slideshow_id));
		error_log( 'Releasing slildes: updated '.$ret .' where slideshow_id = '.$slideshow_id);
		
		/**
		*	Build the update/insert statement foreach 
		*
		*	Iterates the slides collection, builds appropraite query
		*	Some slides already exist: update; others are new, insert.
		**/
		foreach( $slides as $s ) {
		
			$FIELDS = array('slideshow_id');
			$VALUES = array($slideshow_id);
			
			$type = $s['type'];
			$slide_id = '';
			
			if( 'image' === $type ) {
				
				$FIELDS[] = 'post_id';
				$VALUES[] = $s['post_id'];
				
			}
			else {	// 'text' === $type
				
				$FIELDS[] = 'text_title';
				$VALUES[] = "'".addslashes($s['text_title'])."'";
						
				$FIELDS[] = 'text_content';
				$VALUES[] = "'".addslashes($s['text_content'])."'";
			}
						
			if( array_key_exists('slide_id',$s ) ) {
			
				$slide_id = $s['slide_id'];
				
				error_log( '$slide_id = ' . $slide_id);
				
				//$FIELDS[] = 'id';
				//$VALUES[] = $s['slide_id'];
			}
			
			if( array_key_exists('ordering',$s) && is_numeric($s['ordering'])) {
				$FIELDS[] = 'ordering';
				$VALUES[] = $s['ordering'];
			}
			
			if( array_key_exists('slide_link',$s ) && !empty($s['slide_link'])) {
				$FIELDS[] = 'slide_link';
				$VALUES[] = "'".addslashes($s['slide_link'])."'";
			}
			
			$table_name = $wpdb->prefix . 'slideshow_slides';
			$sql = '';
			
			if( ! empty($slide_id) ) {
			
				// pre-existing slide - update, do not create
				$sql = "UPDATE $table_name SET ";
				for( $i=0;$i<count($FIELDS);$i++) {
					$sql .= $FIELDS[$i] .'='.$VALUES[$i];
					if( $i < count($FIELDS)-1) {
						$sql .= ',';
					}
				}
				$sql .= " WHERE id = $slide_id";
			}
			else {
			
				$sql = "INSERT INTO $table_name (";
				$sql .= implode(',',$FIELDS);
				$sql .=") VALUES (" . implode(',',$VALUES) . ")";
			}
		
			error_log( "\n\n".$sql."\n\n" );
		
			$wpdb->query($sql);
		}
		
		echo '{"result":"success","slideshow_id":"'.$slideshow_id.'", "feedback":"Slideshow collection saved"}';
		die();
		
	}
	
	public function slideshow_fetch_collection() {
		
		global $wpdb;
		
		$slideshow_id = $_POST['slideshow_id'];
		
		$table_name = $wpdb->prefix.'slideshows';
		$is_active = $wpdb->get_var("SELECT is_active FROM $table_name WHERE id =$slideshow_id");
				
		$table_name = $wpdb->prefix . 'slideshow_slides';
		$sql = "SELECT * FROM $table_name WHERE slideshow_id=$slideshow_id ORDER BY ordering";
		$slides = $wpdb->get_results($sql);
		$out = array();
		foreach( $slides as $s ) {
			if( $s->post_id ) {
				$out[] = '{"id":"'.$s->id.'","post_id":"'.$s->post_id.'","slide_link":"'.$s->slide_link.'","ordering":"'.$s->ordering.'"}'; 
			}
			else {
				$out[] = '{"id":"'.$s->id.'","slide_link":"'.$s->slide_link.'","text_title":"'.stripslashes($s->text_title).'","text_content":"'.stripslashes($s->text_content).'","ordering":"'.$s->ordering.'"}'; 
			}
		}
		
	//	error_log( implode( "\n", $out ));
			
		echo '{"slides":['. implode(',',$out).'], "is_active":"'.$is_active.'"}';
		die();
		
	}
	
	
	/**
	*	Build a simpler data structure for metadata
	*
	*	return this as a nested array
	**/
	
	public function slideshow_fetch_img_meta() {
		
		global $wpdb;

		// try to get the post from the local media cache first,
		// 	fallback to looking in the network shared media collection

		$post_id = $_POST['post_id'];
		$sql = "SELECT post_title FROM $wpdb->posts WHERE ID = $post_id";
		$post_title = $wpdb->get_var($sql);
		
		$source = 'local';	// originates in the blog owner's media dirs vs. network shared media
		
		$meta;
		
		if( $post_title == NULL ) {
			$sql = "SELECT post_title FROM wp_posts WHERE ID = $post_id";
			$post_title = $wpdb->get_var($sql);
			if( $post_title == NULL ) {
				echo '{"result":"failed"}';
				die();
			}
			$source = 'network';
			$sql = "SELECT meta_value FROM wp_postmeta WHERE meta_key='_wp_attachment_metadata' AND post_id = $post_id";
			$meta = $wpdb->get_var($sql);
		}
		else {
			// 'local' again/still
			$sql = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_wp_attachment_metadata' AND post_id= $post_id";
			$meta = $wpdb->get_var($sql);
		}
		$meta = maybe_unserialize($meta);
		
		$postmeta = array();
		
		$postmeta['title'] = $post_title;
		
		list( $year, $month, $file ) = explode( '/',$meta['file']);
		
		$rootdir = 'files';		// default for 'local' source
		if( $source === 'network' ) {
			$rootdir = 'wp-uploads';
		}
		
		$postmeta['folder'] = sprintf( "/%s/%4d/%02d/",$rootdir,$year,$month);
		$postmeta['file'] = $file;
		$postmeta['width'] = $meta['width'];
		$postmeta['height'] = $meta['height'];
		
		$postmeta['thumb'] = array( 
			'file' => $meta['sizes']['thumbnail']['file'],
			'width'=> $meta['sizes']['thumbnail']['width'],
			'height'=> $meta['sizes']['thumbnail']['height'] 
		);
		$postmeta['medium'] = array( 
			'file' => $meta['sizes']['medium']['file'],
			'width'=> $meta['sizes']['medium']['width'],
			'height'=> $meta['sizes']['medium']['height'] 
		);

		$postmeta['large'] = array( 
			'file' => $meta['sizes']['large']['file'],
			'width'=> $meta['sizes']['large']['width'],
			'height'=> $meta['sizes']['large']['height'] 
		);

		/*		
		foreach( $meta as $k => $v ) {
			if( is_array($v) ) {
				foreach( $v as $j => $l ) {
					if( is_array($l)) {
						foreach( $l as $a => $b ) {
							if( is_array($b)) {
								foreach( $b as $c => $d ) {
									error_log( $k .': '.$j.': ['. $a .'] '. $c .' => ' . $d );
								}
							}
							else {
								error_log( $k.': ['.$j.'] '.$a .' <=> '.$b );		
							}
						}
					}
					else {
						error_log( $k.': '.$j .' => '. $l );
					}
				}
			}
			else {
				error_log( $k .' = > '. $v );
			}
		}
*/		
		
		return $postmeta;
		
	}
	

	/**
	*	Fetch image meta callback	
	*		wraps the call to get img meta data
	*	returns it as JSON
	**/

	public function slideshow_fetch_img_meta_callback() {
	
		$post_id = $_POST['post_id'];
		
		$meta = self::slideshow_fetch_img_meta($post_id);

		$out = array();
		
		$out[] = '{"result":"success"';
		$out[] = '"meta": {"title": "'.$meta['title'].'"';
		$out[] = '"file":"'.$meta['file'].'"';
		$out[] = '"folder":"'.$meta['folder'].'"';
		$out[] = '"height":"'.$meta['height'].'"';
		$out[] = '"width":"'.$meta['width'].'"';
		
		$out[] = '"thumb": {"file":"'.$meta['thumb']['file'].'"';
		$out[] = '"width":"'.$meta['thumb']['width'].'"';
		$out[] = '"height":"'.$meta['thumb']['height'].'"}';
		
		$out[] = '"medium": {"file":"'.$meta['medium']['file'].'"';
		$out[] = '"width":"'.$meta['medium']['width'].'"';
		$out[] = '"height":"'.$meta['medium']['height'].'"}';
		
		$out[] = '"large": {"file":"'.$meta['large']['file'].'"';
		$out[] = '"width":"'.$meta['large']['width'].'"';
		$out[] = '"height":"'.$meta['large']['height'].'"}}}';
		
		echo implode(',',$out);
		die();
		

	}
	
	public function slideshow_precheck_collection_name() {
		
		global $wpdb;
		
		$slideshow_name = sanitize_text_field($_POST['slideshow_name']);
		$table_name = $wpdb->prefix . 'slideshows';
		
		$sql = "SELECT id FROM $table_name WHERE title = '".$slideshow_name."'";
		
		$id = $wpdb->get_var($sql, FALSE);
		
		if( $id ) {		
			/* found - not okay to use */
			echo '{"result":"found", "slideshow_id":"'.$id.'"}';
		}
		else {	
			/* failed - is okay to use */
			echo '{"result":"not found"}';
		}
		die();
	}
	
	public function slideshow_create_collection( $slideshow_name = '' ) {
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'slideshows';
		$sql = "INSERT INTO $table_name (title) VALUES ( '".addslashes($slideshow_name)."' )";
		$wpdb->query($sql);
		
		return $wpdb->insert_id;
	}
	
	
	/**
	*	Store the content of the Add Text-only slide subform
	*
	**/
	public function slideshow_add_text_slide() {
		
		error_log(__FUNCTION__ );
		
		global $wpdb;
		
		$slideshow_id = $_POST['slideshow_id'];
		$slideshow_name = sanitize_text_field($_POST['slideshow_name']);
		$title = sanitize_text_field($_POST['title']);
		$content = sanitize_text_field($_POST['content']);
		$link = '';
		if( array_key_exists('slide_link',$_POST)) {
			$link = sanitize_text_field($_POST['slide_link']);	
		}
				
		if( empty($slideshow_id) || $slideshow_id == 'null' ) {
			if( ! empty($slideshow_name) ) {
				$slideshow_id = self::slideshow_create_collection($slideshow_name);
			}
			else {
				echo '{"result":"failed"}';
			}
		}
		
		$table_name = $wpdb->prefix . 'slideshow_slides';
		$sql = "INSERT INTO $table_name (slideshow_id,text_title,text_content, slide_link) values ( $slideshow_id, '".addslashes($title)."','". addslashes($content)."', '".$link."')";
		
		$wpdb->query($sql);
		
		$slide_id = $wpdb->insert_id;
		if( $slide_id ) {
			echo '{"result":"success", "slide_id":"'.$slide_id.'"}';
		}
		else {
			echo '{"result":"failed"}';
		}
		die();
	}

	
	/**
	*	Store / adjust settings from the global Slideshow Settings long form of options.
	*
	**/
	public function slideshow_settings_admin_page() {
				
	//	error_log(__FUNCTION__);
				
		$out = array();
		$out[] = '<div class="wrap">';
		
		$out[] = '<div id="icon-options-general" class="icon32">';
		$out[] = '<br>';
		$out[] = '</div>';
		
		$out[] = '<h2>Slideshow Settings</h2>';
		$out[] = '<p>&nbsp;</p>';
		$out[] = '<p>Instructions go here.</p>';
		
		$out[] = '<table class="form-table">';
		
		$out[] = self::slideshow_settings_parse_defaults();
					
		$out[] = '</table>';
		
		$out[] = '<p class="submit">';
		$out[] = '<input type="submit" value="Save Changes" class="button button-primary" id="coop-slideshow-submit" name="submit">';
		$out[] = '</p>';
		
		echo implode("\n",$out);
	}
	
	public function slideshow_settings_save_changes() {
		
	//	error_log(__FUNCTION__);
		
		// reconstitute the keys we need to get into the $_POST object
		$keys = stripslashes($_POST['keys']);
		$keys = str_replace(array('[',']','"'), array('','',''), $keys );
		$keys = explode(",",$keys) ;
		
		foreach( $keys as $k ) {
			$val = $_POST[$k];
			update_option('_'.$this->slug.'_'.$k, "$val" );
		//	error_log( $k . ' => '. $val );
		}
		
		echo '{"feedback": "'.count($keys).' settings updated"}';
		die();
	}	
	
	public function slideshow_settings_publish_config() {
	
		global $wpdb;
		$tag = '_'.$this->slug.'_';
		$sql = "SELECT option_name as name, option_value as val FROM $wpdb->options WHERE option_name LIKE '".$tag."%'";
		
	//	error_log($sql);
		
		$res = $wpdb->get_results($sql);
		
		$out = array('<script id="slideshow-settings" type="text/javascript">');
		$out[] = 'window.slideshow_custom_settings = {';
		
		foreach( $res as $r ) {
			$k = str_replace($tag,'',$r->name);
			
			if( is_numeric($r->val) || $r->val == 'true' || $r->val == 'false' || $r->val == 'undefined' ) {
				$v = $r->val;	
			} 
			else {
				$v = sprintf("'%s'",$r->val);
			}
			$out[] = sprintf("%s: %s,",$k,$v);
		}
		
		$out[] = '};';
		$out[] = '</script>';
		echo implode("\n",$out);		
	}
	
	private function slideshow_settings_parse_defaults() {
	
		$lines = file( dirname(__FILE__).'/inc/default-settings.js');
		
		$out = array();
		$_fmt = '<tr><th>%s</th><td>%s</td><td>%s</td></tr>';
		
		foreach( $lines as $l ) 
		{
			/// skip lines
			if( FALSE !== strpos($l,'/*') ) {
				continue;
			}
			if( FALSE !== (strpos($l,' ')==0) ) {
				continue;
			}
			
			// Headers
			if( FALSE !== strpos($l, '//') ) {
				list($j,$h) = explode('//',$l);
				$out[] = '<tr><th><h3>'.$h.'</h3></th><td>Current value</td><td>Default</td></tr>';
				continue;
			}
	
			list($t,$s) = explode(": ",rtrim($l,",\n "));
			
			$widget = array();
			$_opt = get_option('_slideshow_'.$t);
			$default = '';
			
			if( FALSE !== strpos($s,',')) {
				// multiple term radio
				$s = str_replace(array('"',"'"),array('',''),$s);
				$pcs = explode(',',$s);
				$default = $pcs[0];
				$actual = (!empty($_opt) ? $_opt: $default);
				
				for( $i=0; $i < count($pcs); $i++ ) {
					$id = $t.$i;
					$checked = '';
					if( $actual == $pcs[$i] ) {
						$checked = ' checked="checked"';
					}
					$widget[] = sprintf('<input type="radio" id="%s" name="%s" value="%s"%s>',$id,$t,$pcs[$i],$checked);
					$widget[] = sprintf('<label for="%s">%s</label>', $id,$pcs[$i]);
				}
			}
			else if( FALSE !== strpos($s,'true')) {
				// binary radio T/f
				$default = 'true';
				$actual = (!empty($_opt) ? $_opt: $default);
				
				$checked = '';
				if( $actual == 'true' ) {
					$checked = ' checked="checked"';
				}
				$widget[] = sprintf('<input type="radio" id="%s-t" name="%s" value="true"%s>',$t,$t,$checked);
				$widget[] = sprintf('<label for="%s-t">true</label>',$t);
				
				$checked = '';
				if( $actual == 'false' ) {
					$checked = ' checked="checked"';
				}
				$widget[] = sprintf('<input type="radio" id="%s-f" name="%s" value="false"%s>',$t,$t,$checked);
				$widget[] = sprintf('<label for="%s-f">false</label>',$t);
				
			}
			else if( FALSE !== strpos($s,'false')) {
				// binary radio t/F
				$default = 'false';
				$actual = (!empty($_opt) ? $_opt: $default);
				
				$checked = '';
				if( $actual == 'true' ) {
					$checked = ' checked="checked"';
				}
				$widget[] = sprintf('<input type="radio" id="%s-t" name="%s" value="true"%s>',$t,$t,$checked);
				$widget[] = sprintf('<label for="%s-t">true</label>',$t);
				
				$checked = '';
				if( $actual == 'false' ) {
					$checked = ' checked="checked"';
				}
				$widget[] = sprintf('<input type="radio" id="%s-f" name="%s" value="false"%s>',$t,$t,$checked);
				$widget[] = sprintf('<label for="%s-f">false</label>',$t);
				
			}
			else if( FALSE !== strpos($s,"'")) {
				// quoted string value - strip quotes
				$default = str_replace(array('"',"'"),array('',''),$s);
				$actual = (!empty($_opt) ? $_opt: $default);
								
				$widget[] = sprintf('<input type="text" id="%s" name="%s" value="%s">',$t,$t,$actual);

			}
			else {
				// text field
				$default = $s;
				$actual = (!empty($_opt) ? $_opt: $default);
				
				$widget[] = sprintf('<input type="text" id="%s" name="%s" value="%s">',$t,$t,$actual);

			}	
			
			$out[] = sprintf($_fmt,$t,implode("&nbsp;&nbsp;",$widget),$default);
		}
		
		return implode("\n",$out);
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

if ( ! isset($slideshow) ) {
	$slideshow = new Slideshow();
}
	
endif; /* ! class_exists */