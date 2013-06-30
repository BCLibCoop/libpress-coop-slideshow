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
		
		
		global $wpdb;
		$sql = sprintf("SELECT * FROM $wpdb->options WHERE option_name LIKE '_%s_%%'",$this->slug);
		$res = $wpdb->get_results($sql);
		$_options = array();
		foreach( $res as $r ) {
			$_options[$r->option_name] = $r->option_value;
		}
				
		$lines = file( dirname(__FILE__).'/inc/default-settings.js');
		
		$fmt = '<tr><th>%s</th><td>%s</td><td>%s</td></tr>';
		foreach( $lines as $l ) {
		
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
				$out[] = '<tr><th><h3>'.$h.'</h3></th><td>Current</td><td>Default</td></tr>';
				continue;
			}
	
			list($t,$s) = explode(": ",rtrim($l,",\n "));
			
			$opt = 'NOOPT';
			$k = '_'.$this->slug.'_'.$t;
			if ( array_key_exists($k,$_options) ) {
				$opt = $_options[$k];
			}
			
			if( FALSE !== strpos($s,',')) {
				// multiple term radio
				$b = array();
				$default = '';
				$pcs = explode(',',$s);
				for( $i=0;$i<count($pcs); $i++ ) {
					$p = str_replace("'",'',$pcs[$i]);					
					
					if($opt!=='NOOPT' && $opt == $p ) {
						$b[] = sprintf('<input type="radio" name="%s" id="%s%d" value="%s" checked="checked">',$t,$t,$i,$p);	
					}
					else {
						$b[] = sprintf('<input type="radio" name="%s" id="%s%d" value="%s"%s>',$t,$t,$i,$p,(($i==0)?' checked="checked"':''));
					}
				
					if( $i == 0 ) {
						$default = $p;
					}
					$b[] = sprintf('<label for="%s%d">%s</label>',$t,$i,$p);	
				}
				$out[] = sprintf($fmt, $t, implode("\n",$b), $default);
				
			}
			else if( FALSE !== strpos($s,'true')) {
				// radio group: default true
				$b = array();
				$b[] = sprintf('<input type="radio" name="%s" id="%s-t" value="true" checked="checked">',$t,$t);
				$b[] = sprintf('<label for="%s-t">true</label>',$t);
				$b[] = sprintf('<input type="radio" name="%s" id="%s-f" value="false">',$t,$t);
				$b[] = sprintf('<label for="%s-f">false</label>',$t);
				$out[] = sprintf($fmt, $t, implode("\n",$b), 'true');
			}
			else if( FALSE !== strpos($s,'false')) {
				// radio group: default false
				$b = array();
				$b[] = sprintf('<input type="radio" name="%s" id="%s-t" value="true">',$t,$t);
				$b[] = sprintf('<label for="%s-t">true</label>',$t);
				$b[] = sprintf('<input type="radio" name="%s" id="%s-f" value="false" checked="checked">',$t,$t);
				$b[] = sprintf('<label for="%s-f">false</label>',$t);
				$out[] = sprintf($fmt, $t, implode("\n",$b), 'false');
			}
			else if( FALSE !== strpos($s,"'")) {
				// quoted string value - strip quotes
				$s = str_replace("'",'',$s);
				$i = sprintf('<input type="text" name="%s" id="%s" value="%s">',$t,$t,$s);
				$out[] = sprintf($fmt, $t, $i, $s );
			}
			else {
				// text field
				$i = sprintf('<input type="text" name="%s" id="%s" value="%s">',$t,$t,$s);
				$out[] = sprintf($fmt, $t, $i, $s);
			}
			
		}
			
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