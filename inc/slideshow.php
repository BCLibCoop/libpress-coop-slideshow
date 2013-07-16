<?php defined('ABSPATH') || die(-1);

/**
 * @package Slideshow - frontside support
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Slideshow
 * Description: Slideshow frontside theme support script.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.2.0
 **/
 
if ( ! class_exists( 'Slideshow' )) :

class Slideshow {

	var $slug = 'slideshow';
	var $sprite = '';

	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	//	add_action( 'init', array( &$this, 'create_slide_post_type'));
	}

	public function _init() {
		
		if( ! is_admin() ) 
		{
	//		error_log( __CLASS__ .'::'. __FUNCTION__ );		
		}
	}
	
	public function loader_script() {
		
		global $wpdb;
		
		$table_name =  $wpdb->prefix . 'slideshows';
		$shows = $wpdb->get_results("SELECT * FROM $table_name WHERE is_active=1");
		
		$layout;
		$transition;

		foreach( $shows as $show ) {
			$layout = $show->layout;
			$transition = $show->transition;
			break;
		}
		
		$out = array('<script type="text/javascript">');
		$out[] = 'jQuery().ready(function() { ';
		
		$out[] = '  window.slideshow_custom_settings.layout = "'.$layout.'";';
		$out[] = '  window.slideshow_custom_settings.transition = "'.$transition.'";';
				
		$out[] = '	jQuery(".slider").bxSlider( window.slideshow_custom_settings ); ';
		$out[] = '});';
		$out[] = '</script>';
		
		echo implode("\n",$out);
	}
	
	
	public function fetch_styles_uri() {
		
		//  get_template_directory_uri() . '/bxslider/themes/theme/pn-theme.css
		//  get_template_directory_uri() . '/bxslider/themes/v-theme/v-theme.css
		//  get_template_directory_uri() . '/bxslider/themes/h-theme/h-theme.css
		
		global $wpdb;
		
		$table_name =  $wpdb->prefix . 'slideshows';
		$shows = $wpdb->get_results("SELECT * FROM $table_name WHERE is_active=1");
		$done_one = false;
		foreach( $shows as $show ) {
			if( $done_one ) {
				break;
			}
			
			if( $show->layout == 'no-thumb' ) {
			
				$theme = get_option('_'.$this->slug.'_prevNextCSSFile');
				$dir = str_replace('.css','',$theme);
			
				return get_template_directory_uri() . '/bxslider/themes/'.$dir.'/'.$theme;
			}
			else if(  $show->layout == 'vertical' ) {
			
				$theme = get_option('_'.$this->slug.'_verticalThumbsCSSFile');
				$dir = str_replace('.css','',$theme);
			
				return get_template_directory_uri() . '/bxslider/themes/'.$dir.'/'.$theme;
			}
			
			$theme = get_option('_'.$this->slug.'_horizontalThumbsCSSFile');
			$dir = str_replace('.css','',$theme);
			
			return get_template_directory_uri() . '/bxslider/themes/'.$dir.'/'.$theme;
			
			
			// we should never get here ...
			error_log( __CLASS__.'::'.__FUNCTION__.': failed to return stylesheet uri');
			$done_one = true;
		}
	}
	
	
	public function fetch_markup() {
		
		global $wpdb, $slideshow_manager;
		
		$out = array();
		$slide_ml = array();
		$pager_ml = array();
		
		$table_name =  $wpdb->prefix . 'slideshows';
		$shows = $wpdb->get_results("SELECT * FROM $table_name WHERE is_active=1");
		
		$out[] = '<div class="hero row" role="banner">';
		$out[] = '<div id="slider" class="row slider">';
				
		// there should only be one active at any given moment
		// but a safe guard is worthwhile I suppose
		$done_one = false;
		foreach( $shows as $show ) {
			
			if( $done_one ) {
				break;
			}
		//	error_log( $show->title );
			
			if( $show->layout !== 'no-thumb' ) {
				$pager_class = get_option('_slideshow_pagerCustom');
				$pager_class = str_replace('.','',$pager_class);
				$pager_ml[] = '<div class="row '.$pager_class.' '.$show->layout.'">';
			}
			

			$table_name =  $wpdb->prefix . 'slideshow_slides';
			$slides = $wpdb->get_results("SELECT * FROM $table_name WHERE slideshow_id = $show->id ORDER BY ordering");
			foreach( $slides as $slide ) {
				
				if( $slide->post_id != null ) {
					$meta = $slideshow_manager->slideshow_fetch_img_meta($slide->post_id);
					self::build_image_slide( $show, $slide, $meta, &$slide_ml, &$pager_ml );
				}
				else {
					self::build_text_slide( $show, $slide, &$slide_ml, &$pager_ml );
				}
			}
			
			if( $show->layout !== 'no-thumb' ) {
				$pager_ml[] = '</div><!-- end of pager -->';
			}
			
			// make certain we don't list two slideshows at one time
			$done_one = true;
		}
		
		$slide_ml[] = '</div><!-- #slider.row.slider -->';
		
		$out = array_merge( $out, $slide_ml,  $pager_ml );
				
		$out[] = '</div><!-- .hero.row -->';
		
		return implode( "\n", $out );
	}
	
	private function build_image_slide( $show, $slide, $meta, $slide_ml, $pager_ml ) {
		
		$slide_ml[] = '<div class="slide image">';
		if( $slide->slide_link != null ) {
			$slide_ml[] = '<a href="'.$slide->slide_link.'">';	
		}
		//$slide_ml[] = '<h2>'.$slide->text_title.'</h2>';
		
		$hostdomain = '';	// allow to default to current domain in browser
		if( $meta['source'] == 'network' ) {
			$hostdomain = get_bloginfo('wpurl');	// should be url of blog 1		
		}
		
		$url = $hostdomain . $meta['folder'] . $meta['large']['file'];
		
		$slide_ml[] = '<img src="'.$url.'" >';
		if( $slide->slide_link != null ) {
			$slide_ml[] = '</a>';	
		}
		$slide_ml[] = '</div><!-- .slide.image -->';
		
		if( $show->layout !== 'no-thumb' ) {
		
			$url = $hostdomain . $meta['folder'] . $meta['thumb']['file'];
		
			$pager_ml[] = '<a href="" data-slide-index="'.$slide->ordering.'">';
			$pager_ml[] = '<div class="thumb image">';
			$pager_ml[] = '<img class="pager-thumb" src="'.$url.'" >';
			$pager_ml[] = '</div></a>';
		}
	}
	
	private function build_text_slide( $show, $slide, $slide_ml, $pager_ml ) {
		
		$slide_ml[] = '<div class="slide text">';
		if( $slide->slide_link != null ) {
			$slide_ml[] = '<a href="'.$slide->slide_link.'">';	
		}
		$slide_ml[] = '<h2>'.$slide->text_title.'</h2><p>'.$slide->text_content.'</p>';
		if( $slide->slide_link != null ) {
			$slide_ml[] = '</a>';	
		}
		$slide_ml[] = '</div><!-- .slide.text -->';
		
		
		if( $show->layout !== 'no-thumb' ) {
		
			$pager_ml[] = '<a href="" data-slide-index="'.$slide->ordering.'">';
			$pager_ml[] = '<div class="thumb text">';
			$pager_ml[] = '<div class="pager-thumb text-thumb">T</div>';
			$pager_ml[] = '</div></a>';
			
		}	
	}	
}

if ( ! isset( $slideshow ) ) {
	global $slideshow; 
	$slideshow = new Slideshow();
}
	
endif; /* ! class_exists */