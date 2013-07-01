<?php defined('ABSPATH') || die(-1);

/**
 * @package SlideshowSetup
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Slideshow Setup
 * Description: Slideshow configurator.  NETWORK ACTIVATE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.1.0
 **/
 
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
	//	wp_register_style( 'coop-slideshow', 	plugins_url( '/css/slideshow.css', __FILE__ ), false );
	//	wp_enqueue_style( 'coop-slideshow' );
	//	wp_enqueue_script( 'coop-slideshow-js' );
	}
	
	public function admin_enqueue_styles_scripts($hook) {
	
		error_log($hook);
	
		if( 'site-manager_page_slideshow' !== $hook ) {
			return;
		}

		wp_register_style( 'coop-slideshow-admin-css', plugins_url( '/css/slideshow-admin.css', __FILE__ ), false );
		wp_register_script( 'coop-slideshow-admin-js', plugins_url( '/js/slideshow-admin.js',__FILE__), array('jquery'));
		wp_register_script( 'coop-slideshow-defaults-js', plugins_url( '/inc/default-settings.js',__FILE__), array('jquery'));
				
		wp_enqueue_style( 'coop-slideshow-admin-css' );
		wp_enqueue_script( 'coop-slideshow-admin-js' );
		wp_enqueue_script( 'coop-slideshow-defaults-js' );	//  template of bxSlider's default values.
			// we use this to test whether the user has altered any settings to know what we have to save.
		
	}
	
	
	public function add_slideshow_menu() {
		add_submenu_page( 'site-manager', 'Slideshow Settings', 'Slideshow Settings', 'edit_options','slideshow', array(&$this,'slideshow_admin_settings_page'));
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
		
		$out[] = Slideshow::parseSlideshowDefaults();
					
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