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
 * Version: 0.3.2
 **/
 
if ( ! class_exists( 'Slideshow' )) :

class Slideshow {

	var $slug = 'slideshow';
	var $sprite = '';
	var $show;

	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	}

	public function _init() {
		
		self::init_slideshow();
		
		if( ! is_admin() ) 
		{
	//		error_log( __CLASS__ .'::'. __FUNCTION__ );		
		}
	}
	
	private function init_slideshow() {
		
		global $wpdb;		

		$table_name = $wpdb->prefix . 'slideshows';
		$this->show = $wpdb->get_row("SELECT * FROM $table_name WHERE is_active=1");
		if( $this->show == NULL ) {
			$this->show = $wpdb->get_row("SELECT * FROM $table_name ORDER BY date DESC LIMIT 1");	
		}
	}
	
	public function loader_script() {
		
		$layout = $this->show->layout;
		$transition = $this->show->transition;
		$captions  = $this->show->captions;
		
		$out = array('<script type="text/javascript">');
		$out[] = 'jQuery().ready(function() { ';
		
		if( $layout == 'no-thumb' ) {	
			$out[] = '  window.slideshow_custom_settings.pager = false;';
			$out[] = '  window.slideshow_custom_settings.controls = true;';
		}
		else {
			$out[] = '  window.slideshow_custom_settings.pager = true;';
			$out[] = '  window.slideshow_custom_settings.controls = false;';
		}
		
		$out[] = '  window.slideshow_custom_settings.autoPlay = true;';
	//	$out[] = '  window.slideshow_custom_settings.easing = null;';
		
		$out[] = '  window.slideshow_custom_settings.captions = '.$captions.';';

		$out[] = '  window.slideshow_custom_settings.layout = "'.$layout.'";';
		$out[] = '  window.slideshow_custom_settings.mode = "'.$transition.'";';
				
		$out[] = '	jQuery(".slider").bxSlider( window.slideshow_custom_settings ); ';
		$out[] = '});';
		$out[] = '</script>';
		
		echo implode("\n",$out);
	}
	
	
	public function fetch_styles_uri() {
		
		//  get_template_directory_uri() . '/bxslider/themes/theme/pn-theme.css
		//  get_template_directory_uri() . '/bxslider/themes/v-theme/v-theme.css
		//  get_template_directory_uri() . '/bxslider/themes/h-theme/h-theme.css
		
			
		if( $this->show->layout == 'no-thumb' ) {
		
			$theme = get_option('_'.$this->slug.'_prevNextCSSFile');
			$dir = str_replace('.css','',$theme);
		
			return get_template_directory_uri() . '/bxslider/themes/'.$dir.'/'.$theme;
		}
		else if(  $this->show->layout == 'vertical' ) {
		
			$theme = get_option('_'.$this->slug.'_verticalThumbsCSSFile');
			$dir = str_replace('.css','',$theme);
		
			return get_template_directory_uri() . '/bxslider/themes/'.$dir.'/'.$theme;
		}
		
		$theme = get_option('_'.$this->slug.'_horizontalThumbsCSSFile');
		$dir = str_replace('.css','',$theme);
		
		return get_template_directory_uri() . '/bxslider/themes/'.$dir.'/'.$theme;
		
	}
	
	
	public function fetch_markup() {
		
		global $wpdb, $slideshow_manager;
		
		$out = array();
		$slide_ml = array();
		$pager_ml = array();
				
		$out[] = '<div class="hero row" role="banner">';
		$out[] = '<div id="slider" class="slider">';
									
		if( $this->show->layout !== 'no-thumb' ) {
			$pager_class = get_option('_slideshow_pagerCustom');
			$pager_class = str_replace('.','',$pager_class);
			$pager_ml[] = '<div class="row '.$pager_class.' '.$this->show->layout.'">';
		}
		
		
		$table_name =  $wpdb->prefix . 'slideshow_slides';
		$id = $this->show->id;
		$slides = $wpdb->get_results("SELECT * FROM $table_name WHERE slideshow_id = $id ORDER BY ordering");
		foreach( $slides as $slide ) {
			
			if( $slide->post_id != null ) {
				$meta = $slideshow_manager->slideshow_fetch_img_meta($slide->post_id);
				self::build_image_slide( $this->show, $slide, $meta, &$slide_ml, &$pager_ml );
			}
			else {
				self::build_text_slide( $this->show, $slide, &$slide_ml, &$pager_ml );
			}
		}
		
		if( $this->show->layout !== 'no-thumb' ) {
			$pager_ml[] = '</div><!-- end of pager -->';
		}
		else {
			// inject controls for prev/next
			$pager_ml[] = '<div class="pn-controls">';
			$pager_ml[] = '<div class="pn-controls-direction">';
			$pager_ml[] = '<a href class="pn-prev">Prev</a>';
			$pager_ml[] = '<a href class="pn-next">Next</a>';
			$pager_ml[] = '</div><!-- .pn-controls-direction -->';
			$pager_ml[] = '</div><!-- .pn-controls -->';
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
			
		$url = $meta['folder'] . $meta['large']['file'];
		
		$slide_ml[] = '<img src="'.$url.'"  alt="'.$slide->text_title.'" title="'.$slide->text_title.'" >';
		if( $slide->slide_link != null ) {
			$slide_ml[] = '</a>';
		}
		$slide_ml[] = '</div><!-- .slide.image -->';
		
		if( $show->layout !== 'no-thumb' ) {
		
			$url = $meta['folder'] . $meta['thumb']['file'];
		
			$pager_ml[] = '<a href="" data-slide-index="'.$slide->ordering.'">';
			$pager_ml[] = '<div class="thumb image">';
			$pager_ml[] = '<img class="pager-thumb" src="'.$url.'" alt="'.$slide->text_title.'" >';
			$pager_ml[] = '</div></a>';
		}
	}
	
	private function build_text_slide( $show, $slide, $slide_ml, $pager_ml ) {
		
		$slide_ml[] = '<div class="slide text">';
		if( $slide->slide_link != null ) {
			$slide_ml[] = '<a href="'.$slide->slide_link.'">';	
		}
		$slide_ml[] = '<h2>'.stripslashes($slide->text_title).'</h2><p>'.stripslashes($slide->text_content).'</p>';
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