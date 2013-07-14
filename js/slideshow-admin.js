/**
 * @package Slideshow Setup 
 * @copyright BC Libraries Coop 2013
 *
 **/

;(function($,window) {

	var self,
		_configured = {},	// passed in options
		editing_node,
		opts = {},			// opts == current at start up (diverges as user changes settings)
		signalbox,				// for showing signals to the user in a floating div .sort-table-signal
		slideshow_id;
	
	var SlideShowSetup = function( options ) {
		this.init( options );
	}
	
	SlideShowSetup.prototype  =  {
	
	
		init: function( options ) {
			
			self = this;
			
			// init hook-ups listed more-or-less in page order
			
			$('.slideshow-collection-name').change( self.collection_name_monitor_changes );
			
			$('#collection-name-signal img').hover( self.alt_hover_in, self.alt_hover_out )
				.attr('alt','enter a new name');
			
			$('#slideshow_select').chosen().change( self.fetch_selected_slideshow );
			$('#slideshow_select').chosen().change( self.reset_collection_name_signal );		
			$('#slideshow_page_selector').chosen();
			
						 
			$('.slideshow-save-collection-btn').click( function(evt) {
				evt.stopImmediatePropagation();
				self.save_collection();
				return false;
			});
			
			$('.slideshow-delete-collection-btn').click( function(evt) {
				evt.stopImmediatePropagation();
				self.delete_this_collection();
				return false;
			});

			
/*
			$('.slideshow-text-slide-link-btn').click( function(evt){
				evt.stopImmediatePropagation();
				self.toggle_text_link_input();
				return false; 
			});
*/
				
			$('.slideshow-text-slide-cancel-btn').click( function(evt){
				evt.stopImmediatePropagation();
				self.clear_text_slide_form();
				return false;
			});
			
			$('.slideshow-text-slide-save-btn').click( function(evt) {
				evt.stopImmediatePropagation();
				self.add_text_only_slide();
				return false;
			});
			
										
/*
			$('#coop-slides-setup-submit').click(function(evt){
				evt.stopImmediatePropagation();
				self.save_collection();
				return false;
			});
*/	
			
			$('#runtime-signal img.signals-sprite').addClass('reload-disabled').hover( self.runtime_hover_in, self.runtime_hover_out ).click( self.runtime_calculate );
			
			$('#runtime-signal img').attr('alt','recalculate runtime');
						
			self.init_quick_set_layout();
			
		},
				
		init_quick_set_layout: function() {
		
			// bind clicking on graphics to radio buttons
			$('.slideshow-control-img').click( self.set_layout_control );
		
			// fetch and set current/default values 
			var layout = window.coop_slideshow_settings.current.currentLayout;
			var transition = window.coop_slideshow_settings.current.mode;
			$('input[value="'+layout+'"][name="slideshow-layout"]').attr('checked','checked');
			$('input[value="'+transition+'"][name="slideshow-transition"]').attr('checked','checked');			
		},
		
		alt_hover_in: function(obj) {
		
			var type = 'img';
			if( obj.type == 'mouseenter' ) {
				type='event';
				obj = obj.currentTarget;
			}
			var img = $(obj);	
			var tip = $('.alt-hover');
			var txt = img.attr('alt');
			
			if( txt == undefined || txt == '') {
				return;
			}
			tip.empty().append( txt );
			
			var twidth = parseInt(tip.width(),10) / 2;
			var theight = tip.outerHeight();
			
			var box = img.parent();
			var pos = box.offset();	// absolute left, top 
			var adminmenu = $('#adminmenuwrap').width();	
				
			var leftOffset = pos.left - 16 - adminmenu - twidth;
			var topOffset = pos.top - 32 - theight;
				 
				tip.css( 'top', topOffset ).css( 'margin-left',leftOffset).css('position','absolute' );

				tip.show();		
		},
		
		alt_hover_out: function(evt) {
			$('.alt-hover').hide();
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
			
			var page_id = $('#slideshow_page_selector option').filter(':selected').val();
			var slide_link = '/?page=' + page_id;
			
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
					self.fetch_selected_slideshow();
				}
				else {
					alert( 'Unable to save the text slide.' );
					$('#slideshow-text-slide-heading').focus();
				}
			});
		},
				
		clear_drop_table_rows: function() {
		
			var rows = $('.slideshow-collection-row');
			for( i=0;i<rows.length;i++) {	
			//	console.log( 'clearing row ' + i );
				$('.thumbbox',rows[i]).empty();
				$(rows[i]).data('slide-id','');
				$(rows[i]).children().last().text('');
				$(rows[i]).children().last().children('span').empty();
			}
		},
		
		/**
		*	Re-use rows after deleting an entry
		**/
		clear_and_reinsert_row: function( dragged ){
			$('.thumbbox',dragged).empty();
			$(dragged).data('slide-id','');
			$('div',dragged).empty();

			$('.slideshow-sortable-rows').append(dragged);
		},
		
		clear_text_slide_form: function() {
			$('#slideshow-text-slide-heading').empty().val('');
			$('#slideshow-text-slide-content').empty().val('');
			$('.slideshow-text-slide-link-input').empty().val('');
		},
			
		collection_name_monitor_changes: function(evt) {
		
			var input = this;
			var spritebox = $('#collection-name-signal');
			var sprite = $('img',spritebox);
			
			
			if( $(input).val().trim() == '' ) {
				// whitespace only in field 
				sprite.attr('class','signals-sprite cross-disabled');
				$('#collection-name-status-msg').empty()
					.append( 'Cannot use empty spaces for collection name' );
				
			}
			else if( $(input).val().trim().length == 0 ) {
				sprite.attr('class','signals-sprite plus-disabled');
				$('#collection-name-status-msg').empty();
			}
			else {
				sprite.attr('class','signals-sprite tick-disabled');
				$('#collection-name-status-msg').empty().append( 'Checking if name already in use' );
				self.precheck_slideshow_name();
			}
			
		},			
		
		delete_this_collection: function() {
		
			if( confirm("This is a destructive operation.\nAre you sure you want to\nremove this slideshow from the database?" )) {
			
				var is_active = $('#slideshow-is-active-collection').is(':checked');
			
				var data = {
					action: 'slideshow-delete-slide-collection',
					slideshow_id: $('#slideshow_select').val()
				};
				
				$.post( ajaxurl, data ).complete(function(r) {
				
					var res = JSON.parse(r.responseText);
					if( res.result == 'success' ) {
						alert( res.feedback );
						window.history.go(0);
					}
				});
			}
			else {
				alert( 'Operation cancelled');
			}
			return false;
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
			
			self.runtime_calculate();
		
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
			
			var titlediv = $('div',textbox).first();
			var linkdiv = $('div',textbox).last();
			var anchor = linkdiv.children('a').first();
				anchor.attr('href', link );
				titlediv.empty().text( cap );
				linkdiv.empty().append( anchor );

			$(thumb).addClass('ghosted').parent().draggable('option','disabled',true);

			self.fetch_img_meta( id );

			self.runtime_calculate();

		},
		
		
		return_to_source: function( row, ui ) {
		
			console.log( 'return to source' );
		
			var $t = $(this); // this
			
			var dragged = ui.draggable;
		
			if ( ! dragged.hasClass('slideshow-collection-row')) {
				return;
			}
			
			var dropzone_id = $($t).attr('id');
					
			if( dropzone_id == 'slide-remove-local'  ) {
				self.slide_remove_local( dragged );
				return;
			}
			else if( dropzone_id == 'slide-remove-shared' ) {
				self.slide_remove_shared( dragged );
				return;
			}
		},
		
		over_source: function( evt, ui ) {
			
		},
		
		leave_source: function( evt, ui ) {
			
		},
	
			
		over_drop: function( evt, ui ) {
		//	console.log( 'over drop zone' );
			
		},
		
		leave_drop: function( evt, ui ) {
			
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
			});
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
				self.slideshow_id = opt.val();
				if( res.is_active === 1 ) {
					$('#slideshow-is-active-collection').attr('checked','checked');
				}
				else {
					$('#slideshow-is-active-collection').removeAttr('checked');
				}
				
				var i;
				for( i=0; i<slides.length; i++ ) {
				
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
			});
		},
		
		
		insert_inline_edit: function() {
		
			var imgsrc = $('.slideshow-signals-preload img').attr('src');
			var div = $('<div class="slideshow-inline-edit-toggle"/>')
							.css('background-image','url('+imgsrc+')')
							.css('background-position', '-266px -6px'); 				
			return div;
		},
		
		
		inline_edit_toggle: function() {
		
			var target = $(this);
			
			console.log( target );
					
			var txt = target.text();
			var top = target.css('top');
			var left = target.css('left');
			var width = target.width();
			var height = target.outerHeight();
			
			if( target.attr('id') == 'inline-edit' ){
				var div = self.editing_node;
				div.text( target.val() );
				div.append( self.insert_inline_edit() );
				div.hover(self.inline_edit_hover_in, self.inline_edit_hover_out );
				div.click(self.inline_edit_toggle);
				
				target.replaceWith( div );
			}
			else {		
				var input = $('<input type="text" id="inline-edit" value="'+txt+'"/>');
					input.css('top',top).css('width',width).css('left',left);
					self.editing_node = target.replaceWith( input );
					input.bind('focusout', self.inline_edit_toggle ).focus();
					
			}
		},
		
		
		
		inline_edit_hover_in: function(evt) {
			$('.slideshow-inline-edit-toggle',evt.target).css('background-position','-266px -70px');
		},
		
		inline_edit_hover_out: function(evt) {
			$('.slideshow-inline-edit-toggle',evt.target).css('background-position','-266px -6px');
		},
		
		place_slide_img: function( id, post_id, link, row ) {
			
		//	console.log( 'called place_slide_img ' + id + ': ' + post_id );
			
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
				
				$(row).data('slide-id', id );
				
				var img = $('<img data-img-id="' + post_id + '" src="'+src+'" width="' + w + '" height="' + h + '">');
				$(row).children().first().empty().append( img );
						
				var title = $('<div class="slide-title" />').append(meta['title']).append( self.insert_inline_edit() );
					title.hover(self.inline_edit_hover_in, self.inline_edit_hover_out );
					title.click(self.inline_edit_toggle);
					$(row).children().eq(1).empty().append( title );
				
				if( undefined !== link ) {	
					var anchor = $('<a class="slide-anchor" target="_blank"/>').text( link ).attr('href',link);
					var div = $('<div class="slide-link" />').append( anchor ).append( self.insert_inline_edit());
						div.hover(self.inline_edit_hover_in, self.inline_edit_hover_out );
						div.click(self.inline_edit_toggle);
					$(row).children().eq(1).append( div );

				}
				
				self.runtime_calculate();
			});
		},
		
		place_slide_text: function( id, title, content, link, row ) {
		
		//	console.log( 'called place_slide_text - ' + id + ' - ' + content );
		
			if( row == null ) {
				// get the first empty row ...
				row = self.first_empty_row();
			}
			
			$(row).data('slide-id',id);
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

			self.runtime_calculate();
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
					$('#collection-name-signal img').addClass('cross-active');
										
					if( res.slideshow_id > 0 ) {
						$('#slideshow_select').attr('selectedIndex',res.slideshow_id);
						$('#slideshow_select').trigger('liszt:updated');
						$('#collection-name-signal img').attr('class','signals-sprite tick-active')
							.attr('alt','reload this collection').unbind('click').bind('click',self.fetch_collection_by_name );
						$('#collection-name-status-msg').empty().append( 'Found slideshow with that name. Click green tick to reload slideshow.' );

					}
				}
				else {
					// okay to use if newly created
					$('#collection-name-signal img').attr('class','signals-sprite plus-active')
						.attr('alt','save collection name')
						.unbind('click').bind('click',self.save_collection_name);
					$('#collection-name-status-msg').empty().append( 'Click the green plus to save slideshow name.' );

				}
				
			});
				
		},
		
		runtime_calculate: function() {
		
			$('.slideshow-runtime-information').empty();
		
			var children = $('.thumbbox').children();	
/*
			for( i=0; i<children.length; i++ ) {
				console.log( i +': ' + $(children[i]).parent().parent().attr('id'));
			}
			
*/	
			var msg;
			if( undefined === children ){
				msg = "There must be slides before calculating the runtime.";
			}
			else {
			///	var index = parseInt(row.replace('row',''),10) + 1; // offset zero-based index 
				var index = children.length;
				var dwell = parseInt(window.coop_slideshow_settings.current.pause,10) / 1000;
				var transit = parseInt(window.coop_slideshow_settings.current.speed,10) / 1000;
				
				var net = index * (dwell + transit);	// slideshow cycle in seconds
				
				msg = "There are "+index+" slides in this slideshow. Each slide will show for "+dwell+" seconds. ";
				msg += "Transition between slides will take "+transit+" seconds. ";
				msg += "The slideshow will take a total of "+net+" seconds to cycle completely.";
			}
			$('.slideshow-runtime-information').empty().text( msg );
		},
		
		reset_collection_name_signal: function(){
			$('#collection-name-signal img').removeClass('cross-active').addClass('tick-disabled').attr('alt','name has been saved');
		},
		
		runtime_hover_in: function(evt) {
			$(this).removeClass('reload-disabled').addClass('reload-active');
			self.alt_hover_in(evt);
		},
		
		runtime_hover_out: function(evt) {
			$(this).removeClass('reload-active').addClass('reload-disabled');
			self.alt_hover_out(evt);
		},
			
		save_collection_name: function() {
		
			var is_active = $('#slideshow-is-active-collection').is(':checked');
			//	console.log( 'is_active: ' + is_active );
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
				
				// read the title from it's box
				text_title = $(rows[i]).children().last().children('div').first().text();	// now in fact, .first()
				
				// link? - read the link URL from the anchor
				slide_link = $(rows[i]).children().last().children('div').eq(2).children('a').attr('href');  // slide link box

				
				if( img_id == undefined ) {

					console.log( 'no img_id - text slide - ' + text_title );		
					type = 'text';		

					// read the content of the content div 
					text_content = $(rows[i]).children().last().children('div').last().text();
				}
				
				if( type == 'text' && text_title == '' ) {
					console.log( 'not a text slide (empty title) - ' );
					// skip the rest of the loop
					continue;
				}
				
				// if this slide has already been saved it has a slide_id index
				slide_id = $(rows[i]).data('slide-id');
														
				if( type == 'image' ) {
					// this is all we need for an image slide, 
					// along with possible slide_id and slide_link values
					post_id = img_id;
				}	
				
				console.log( type + ': ' + text_title + ': ' + text_content + ': ' + post_id + ': ' + slide_id + ': ' + slide_link );
				
				
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
		*	image-click handler: sets the layout 
		*	radio buttons when it's graphic is clicked
		**/
		set_layout_control: function() {
			var t = $(this);
			var id = t.data('id');
			$('#'+id).click();
		},
		
		show_checkmark: function() {
			$('#collection-name-signal img').addClass('tick-active').fadeOut(2000);
		},	
		
		slide_remove_local: function( dragged ) {
			
			console.log( 'slide_remove_local()' );
		
			var img_id = $('img',dragged).data('img-id');
			$('#thumb'+img_id).removeClass('ghosted').parent().draggable('option','disabled',false);
			self.clear_and_reinsert_row(dragged);
		},
		
		slide_remove_shared: function( dragged ) {
			var img_id = $('img',dragged).data('img-id');
			$('#thumb'+img_id).removeClass('ghosted').parent().draggable('option','disabled',false);
			self.clear_and_reinsert_row(dragged);
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
							
			$('#coop-slideshow-settings-submit').click( this.save_changes );	
			
		//	console.log('returning initialized coop_slideshow_settings object');
			
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
	
	jQuery('.returnable').droppable({ drop: slideshow_setup.return_to_source,
									  over: slideshow_setup.over_source,
									  out:	slideshow_setup.leave_source,
									  hoverClass: 'return_highlight' 
				
								});							
	
	
//	jQuery('.slideshow-runtime-information').append(jQuery('<button class="temp-test droppable">Test data</button>'));
	
});