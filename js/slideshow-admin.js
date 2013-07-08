/**
 * @package Slideshow Setup 
 * @copyright BC Libraries Coop 2013
 *
 **/

;(function($,window) {

	var self,
		_configured = {},	// passed in options
		opts = {},			// opts == current at start up (diverges as user changes settings)
		table_order;
	
	var SlideShowSetup = function( options ) {
		this.init( options );
	}
	
	SlideShowSetup.prototype  =  {
	
		init: function( options ) {
			
			self = this;
		},
		
		add_text_only_slide: function() {
			
			var slideshow_collection_name = $('.slideshow-collection-name').val();
			var slideshow_id = $('#slideshow_select').val();
			
			if( slideshow_collection_name == '' && null == slideshow_id ) {
				alert( 'Whoops! You need to name the slideshow first.' );
				$('.slideshow-collection-name').focus();
				return false;
			}
				
			var title = $('#slideshow-text-slide-heading').val();
			var content = $('#slideshow-text-slide-content').val();
			
			if( title == '' || content == '' ) {
				alert( 'You must enter a title and a message' );
				return false;
			}
			
			var data = {
				action: 'slideshow_add_text_slide',
				slideshow_name: slideshow_collection_name,
				slideshow_id: slideshow_id,
				title: title,
				content: content
			};
			
			$.post( ajaxurl, data ).complete( function(r) {
				var res = JSON.parse(r.responseText);
				
				if( res.result === 'success' ) {
					alert( 'Text slide saved' );
					self.clear_text_slide_form();
					var id = res.slide_id;
					self.place_text_slide(id,title,content);
				}
				else {
					alert( 'Unable to save the text slide.' );
					$('#slideshow-text-slide-heading').focus();
				}
			});

		},
		
		clear_text_slide_form: function() {
			$('#slideshow-text-slide-heading').empty().val('');
			$('#slideshow-text-slide-content').empty().val('');
			// maybe URL subform too 
		},
			
		dragstart: function( evt, ui ) {
	
		},
		
		dragstop: function( evt, ui ) {
				
		},
		
		drop_on_row: function( evt, ui ) {
		
			var row = this.id;	
			var dragged = ui.draggable;
			
			if( $(dragged).hasClass('slideshow-collection-row') ) {
				self.drop_insert_row( this, ui );
			}
			else {
				self.drop_insert_thumbnail( row, dragged, this );		
			}
		},
		
		drop_insert_row: function( row, ui )	{
			
			var $t = $(row); // this
			var dragged = ui.draggable;
			var dropzone_id = $($t).attr('id');
			var dropped_id = $(dragged).attr('id');
			var dropme = $(dragged).detach();
			
			$($t).before(dropme);
			
		//	var rows = $('.slideshow-sortable-rows').children().children();
		//	console.log( rows );
		
		},
		
		drop_insert_thumbnail: function( row, dragged, target )	{
		
			var id = dragged.data('img-id');
			var cap = dragged.data('img-caption');
			var link = dragged.data('img-link');
			
			var thumb = $('#thumb'+id);
			var src = thumb.attr('src');
			var w = thumb.attr('width');
			var h = thumb.attr('height');
			
			var thumbbox = $('.thumbbox',target);
			var img = $('<img data-img-id="' + id + '" src="'+src+'" class="selected" id="selected'+row+'" width="' + w + '" height="' + h + '">');
				thumbbox.empty().append(img);
			var textbox = thumbbox.next();
				linkspan = textbox.children().first();
				textbox.empty().text( cap ).append( linkspan.empty().append( link ));

		},
		
		/*
		dropped: function( evt, ui ) {
				
					var dropzone = $(this).attr('id');
					var d = ui.draggable;
								
					var id = d.data('img-id');
					var cap = d.data('img-caption');
		
					var t = $('#thumb'+id);
					var src = t.attr('src');
					var w = t.attr('width');
					var h = t.attr('height');
					
					var img = $('<img src="'+src+'" class="selected" id="selected'+d+'" width="' + w + '" height="' + h + '">');
					
					$('#'+ dropzone).empty().append( img );
					$('#'+ dropzone).next().empty().text( cap );
					
				//	console.log( 'droppped on ' + dropzone );
					
				},
		*/
		
		over_drop: function( evt, ui ) {
		//	console.log( 'over drop zone' );
		
			var dropzone = this.id;
		
			if( self._dragging ) {
				_dragee = $(self._dragging);
				
				console.log( 'dragee: ' + _dragee ); 
				
				if( _dragee.hasClass('slideshow-collection-row') ) {
					console.log( 'has class slideshow-collection-row' );
				}
			}			
		
		},
		
		drag_representation: function( evt ) {
		
			if( $(this).hasClass('slideshow-collection-row') ) {
				return self.drag_row_rep( evt, this );
			}
			else {
				return self.drag_thumb_rep( evt, this );
			}
		},
		
		drag_row_rep: function( evt, obj ) {
			
			var row = $(obj).clone();
			return row;
		},
		
		drag_thumb_rep: function( evt, obj ){
		
			var d = $(obj).data('img-id');
			var t = $('#slotview'+d);
			var src = t.attr('src');
		//	console.log( src );
			var img = $('<img src="'+src+'" class="slotview" height="49" id="slotcopy'+d+'">');
			return $('<div class="slideshow-drag-helper draggable"></div>').append(img.show());
		},
		
		first_empty_row: function() {
			var rows = $('.slideshow-collection-row');
			for( i=0;i<rows.length;i++) {
				var img = $('.thumbbox',rows[i]).children().first(); // should be an img or a para with a T in it
				if( img == null || img == undefined ) {
					return rows[i];
				}
				return false;
			}	
		},
				
		past_slideshow_selected: function() {
		
		//	console.log( $(this) );
			var opt = $('#slideshow_select option').filter(':selected');
			$('.slideshow-collection-name').val( $(opt).text() );
			var data = {
				action: 'slideshow-fetch-collection',
				slideshow_id: opt.val()
			};
			
			$.post( ajaxurl, data ).complete(function(r){
				var res = JSON.parse(r.responseText);
				var i;
				for( i=0; i<res.length;i++ ) {
				
					var row = $('.slideshow-collection-row').eq( i );
				
					if( res[i].post_id == "" ) {
	//					console.log( 'res['+i+'].post_id: empty - this is a text entry' ); 				
						self.place_slide_text( res[i].id, res[i].text_title,res[i].text_content, row);
					}
					else {
						// needs to include title/caption in db for image too - needs UI for setting same
						self.place_slide_img(res[i].id,res[i].post_id,res[i].slide_link,row );
					}
					
					
				} 
				
			});
		},
		
		place_slide_img: function( id, post_id, link, row ) {
			
			
				
		},
		
		place_slide_text: function( id, title, content, row ) {
		
			if( row == null ) {
				// get the first empty row ...
				row = self.first_empty_row();
			}
			
			$(row).children().first().empty().append($('<span class="slideshow-big-t">T</span>'));
			$(row).children().last().empty().append(title); 
			// do something for a mouseover popover showing the content 
			
		},
		
		precheck_slideshow_name: function() {
			
			var data = {
				action: 'precheck_slideshow_collection_name',
				slideshow_name: $('.slideshow-collection-name').val()
			};
			
			$.post( ajaxurl, data ).complete(function(r){
				var res = JSON.parse(r.responseText);
				if( res.result == 'found' ) {
					// not okay to use if keyed into field
					alert( 'A collection already exists with that name.' );
					$('.slideshow-collection-name').focus();
				}
				else {
					// okay to use if newly created
					self.show_checkmark();
				}
				
			});
				
		},
		
		save_collection: function() {
		
			var slides = [];
			var rows = $('.slideshow-collection-row');
			for( i=0;i<rows.length;i++) {
			
				var text_title, text_content, post_id, slide_link, ordering;
			
				var img = $(rows[i]).children().first().children('img');
				var type = 'image';		// bias inherent in the system :-)
				if( img === null ) {
					console.log( 'null img - text slide' );
					type = 'text';
				}
				
				slide_link = $(rows[i]).children().last().children('span').text();
				
				if( type === 'image' ) {
					post_id = $(img).data('img-id');
				}
				if( type === 'text' ) {
					text_title = $(rows[i]).children().last().text();
					text_content = $(rows[i]).children().last().children('div').text();
				}
								
				var slide = {
					type: type,
					text_title: text_title,
					text_content: text_content,
					slide_link: slide_link,
					post_id: post_id,
					ordering: i
				}
				slides.push( slide );
			}
		
			var data = {
				action: 'slideshow-save-slide-collection',
				title:	$('.slideshow-collection-name').val(),
				slideshow_id: $('#slideshow_select').val(),
				layout: $('input[name="slideshow-layout"]').val(),
				transition: $('input[name="slideshow-transition"]').val(),
				slides: slides
			};
			
			$.post(ajaxurl,data).complete(function(r){
				var res = JSON.parse(r.responseText);
				
				/// do something in response to the save attempt feedback ...
				
			});
			
			
		},
		
		
		/**
		*	image-click handler to set the layout style
		*	radio buttons when a graphic is clicked.
		**/
		set_layout_control: function() {
			var t = $(this);
			var id = t.data('id');
		//	console.log( id );
			$('#'+id).click();
		},
		
		show_checkmark: function() {
			alert( 'show checkmark in the right hand edge of the Collection name field' );
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
									 start:  slideshow_setup.dragstart, 
									 stop:   slideshow_setup.dragstop,
									 helper: slideshow_setup.drag_representation
								});
									 
	jQuery('.droppable').droppable({ drop:  slideshow_setup.drop_on_row,
									 over:  slideshow_setup.over_drop,
									 out:   slideshow_setup.leave_drop,
									 hoverClass: 'drop_highlight' 
								});
								
	jQuery('.slideshow-control-img').click( slideshow_setup.set_layout_control );
	
	jQuery('.slideshow-collection-name').blur( slideshow_setup.precheck_slideshow_name );
	
	jQuery("#slideshow_select").chosen().change( slideshow_setup.past_slideshow_selected );
	jQuery('.slideshow-text-slide-save-btn').click( function(event) {
			event.stopPropagation();
			slideshow_setup.add_text_only_slide();
		});
	jQuery('#coop-slides-setup-submit').click(function(event){
		event.stopPropagation();
		slideshow_setup.save_collection();
	});
	
	
});