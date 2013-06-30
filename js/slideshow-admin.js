/**
 * @package SlideshowSetup
 * @copyright BC Libraries Coop 2013
 *
 **/
 
;(function($,window) {

	var self,
	 	_defaults = {},		// bxSlider
		_configured = {},	// passed in options
		current = {},		// _defaults + _configured
		opts = {};			// opts == current at start up (diverges as user changes settings)
	
	var SlideShow = function( options ) {
		this.init( options );
	}
	
	SlideShow.prototype  =  {
	
		init: function( options ) {
			
			self = this; // reference back to our global self
			
			// load the definitional default set by bxSlider
			this._defaults = $.extend( {}, this._defaults, window.coop_bx_defaults );
			// split out default from tuples (first in list)
			this.clean_up_defaults();

			// capture and save the configuration we were started up with (options as passed in)
			this._configured = $.extend( {}, options );

			// now load our current values as set by Slideshow settings controls
			this.opts = $.extend( {}, this._defaults, options );
			
			// duplicate starting config as current config - this gets changes by user
			this.current = $.extend( {}, this._defaults, options );
			
			// bind the html form fields to this.current fields
			var p;
			for( p in this.current ) {
				if( typeof p !== 'function' ) {
				//	console.log( p + ': ' + this.current[p] );
					$( 'input[name="'+p+'"]' ).on('change', this.set_current_value );	
				}
			}
							
			$('#coop-slideshow-submit').click( this.save_changes );	
			
			return this;
		},
		
		
		clean_up_defaults: function() {
			/**
			*	Some of the defaults are spec'd as csv alternate string values
			*	The first in the tuple is the default value. Find and set that.
			**/
			var p;
			for( p in self._defaults ) {
				if( typeof p !== 'function' ) {
					var v = self._defaults[p];
					var comma = ",";
					if( typeof v === 'string' ) {
						var a = v.split(comma);
						if( a.length > 1 ) {			
							self._defaults[p] = a[0];
						}
					}
				}
			}
		},
		
		
		save_changes: function() {
			
			// save button has been clicked 
			console.log( 'save button has been clicked ' );
			
			// determine which settings are now different ( 
			var p;
			var changed = {}; 
			var keys = [];
			for( p in self.opts ) {
				if( typeof p !== 'function' ) {
				//	console.log( p +': ' + self.opts[p] + ' <=> ' + self.current[p] );
				
					if( self.opts[p] != self.current[p]) {
						keys.push(p);
				//		console.log( p + ' has changed' );
						changed[p] = self.current[p];
					}
				}
			}
			// if changed is still an empty object ... 
			if( changed === {} ) {
			//	console.log( 'nothing has changed' );
				return false;
			}
			// otherwise continue to build data object to send server-side
			
			changed['action'] = 'coop-save-slideshow-change';
			// because the exact changes are arbitrary, pass the array of keys as well 
			changed['keys']   = JSON.stringify(keys); 
			
			$.post( ajaxurl, changed ).complete(function(r) {
				var res = JSON.parse(r.responseText);
				alert( res.feedback );
			});
		},
		
		set_current_value: function() {
			// update self.current to reflect the user's changes
			var id = this.getAttribute('name');
			self.current[id] = this.value;
			console.log( id + ': ' + self.current[id] );
		}
				
		
	}
	
	$.fn.coop_slideshow = function(opts) {
		//alert('here');
		return new SlideShow(opts);
	} 

}(jQuery,window));


jQuery().ready(function(){
	
	window.coop_slideshow = jQuery().coop_slideshow();
	
});