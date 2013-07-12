/**
 * @package Slideshow Setup 
 * @copyright BC Libraries Coop 2013
 *
 **/

;(function($,window) {

	var self,
		_configured = {},	// passed in options
		opts = {},			// opts == current at start up (diverges as user changes settings)
		signalbox,				// for showing signals to the user in a floating div .sort-table-signal
		table_order;
	
	var SlideShowSetup = function( options ) {
		this.init( options );
	}
	
	SlideShowSetup.prototype  =  {
	
	
		init: function( options ) {
			
			self = this;
			$('#row-signal img').addClass('minus-enabled');
			self.signalbox = $('#row-signal').detach();
			
			$('td.slideshow-slide-title').hover( self.slide_hover_in, self.slide_hover_out );
			
			// a.k.a. [ Save name ] button 
			$('.slideshow-save-collection-btn').click( function(event) {
				event.stopPropagation();
				self.save_collection_name();
				return false;
			});
			
			$('.slideshow-text-slide-link-btn').click( function(event){
					event.stopPropagation();
					self.toggle_text_link_input();
					return false; 
			});
				
			$('.slideshow-text-slide-cancel-btn').click( function(event){
				event.stopPropagation();
				self.clear_text_slide_form();
				return false;
			});
			
			$('.slideshow-text-slide-save-btn').click( function(event) {
				event.stopPropagation();
				slideshow_setup.add_text_only_slide();
				return false;
			});
			
			$('.slideshow-runtime-information').click( function(event) {
				event.stopPropagation();
				self.runtime_calculation();
				return false;
			});		
			
			$('#collection-name-signal img.signals-sprite').addClass('tick-enabled');
			
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
			
			var is_active = $('#slideshow-is-active-collection').val();
				if( undefined === is_active ) {
					is_active = String() + '0';
				}
			
			if( title == '' || content == '' ) {
				alert( 'You must enter a title and a message' );
				return false;
			}
			
			var slide_link = $('.slideshow-text-slide-link-input').val();
			
			
			var data = {
				action: 'slideshow_add_text_slide',
				slideshow_name: slideshow_collection_name,
				slideshow_id: slideshow_id,
				title: title,
				content: content,
				slide_link: slide_link,
				is_active: is_active
			};
			
			$.post( ajaxurl, data ).complete( function(r) {
				var res = JSON.parse(r.responseText);
				
				if( res.result === 'success' ) {
					alert( 'Text slide saved' );
					self.clear_text_slide_form();
				/* 	var id = res.slide_id; */
				/* 	self.place_slide_text(id,title,content); */
				
					self.fetch_selected_slideshow();
				}
				else {
					alert( 'Unable to save the text slide.' );
					$('#slideshow-text-slide-heading').focus();
				}
			});
		},
		
		toggle_link_to_input: function() {
			
			if( $('.slideshow-text-slide-link-input').hasClass('hidden')) {
				self.activate_link_to_input();
			}
			else {
				self.deactivate_link_to_input();
			}
		},
		
		activate_link_to_input: function() {
			$('.slideshow-text-slide-link-input').removeClass('hidden');
		},
		
		deactivate_link_to_input: function() {
			$('.slideshow-text-slide-link-input').addClass('hidden');
		},
		
		clear_drop_table_rows: function() {
		
			var rows = $('.slideshow-collection-row');
			for( i=0;i<rows.length;i++) {	
				console.log( 'clearing row ' + i );
				$('.thumbbox',rows[i]).empty();
				$(rows[i]).data('slide-id','');
				$(rows[i]).children().last().text('');
				$(rows[i]).children().last().children('span').empty();
			}
		},
		
		clear_text_slide_form: function() {
			$('#slideshow-text-slide-heading').empty().val('');
			$('#slideshow-text-slide-content').empty().val('');
			$('.slideshow-text-slide-link-input').empty().val('');
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

			self.fetch_img_meta( id );


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
		
		fetch_img_meta: function( post_id ) {
			
			var data = {
				action: 'slideshow-fetch-img-meta',
				post_id: post_id
			}
			
			$.post( ajaxurl, data ).complete(function(r){
				var res = JSON.parse(r.responseText);
				if( res.result === 'success' ) {
					return res.meta;
				}
				else {
					alert( "Could not retrieve meta data for image: " + post_id );
					return false;
				}
			})
		},
		
		first_empty_row: function() {
		
			var rows = $('.slideshow-collection-row');
			for(var i=0;i<rows.length;i++) {
			
				var txt = $('.slideshow-slide-title',rows[i]).text(); 
				console.log( 'row '+i + ': ' + txt );
				if( txt.length == 0 ) {
					console.log( 'txt.length == 0' );
					return rows[i];
				}
			}
		},
				
		fetch_selected_slideshow: function() {
		
			self.clear_drop_table_rows();
		
		//	console.log( $(this) );
			var opt = $('#slideshow_select option').filter(':selected');
			$('.slideshow-collection-name').val( $(opt).text() );
			
			var data = {
				action: 'slideshow-fetch-collection',
				slideshow_id: opt.val()
			};
			
			$.post( ajaxurl, data ).complete(function(r){
				var res = JSON.parse(r.responseText);
				var slides = res.slides;
				if( res.is_active == 1 ) {
					$('#slideshow-is-active-collection').attr('checked','checked');
				}
				else {
					$('#slideshow-is-active-collection').removeAttr('checked');
				}
				
				var i;
				for( i=0; i<slides.length;i++ ) {
				
					var row = $('.slideshow-collection-row').eq( i );
					if( slides[i].post_id == null ) {	
						// this is a text entry
						self.place_slide_text( slides[i].id, slides[i].text_title, slides[i].text_content, slides[i].slide_link, row);
					}
					else {
						// needs to include title/caption in db for image too - needs UI for setting same
						self.place_slide_img( slides[i].id, slides[i].post_id, slides[i].slide_link, row );
					}
				}
				
				self.runtime_calculation();
			});
		},
		
		place_slide_img: function( id, post_id, link, row ) {
			
			console.log( 'called place_slide_img ' + id + ': ' + post_id );
			
			if( row == null ) {
				// get the first empty row ...
				row = self.first_empty_row();
			}
						
			var data = {
				action: 'slideshow-fetch-img-meta',
				post_id: post_id
			}
			
			$.post( ajaxurl, data ).complete(function(r){
				var res = JSON.parse(r.responseText);
				
				var meta = res.meta;
										
				var src = meta['folder'] + meta['thumb']['file'];
				var w = meta['thumb']['width'];
				var h = meta['thumb']['height'];
				
				$(row).attr( 'data-slide-id', id );
				
				var img = $('<img data-img-id="' + post_id + '" src="'+src+'" width="' + w + '" height="' + h + '">');
				$(row).children().first().empty().append( img );
						
				var title = $('<div class="slide-title" />').append(meta['title']);
					$(row).children().eq(1).empty().append( title );
				
				if( undefined !== link ) {
				
					var anchor = $('<a class="slide-anchor" target="_blank"/>').text( link ).attr('href',link);
					var div = $('<div class="slide-link" />').append( anchor );
					$(row).children().eq(1).append( div );
					
				}
			})	
		},
		
		place_slide_text: function( id, title, content, link, row ) {
		
		//	console.log( 'called place_slide_text - ' + id + ' - ' + content );
		
			if( row == null ) {
				// get the first empty row ...
				row = self.first_empty_row();
			}
			
			$(row).attr('data-slide-id',id);
		//	console.log( 'reading back: ' + $(row).data('slide-id') );
			$(row).children().first().empty().append($('<span class="slideshow-big-t">T</span>'));
			
			var titlediv = $('<div class="slide-title" />').append(title);
			
			$(row).children().eq(1).empty().append(titlediv); 
			
			if( undefined !== link ) {
				var anchor = $('<a class="slide-anchor" target="_blank"/>').text( link ).attr('href',link);
				var div = $('<div class="slide-link" />').append( anchor );
					$(row).children().eq(1).append( div );
			}
			$(row).children().eq(1).append( $('<div class="slideshow-content-popover" />').append( content ));
			
		},
		
		precheck_slideshow_name: function() {
			
			if( $('.slideshow-collection-name').val() === '' ) return;
			
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
		
		runtime_calculation: function() {
		
			var row = self.first_empty_row();
			if( ! row ) {
				row = $('#row0');
			}
			var index = $(row).attr('id').replace('row','');
			var dwell = parseInt(window.coop_slideshow_settings.current.pause) / 1000;
			var transit = parseInt(window.coop_slideshow_settings.current.speed) / 1000;
			
			var net = index * (dwell + transit);	// slideshow cycle in seconds
			
			var msg = "There are "+index+" slides in this slideshow. Each slide will show for "+dwell+" seconds. ";
				msg += "Transition between slides will take "+transit+" seconds. ";
				msg += "The slideshow will take a total of "+net+" seconds to cycle completely.";
			
			$('.slideshow-runtime-information').empty().text( msg );
		},
		
		save_collection_name: function() {
		
			var is_active = $('#slideshow-is-active-collection').is(':checked');
				console.log( 'is_active: ' + is_active );
				if( undefined === is_active ) {
					is_active = String() + '0';
				}
		
			var layout = $('input[name="slideshow-layout"]').filter(':checked').val();
				if( undefined === layout ) {
					layout = 'no-thumb';
				}
				
			var transition = $('input[name="slideshow-transition"]').filter(':checked').val();
				if( undefined === transition ) {
					transition = window.coop_slideshow_settings.current.mode;
				}
		
			var data = {
				action: 'slideshow-save-slide-collection',
				title:	$('.slideshow-collection-name').val(),
				slideshow_id: $('#slideshow_select').val(),
				layout: layout,
				transition: transition,
				is_active: is_active
			};
			
			$.post(ajaxurl,data).complete(function(r){
				var res = JSON.parse(r.responseText);
				
				/// do something in response to the save attempt feedback ...
				if( res.result === 'success' ) {
					alert( 'Slide collection metadata saved' );	
					self.fetch_selected_slideshow();			
				}
				else {
					alert( res.feedback );
				}		
			});			
					
		},
		
		save_collection: function() {
		
			var slides = [];
			var rows = $('.slideshow-collection-row');
			
			for( i=0;i<rows.length;i++) {
			
				// slide_id is set in the case of a collection having been reloaded into the editor 
				
				var type = 'image';		// bias inherent in the system :-)
				var text_title = '';
				var text_content = '';
				var post_id = '';
				var slide_id = ''; 
				var slide_link = '';
			
				var img = $(rows[i]).children().first().children('img');
				var img_id = $(img).data('img-id');
				
				if( img_id == undefined ) {
				
					type = 'text';		
					// popover - take it off
					var popover = $(rows[i]).children().last().children('div').detach();
					// link? - take it off
					slide_link = $(rows[i]).children().last().children('a').detach();
					// read this while it is gone
					text_title = $(rows[i]).children().last().text();
					// read this while separated
					text_content = $(popover).text();
					// now put it back where it came from
					$(rows[i]).children().last().append( popover ).append( slide_link );
					
				//	console.log( 'no img_id - text slide - ' + text_title ); 
				}
				
				if( type == 'text' && text_title == '' ) {
				//	console.log( 'not a text slide afterall - ' + text_title );
					// skip the rest of the loop
					continue;
				}
				
				// if this slide has already been saved it has a slide_id index
				slide_id = $(rows[i]).attr('data-slide-id');
							
				// possible for each type
				slide_link = $(rows[i]).children().last().children('a').text();
							
				if( type == 'image' ) {
					// this is all we need for an image slide, 
					// along with possible slide_id and slide_link values
					post_id = img_id;
				}	
				
				if( (type === 'image' && post_id > 0) || (type == 'text')) { 
	
					var slide = {
						type: type,
						slide_id: slide_id,
						text_title: text_title,
						text_content: text_content,
						slide_link: slide_link,
						post_id: post_id,
						ordering: i
					}
					slides.push( slide );
				}
				//	console.log( 'slides.length: ' + slides.length );			
			}
				
			
			var is_active = $('#slideshow-is-active-collection').val();
				if( undefined === is_active ) {
					is_active = String() + '0';
				}
		
			var layout = $('input[name="slideshow-layout"]').filter(':checked').val();
				if( undefined === layout ) {
					layout = 'no-thumb';
				}
				
			var transition = $('input[name="slideshow-transition"]').filter(':checked').val();
				if( undefined === transition ) {
					transition = window.coop_slideshow_settings.current.mode;
				}
			
			
			var data = {
				action: 'slideshow-save-slide-collection',
				title:	$('.slideshow-collection-name').val(),
				slideshow_id: $('#slideshow_select').val(),
				layout: $('input[name="slideshow-layout"]').filter(':checked').val(),
				transition: $('input[name="slideshow-transition"]').filter(':checked').val(),
				layout: layout,
				transition: transition,
				is_active: is_active,
				slides: slides
			};
			
			$.post(ajaxurl,data).complete(function(r){
				var res = JSON.parse(r.responseText);
				
				/// do something in response to the save attempt feedback ...
				if( res.result === 'success' ) {
					alert( 'Slide collection saved' );	
					self.fetch_selected_slideshow();				
				}
				else {
					alert( res.feedback );
				}		
			});
		},
		
		
		/**
		*	image-click handler to set the layout style
		*	radio buttons when a graphic is clicked.
		**/
		set_layout_control: function() {
			var t = $(this);
			var id = t.data('id');
			$('#'+id).click();
		},
		
		show_checkmark: function() {
			alert( 'show checkmark in the right hand edge of the Collection name field' );
		},
		
		/**
		*	slideshow-collection-name input sprite...
		**/
		spritebox: function() {
			
			var box = $('<div class="slideshow-signals"></div>');
			var left = parseInt($('.slideshow-collection-name').css('left'),10);
				left = left + parseInt($('.slideshow-collection-name').css('width'),10) - 32;
				box.css('left',left);
			
			var top = parseInt($('.slideshow-collection-name').css('top'),10) - 32;
				box.css('top',top);
				box.css('position','relative');
				
			$('.slideshow-collection-name').parent().append(box);
			
		},
		
		slide_hover_in: function() {
		
			var box = $(this);	
			var sigbox = $('.signalbox',box);
				sigbox.append(self.signalbox);
		},
		
		slide_hover_out: function() {
			var box = $(this);
			var sigbox = $('.signalbox',box);
			self.signalbox = $('img.signals-sprite',sigbox).parent().detach();
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
	jQuery("#slideshow_select").chosen().change( slideshow_setup.fetch_selected_slideshow );

	jQuery('#coop-slides-setup-submit').click(function(event){
		event.stopPropagation();
		slideshow_setup.save_collection();
	});
	
//	jQuery('.slideshow-runtime-information').append(jQuery('<button class="temp-test droppable">Test data</button>'));
	
});