<?php defined('ABSPATH') || die(-1);

/**
 * @package Slideshow Defaults 
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Slideshow Defaults administration UI
 * Description: Global configuration of a slideshow environment within a blog.  NETWORK ACTIVATE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.2.0
 **/
 
if ( ! class_exists( 'SlideshowDefaults' )) :
	
class SlideshowDefaults {

	var $slug = 'slideshow';
	var $db_init = false;

	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	//	add_action( 'init', array( &$this, 'create_slide_post_type'));
	
		$this->db_init = get_option('_slideshow_db_init');
	
	}

	public function _init() {
	//	error_log( __FUNCTION__ );
	
		if( is_admin() ) 
		{
			add_action( 'wp_ajax_coop-save-slideshow-change', array( &$this, 'slideshow_defaults_save_changes'));
		}
	
	}
	
	/**
	*	Store / adjust settings from the global Slideshow Settings long form of options.
	*
	**/
	public function slideshow_defaults_page() {
				
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
		
		$out[] = self::slideshow_defaults_parse_defaults();
					
		$out[] = '</table>';
		
		$out[] = '<p class="submit">';
		$out[] = '<input type="submit" value="Save Changes" class="button button-primary" id="coop-slideshow-settings-submit" name="submit">';
		$out[] = '</p>';
		
		echo implode("\n",$out);
	}
	
	public function slideshow_defaults_save_changes() {
		
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
	
	public function slideshow_defaults_publish_config() {
	
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
	
	private function slideshow_defaults_parse_defaults() {
	
		$lines = file( dirname(__FILE__).'/default-settings.js');
		
		$all_defaults = array();
		
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
			
			$all_defaults[$t] = $default;

			$out[] = sprintf($_fmt,$t,implode("&nbsp;&nbsp;",$widget),$default);
		}
		
		if( empty($this->db_init) || $this->db_init == FALSE ) {
			foreach( $all_defaults as $term => $val ) {
				update_option('_'.$this->slug.'_'.$term, $val );
			}
			update_option('_'.$this->slug.'_db_init', true );
		}
		
		
		return implode("\n",$out);
	}
}


	
if ( ! isset( $slideshow_defaults ) ) {
	global $slideshow_defaults; 
	$slideshow_defaults = new SlideshowDefaults();
}
	
endif; /* ! class_exists */