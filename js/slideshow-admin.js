/**
 * @package Slideshow Setup 
 * @copyright BC Libraries Coop 2013
 *
 **/

;(function($,window) {

	var self,
		_configured = {},	// passed in options
		opts = {};			// opts == current at start up (diverges as user changes settings)
	
	var SlideShowSetup = function( options ) {
		this.init( options );
	}
	
	SlideShowSetup.prototype  =  {
	
		init: function( options ) {
			
			self = this;
		},
		
		dragstart: function( evt, ui ) {
			
		},
		
		dragstop: function( evt, ui ) {
			
			var d = $(this);
			
		//	console.log( d.data('img-id') + ': ' + ui.position.left + ', ' + ui.position.top  );
			
		},
		
		dropped: function( evt, ui ) {
		
			var dropzone = $(this).attr('id');
			var d = ui.draggable;
			
			var id = d.data('img-id');
			var cap = d.data('img-caption');

		//	console.log( 'caption ' + cap );
		//	console.log( 'dropped object id ' + id );

			var t = $('#thumb'+id);
			var src = t.attr('src');
			
			var img = $('<img src="'+src+'" class="selected" id="selected'+d+'">');
			
			$('#'+ dropzone).empty().append( img );
			$('#'+ dropzone).next().empty().text( cap );
			
		//	console.log( 'droppped on ' + dropzone );
			
		},
		
		over_drop: function( evt, ui ) {
			console.log( 'over drop zone' );
		},
		
		drag_representation: function( evt ) {
		
			var d = $(this).data('img-id');
			var t = $('#slotview'+d);
			var src = t.attr('src');
			console.log( src );
			var img = $('<img src="'+src+'" class="slotview" height="49" id="slotcopy'+d+'">');
			return $('<div class="slideshow-drag-helper draggable"></div>').append(img.show());
		}
		
	}
	
	$.fn.coop_slideshow_setup = function(opts) {
		//alert('here');
		return new SlideShowSetup(opts);
	} 

}(jQuery,window));
			

/**
 * @package Slideshow Settings
 * @copyright BC Libraries Coop 2013
 *
 **/

;(function($,window) {

	var self,
		_configured = {},	// passed in options
		current = {},		// _defaults + _configured
		_defaults = {},		// bxSlider factory settings
		_touched,			// record keys of fields altered until a save
		opts = {};			// opts == current at start up (diverges as user changes settings)
	
	var SlideShowSettings = function( options ) {
		this.init( options );
	}
	
	SlideShowSettings.prototype  =  {
	
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
			
			this._touched = [];
			
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
		//	console.log( 'save button has been clicked ' );
			
			// determine which settings are now different ( 
			var p;
			var changed = {}; 
			var keys = [];
			for( p in self.opts ) {
				if( typeof p !== 'function' ) {
					if( self.opts[p] !== self.current[p]) {
						keys.push(p);
						changed[p] = self.current[p];
					}
					else {
						var i;
						for( i in self._touched ) {
							if( typeof i !== 'function' ){	
								if( i == p ) {
									keys.push(p);
									changed[p] = self.current[p];
									break;
								}
							}
						}
					}
				}
			}
		
			// if changed is still an empty object ... 
			if( changed === {} || keys.length === 0 ) {
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
				
				self._touched = [];
				
			});
		},
		
		touched: function( id ) {
			this._touched.push( id );
		//	console.log( this._touched );
		},
		
		set_current_value: function() {
		
			// update self.current to reflect the user's changes
			var id = this.getAttribute('name');
			var val = this.value;
			if( val == '' ) {
				val = 'empty';
			}
			self.current[id] = val; 
			self.touched( id );
		}
	}
	
	$.fn.coop_slideshow_settings = function(opts) {
		//alert('here');
		return new SlideShowSettings(opts);
	} 

}(jQuery,window));


jQuery().ready(function(){
	
	window.coop_slideshow_settings = jQuery().coop_slideshow_settings();
	
	window.slideshow_setup = jQuery().coop_slideshow_setup();
	
	jQuery('.draggable').draggable({ cursor:'move', 
									 stack:	'.slide', 
									/*  snap:	'.snappable',  */
									 start: slideshow_setup.dragstart, 
									 stop:  slideshow_setup.dragstop,
									 helper: slideshow_setup.drag_representation
								});
									 
	jQuery('.droppable').droppable({ drop:  slideshow_setup.dropped,
									 over:  slideshow_setup.over_drop,
									 out:   slideshow_setup.leave_drop,
									 hoverClass: 'drop_highlight' 
								});
	
});