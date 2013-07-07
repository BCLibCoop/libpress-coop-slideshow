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

	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
	
		if( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles_scripts' ));
			add_action( 'admin_menu', array( &$this,'add_slideshow_menu' ));
			add_action( 'wp_ajax_coop-save-slideshow-change', array( &$this, 'slideshow_settings_save_changes'));
		}
		else {
			add_action( 'wp_enqueue_scripts', array( &$this, 'frontside_enqueue_styles_scripts' ));
		}
	}
	
	public function frontside_enqueue_styles_scripts() {
	
		self::publish_slider_settings();
	
	//	wp_register_style( 'coop-slideshow', 	plugins_url( '/css/slideshow.css', __FILE__ ), false );
	//	wp_enqueue_style( 'coop-slideshow' );
	//	wp_enqueue_script( 'coop-slideshow-js' );
	}
	
	public function admin_enqueue_styles_scripts($hook) {
	
	//	error_log($hook);
	
		if( 'site-manager_page_top-slides' !== $hook && 'site-manager_page_slides-settings' !== $hook ) {
			return;
		}

		wp_register_style( 'coop-slideshow-admin-css', plugins_url( '/css/slideshow-admin.css', __FILE__ ), false );
		wp_register_script( 'coop-slideshow-admin-js', plugins_url( '/js/slideshow-admin.js',__FILE__), array('jquery'));
		wp_register_script( 'coop-slideshow-defaults-js', plugins_url( '/inc/default-settings.js',__FILE__));
				
		wp_enqueue_style( 'coop-slideshow-admin-css' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'coop-slideshow-admin-js' );
		wp_enqueue_script( 'coop-slideshow-defaults-js' );	//  template of bxSlider's default values.
			// we use this to test whether the user has altered any settings to know what we have to save.
		
	}
	
	
	public function add_slideshow_menu() {
	
		$plugin_page = add_submenu_page( 'site-manager', 'Slideshow Admin','Slideshow Admin', 'edit_options', 'top-slides', array(&$this,'slideshow_setup_page'));
		add_submenu_page( 'site-manager', 'Slideshow Settings', 'Slideshow Settings', 'manage_network','slides-settings', array(&$this,'slideshow_admin_settings_page'));
		
		add_action( 'admin_footer-'.$plugin_page, array(&$this,'slideshow_setup_footer_script' ));
		
	}
	
	
	public function slideshow_setup_page() {
	
		global $wpdb;
		
		$out = array();
		$out[] = '<div class="wrap">';
		
		$out[] = '<div id="icon-options-general" class="icon32">';
		$out[] = '<br>';
		$out[] = '</div>';
		
		$out[] = '<h2>Slideshow Setup</h2>';
		$out[] = '<p>&nbsp;</p>';
		$out[] = '<p>This paage supprts the creation of Slideshows: a series of images / text slides which rotate automatically from one to the next. A slide show can comprise up to five slides (for best viewing effect). An image suitable for use in the slideshow is 1000px wide x 300px high. Images should be prepared under the Media menu, and must be given a Media Tag: <b>slide</b>.</p>';
		
		$out[] = '<form name="slideshow-definition-form">';
		
		$out[] = '<input type="text" class="slideshow-set-name" name="slideshow-set-name" value="" placeholder="Enter a name for this slide show">';
		
		// $sql = "SELECT * FROM $wpdb->posts WHERE post_type='attachment' ORDER BY post_title";
		$sql = "SELECT * FROM $wpdb->posts WHERE post_type='attachment' 
				AND ID IN (SELECT object_id FROM $wpdb->term_relationships tr JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id=tt.term_taxonomy_id WHERE tt.taxonomy = 'media_tag') ORDER BY post_title";
		$res = $wpdb->get_results($sql);
		
		
		$out[] = '<table class="slideshow-drag-drop-layout">';
		$out[] = '<tr class="master-row">';
		$out[] = '<td class="slideshow-dropzone">';
		
		$out[] = '<table class="slideshow-droppable-rows">';
		
		$out[] = '<tr class="head-row"><th></th><th>Caption/Title</th><th>Slide Link</th></tr>';
		
		for( $i=0;$i<=5;$i++) {
			$out[] = '<tr id="row'.$i.'" class="snaprow"><td id="dropzone'.$i.'" class="thumbbox droppable snappable">&nbsp;</td><td class="slideshow-caption-title">&nbsp;</td><td class="slideshow-slide-link">&nbsp;</td></tr>';
		}
		
		$out[] = '</table><!-- .slideshow-droppable-rows -->';
		
		$out[] = '<div class="slideshow-runtime-information"></div>';
		
		$out[] = '</td><!-- .slideshow-dropzone -->';
		$out[] = '<td class="slideshow-gutter">&nbsp;</td>';
		$out[] = '<td class="slideshow-dragzone">';
		
		
		
		$out[] = '<table class="slidershow-drag-table">';
		$out[] = '<tr><th class="alignleft">Your Slide Images</th></tr>';
		$out[] = '<tr><td class="slideshow-draggable-items">';
		
		foreach( $res as $r ) {
		
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
			
			$out[] = sprintf('<div class="draggable" data-img-id="%d" data-img-caption="%s"><img id="thumb%d" src="/files/%s%s" width="%d" height="%d" class="thumb">',$r->ID,$file,$r->ID,$folder, $thumbnail['file'], $thumbnail['width'], $thumbnail['height']);
			
			$out[] = sprintf('<img id="slotview%d" src="/files/%s%s" height="%d" class="slotview"></div>',$r->ID,$folder,$large['file'],$thumbnail['height']);
	
		}
		
		$out[] = '</td></tr>';
		
		$out[] = '<tr><th class="alignleft">Shared Slide Images</th></tr>';
		$out[] = '<tr><td class="slideshow-draggable-items">';
		
		/*	fetch NSM images with Media Tag: 'slide' 	*/
		/**
		*	THIS IS HARDCODED TO FETCH FROM blog 1, 
		*	which is the designated Network Shared Media instance. 
		*
		*	The other significant difference is that the 
		*	image repos URL is distinct from the networked sites:
		*	/wp-uploads/ yadda yadda is the repository address:  sites use /files/ yadda yadda.
		**/
		
		$sql = "SELECT * FROM WP_posts WHERE post_type='attachment'
				AND ID IN (SELECT object_id FROM wp_term_relationships tr JOIN wp_term_taxonomy tt ON tr.term_taxonomy_id=tt.term_taxonomy_id WHERE tt.taxonomy = 'media_tag') ORDER BY post_title";
		
		$res = $wpdb->get_results($sql);
		
		foreach( $res as $r ) {
		
			$file = get_post_meta($r->ID,'_wp_attached_file', true);
			
			$d = date_parse($r->post_date);
			$folder = sprintf('%4d/%02d/',$d['year'],$d['month']);
			
			$meta = get_post_meta($r->ID,'_wp_attachment_metadata');
			
			$thumbnail = $meta[0]['sizes']['thumbnail'];
			$medium = $meta[0]['sizes']['medium'];
			$large = $meta[0]['sizes']['large'];
			
			$out[] = sprintf('<div class="draggable" data-img-id="%d" data-img-caption="%s"><img id="thumb%d" src="/wp-uploads/%s%s" width="%d" height="%d" class="thumb">',$r->ID,$file,$r->ID,$folder, $thumbnail['file'], $thumbnail['width'], $thumbnail['height']);
			
			$out[] = sprintf('<img id="slotview%d" src="/wp-uploads/%s%s" height="%d" class="slotview"></div>',$r->ID,$folder,$large['file'],$thumbnail['height']);
	
		}
		
		
		$out[] = '</table>';
		$out[] = '</form><!-- .slideshow-definition-form -->';
		
		
		$out[] = '</td><!-- .slideshow-dragzone -->';
		$out[] = '</tr><!-- .master-row -->';
		$out[] = '</table><!-- .slideshow-drag-drop-layout -->';
		
		
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
		$out[] = '<button class="slideshow-text-slide-link-btn">Link to ...</button>';
		$out[] = '</td>';
		$out[] = '</tr>';
		
		$out[] = '<tr>';
		$out[] = '<td class="slideshow-text-slide-save-box">';
		$out[] = '<button class="slideshow-text-slide-cancel-btn">Cancel</button>';
		$out[] = '<button class="slideshow-text-slide-save-btn">Add the slide</button>';
		$out[] = '</td>';
		$out[] = '</tr>';

		
		$out[] = '</table><!-- .slideshow-text-slide-create -->';
		
		
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
				$out[] = '<img src="'.plugins_url('/imgs/NoThumbnails.png',__FILE__) .'" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-1" value="no-thumbs">';
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
				$out[] = '<img src="'.plugins_url('/imgs/VerticalThumbnails.png',__FILE__) .'" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-2" value="no-thumbs">';
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
				$out[] = '<img src="'.plugins_url('/imgs/HorizontalThumbnails.png',__FILE__) .'" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-3" value="no-thumbs">';
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
				$out[] = '<img src="'.plugins_url('/imgs/HorizontalSlide.png',__FILE__) .'" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-4" value="no-thumbs">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-4">Slide Horizontal</label>';
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
				$out[] = '<img src="'.plugins_url('/imgs/VerticalSlide.png',__FILE__) .'" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-5" value="no-thumbs">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-5">Slide Vertical</label>';
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
				$out[] = '<img src="'.plugins_url('/imgs/Fade.png',__FILE__) .'" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-6" value="no-thumbs">';
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
		
			
		$out[] = '<p class="submit">';
		$out[] = '<input type="submit" value="Save Changes" class="button button-primary" id="coop-slides-setup-submit" name="submit">';
		$out[] = '</p>';
		
		echo implode("\n",$out);
		
	}
	
	
	public function slideshow_setup_footer_script() {
	/*
	
		$out = array('<script type="text/javascript">');
		
		$out[] = 'function notify( evt ) {';
		$out[] = '    console.log( evt.type + ", " + jQuery(this).data("img-id")); ';
		$out[] = '};';
		
		$out[] = 'function dragstart_handler( evt, ui ) {';
		$out[] = '  console.log( "start " + jQuery(this).data("img-id"))  ';
		$out[] = '};';
		
		$out[] = 'function dragstop_handler( evt, ui ) {';
		$out[] = '  console.log( "stop " + jQuery(this).data("img-id"))  ';
		$out[] = '};';
		
		$out[] = 'jQuery().ready(function(){ ';
		$out[] = "   jQuery('.draggable').draggable({ cursor: 'move', stack:'.slide', snap:'.snappable', start: dragstart_handler, stop: dragstop_handler } ); ";
		$out[] = "   jQuery('.droppable').droppable(); ";
		$out[] = '});';
		$out[] = '</script>';
		
		echo implode( "\n", $out );
	
*/	
	}
	
	
	
	public function publish_slider_settings() {
	
		global $wpdb;
		$tag = '_'.$this->slug.'_';
		$sql = "SELECT option_name as name, option_value as val FROM $wpdb->options WHERE option_name LIKE '".$tag."%'";
		
		error_log($sql);
		
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
	
	
	private function parseSlideshowDefaults() {
	
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
		
	

	public function slideshow_admin_settings_page() {
				
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
		
		$out[] = self::parseSlideshowDefaults();
					
		$out[] = '</table>';
		
		$out[] = '<p class="submit">';
		$out[] = '<input type="submit" value="Save Changes" class="button button-primary" id="coop-slideshow-submit" name="submit">';
		$out[] = '</p>';
		
		echo implode("\n",$out);
	}
	
	
	public function slideshow_settings_save_changes() {
		
		error_log(__FUNCTION__);
		
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
	
	
	
	
	
	
	
	
}

if ( ! isset($slideshow) ) {
	$slideshow = new Slideshow();
}
	
endif; /* ! class_exists */