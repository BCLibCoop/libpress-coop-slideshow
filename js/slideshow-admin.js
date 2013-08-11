/**
 * @package Slideshow Setup 
 * @copyright BC Libraries Coop 2013
 *	version: 0.3.2
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
			self.editing_node = null;
			
			// init hook-ups listed more-or-less in page order
			$('A.add-new').click( function(evt){
				evt.stopImmediatePropagation();
				self.init_add_new_state();
				return false;
			});
			
			$('.slideshow-collection-name').change( self.collection_name_monitor_changes );
			
			$('#collection-name-signal img').hover( self.alt_hover_in, self.alt_hover_out )
				.attr('alt','enter a new name');
			
			$('#slideshow_select').chosen({disable_search_threshold: 10}).change( self.fetch_selected_slideshow );
		//	$('#slideshow_select').chosen().change( self.reset_collection_name_signal );		
			$('#slideshow_page_selector').chosen({disable_search_threshold: 10});
			
						 
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
			
			// retrieve the currently active slideshow by default
			self.fetch_selected_slideshow();
			
		},
				
		init_add_new_state: function() {
		
			$('#slideshow_select').remove();
			$('#slideshow_select_chzn').remove();
			self.clear_drop_table_rows();
			// clear activated collection flag
			var activated = $('#slideshow-is-active-collection').filter(':checked');
			if( activated.length ) {
				$('#slideshow-is-active-collection').click();
			}
			$('#slideshow-is-active-collection').removeAttr('checked');
			// clear the input field, show and set focus
			$('.slideshow-collection-name').show().val('').focus();
			
		},
				
		init_quick_set_layout: function() {
		
			// bind clicking on graphics to radio buttons
			$('.slideshow-control-img').click( self.set_layout_control );
		
		
			// fetch and set current/default values - WRONG
			// we need to reset values this way only on creating a blank collection name form
			//
			// routinely the fetch_collection routine should reset the current slideshow's settings
			
			/*
			var layout = window.coop_slideshow_settings.current.currentLayout;
			var transition = window.coop_slideshow_settings.current.mode;
			$('input[value="'+layout+'"][name="slideshow-layout"]').attr('checked','checked');
			$('input[value="'+transition+'"][name="slideshow-transition"]').attr('checked','checked');
			*/			
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
			
			var sel_opt = $('#slideshow_page_selector option').filter(':selected')
			var page_id = sel_opt.val();
			var opt_class = $(sel_opt).hasClass('page')?'page':'post';
			var guid = $(sel_opt).data('guid');
			var slide_link;
			
			if( opt_class === 'page' ) {
				slide_link = '/?page=' + page_id;
			}
			else {
				slide_link = guid;
			}
			
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
			var caption = $('<div class="slide-title"><span class="placeholder">Caption/Title</span></div>');
			var link = $('<div class="slide-link"><span class="placeholder">Link URL</span></div>');
			for( i=0;i<rows.length;i++) {	
			
				$('.thumbbox',rows[i]).empty();
				$(rows[i]).data('slide-id','');
				$(rows[i]).children().last().empty().append($(caption).clone()).append($(link).clone());
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
				if( txt.length == 0 ) {
					return rows[i];
				}
			}
		},
				
		fetch_selected_slideshow: function() {
		
			self.clear_drop_table_rows();
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
								
				if( res.is_active === "1" ) {
					$('#slideshow-is-active-collection').attr('checked','checked');
				}
				else {
					$('#slideshow-is-active-collection').removeAttr('checked');
				}
				
				if( res.captions === "1" ) {
					$('#slideshow-show-captions').attr('checked','checked');
				}
				else {
					$('#slideshow-show-captions').removeAttr('checked');
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
						self.place_slide_img( slides[i].id, slides[i].post_id, slides[i].text_title, slides[i].slide_link, row );
					}
				}
				
				/* the layout and transition settings also need restoring */
							
				var layout = res.layout;
				var transition = res.transition;
				
				$('input[value="'+layout+'"][name="slideshow-layout"]').attr('checked','checked');
				$('input[value="'+transition+'"][name="slideshow-transition"]').attr('checked','checked');
				
				
			});
		},
		
		insert_inline_edit_toggle: function( opt ) {
			
			var imgsrc = $('.slideshow-signals-preload img').attr('src');
			var div = $('<div class="slideshow-inline-edit-toggle" />')
							.css('background-image','url('+imgsrc+')');
			if ( opt ) {				
				div.css('background-position', '-266px -70px');
			}
			else {
				div.css('background-position', '-266px -6px');
			}
			return div;
		},
				
		inline_edit_hover_in: function(evt) {
			$('.slideshow-inline-edit-toggle',evt.target).css('background-position','-266px -70px');
		},
		
		inline_edit_hover_out: function(evt) {
			$('.slideshow-inline-edit-toggle',evt.target).css('background-position','-266px -6px');
		},
		
		inline_form_toggle: function() {
		
			// this is only the title 
			// top level element === TD.slideshow-slide-title 
			var target;
			var content_edit;
			
			if( this.id === 'inline-editor') {
				target = $(this);
			}
			else if( $(this).parent().hasClass('slide-title-box')) {
				target = $(this).parent().parent();
			}
			else {
				target = $(this).parent();
			}
			
			console.log( target );
									
			// guard - only one active inline-editor at one time
			if( self.editing_node !== null && target.attr('id') == undefined ) {
				// restore the graphic for all targets to neutral
				$('.slideshow-inline-edit-toggle').css('background-position','-266px -6px');
				return;
			}
			
			var top = target.css('top');
			var left = target.css('left');
			var width = target.width();
			var height = target.outerHeight();
			
			if( target.attr('id') == 'inline-editor' ){		// state marker == active editor
			
				// restore NON-EDIT view
				var td = self.editing_node;
				
				var title_edit = $('#slide-title-edit');
				var link_edit 	= $('#slide-link-edit');
					content_edit = $('#slide-content-edit');
				
				if( title_edit.val().trim() === '' ) {
					alert( 'Slide title cannot be empty' );
					title_edit.focus();
					return;
				}
				
				var title_div = $('<div class="slide-title" />').append(title_edit.val());
				
				var a = $('<a/>').attr('href',link_edit.val()).attr('target','_blank').text(link_edit.val());
				var link_div = $('<div class="slide-link" />').append(a);
				
				td.empty().append(title_div).append(link_div);
				
				if( content_edit !== undefined ) {
					var txt = content_edit.text().trim();
					if( txt !== null && txt !== undefined && txt !== '' ) {
						var content_div = $('<div class="slide-content" />').append(content_edit.val().trim());
						td.append(content_div);
					}
				}
				
				// restore click-to-edit functionality
				title_div.hover( self.inline_edit_hover_in, self.inline_edit_hover_out );
				title_div.append(self.insert_inline_edit_toggle());
				title_div.click( self.inline_form_toggle );
				
				target.replaceWith( td );
				// clear buffer for reuse (also, a null buffer is part of active-inline-editor detection)
				self.editing_node = null;
			}
			else {
				// convert to INLINE-EDITOR
			
				var inline_editor = $('<td class="inline-editor" id="inline-editor" />');
				var title_edit = $('<input class="slide-title-edit" type="text" id="slide-title-edit" value="'+$('.slide-title',target).text()+'" placeholder="Caption/Title (required)" />');
				var link_edit = $('<input class="slide-link-edit" type="text" id="slide-link-edit" value="'+$('.slide-link',target).text()+'" placeholder="/?page_id=123" />');
				var div_title = $('<div class="slide-title-box"/>').append(title_edit).append(self.insert_inline_edit_toggle(1));
			
				inline_editor.append(div_title).append(link_edit);
				title_edit.css('width','94%');
				link_edit.css('width','100%');
											
				var text  = $(target).children('.slide-content').first().text();
				if( text !== null && text !== undefined && text.trim() !== '' ) {
					content_edit = $('<textarea class="slide-content-edit" id="slide-content-edit" placeholder="text message">'+text+'</textarea>');
					inline_editor.append(content_edit);
					content_edit.css('width','100%');
				}
				else {
					console.log( 'No content for this slide content-text' );
				}
				inline_editor.css('top',top).css('width',width).css('left',left);
				self.editing_node = target.replaceWith( inline_editor );
				$('.slideshow-inline-edit-toggle').click( self.inline_form_toggle );
				title_edit.focus();
				
			}
		},
		
				
		place_slide_img: function( id, post_id, slide_title, link, row ) {
						
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
				
				var this_title = meta['title'];
				if( slide_title != '' && title != meta['title'] ) {
					this_title = slide_title;
				}
				
				var img = $('<img data-img-id="' + post_id + '" src="'+src+'" width="' + w + '" height="' + h + '">');
				$(row).children().first().empty().append( img );
				
				var title = $('<div class="slide-title" />').append(this_title).append( self.insert_inline_edit_toggle() );
					title.hover(self.inline_edit_hover_in, self.inline_edit_hover_out );
					title.click(self.inline_form_toggle);
					$(row).children().eq(1).empty().append( title );
				
				if( undefined !== link ) {	
					var anchor = $('<a class="slide-anchor" target="_blank"/>').text( link ).attr('href',link);
					var div = $('<div class="slide-link" />').append( anchor );
					$(row).children().eq(1).append( div );
				}
				
				self.runtime_calculate();
			});
		},
		
		place_slide_text: function( id, title, content, link, row ) {
		
			if( row == null ) {
				// get the first empty row ...
				row = self.first_empty_row();
			}
			
			$(row).data('slide-id',id);
			$(row).children().first().empty().append($('<span class="slideshow-big-t">T</span>'));
			
			var titlediv = $('<div class="slide-title" />').append(title).append( self.insert_inline_edit_toggle());
				titlediv.hover(self.inline_edit_hover_in, self.inline_edit_hover_out );
				titlediv.click(self.inline_form_toggle);
			$(row).children().eq(1).empty().append(titlediv); 
			
			if( undefined !== link ) {
				var anchor = $('<a class="slide-anchor" target="_blank"/>').text( link ).attr('href',link);
				var div = $('<div class="slide-link" />').append( anchor );
				$(row).children().eq(1).append( div );
			}
			$(row).children().eq(1).append( $('<div class="slide-content" />').append( content ));

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
	
	
		save_collection: function() {
		
			var slides = [];
			var rows = $('.slideshow-collection-row');
			
		
			for( i=0;i<rows.length;i++) {
			
				// remove the placeholder spans which are purely eyecandy
				$('span.placeholder',rows[i]).remove();
			
				// slide_id is set in the case of a collection having been reloaded into the editor 
				
				var type = 'none';		// bias inherent in the system :-)
				var text_title = '';
				var text_content = '';
				var post_id = '';
				var slide_id = ''; 
				var slide_link = '';
				var use_captions = '0';
			
				var img = $(rows[i]).children().first().children('img');
				var img_id = $(img).data('img-id');
				
				// read the title from it's box
				text_title = $(rows[i]).children().last().children('div.slide-title').first().text();
								
				// link? - read the link URL from the anchor
				slide_link = $(rows[i]).children().last().children('div.slide-link').children('a').attr('href');  // slide link box
				
				if( img_id == undefined ) {
							
					// read the content of the content div
					text_content = $(rows[i]).children().last().children('div').last().text();
					if( text_title.length > 0 && text_content.length > 0 ) {
						type = 'text';	
					}
				}
				else {
					type = 'image';
					post_id = img_id;
				}
				
				
				if( type == 'text' && text_title == '' ) {
					
				}

				// if this slide has already been saved it has a slide_id index
				slide_id = $(rows[i]).data('slide-id');
														
				if( (type === 'image' && post_id > 0) || (type == 'text' && text_title.length > 0)) 
				{ 
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
			}
				
			var is_active = $('#slideshow-is-active-collection').filter(':checked').val();
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
			
			if( $('#slideshow-show-captions').is(':checked')) {
				use_captions = '1';
			}
			
			var slideshow_id = $('#slideshow_select').val();
			
			var data = {
				action: 'slideshow-save-slide-collection',
				title:	$('.slideshow-collection-name').val(),
				slideshow_id: slideshow_id,
				layout: layout,
				transition: transition,
				is_active: is_active,
				captions: use_captions,
				slides: slides
			};
			
			$.post(ajaxurl,data).complete(function(r){
				var res = JSON.parse(r.responseText);
				
				/// do something in response to the save attempt feedback ...
				if( res.result === 'success' ) {
					alert( 'Slide collection saved' );	
				//	self.fetch_selected_slideshow();
					window.history.go(0);			
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
	
	$.fn.coop_slideshow_manager = function(opts) {
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
		_debug, 
		_configured = {},	// passed in options
		current = {},		// _defaults + _configured
		_defaults = {},		// bxSlider factory settings
		_touched,			// record keys of fields altered until a save
		opts = {};			// opts == current at start up (diverges as user changes settings)
	
	var SlideShowSettings = function( options ) {
		
		self = this; // reference back
		this.init( options );
		self._debug = false;
	}
	
	SlideShowSettings.prototype  =  {
	
		init: function( options ) {
					
			// load the definitional default set by bxSlider
			self._defaults = $.extend( {}, self._defaults, window.coop_bx_defaults );
			// split out default from tuples (first in list)
			self.clean_up_defaults();

			// capture and save the configuration we were started up with (options as passed in)
			self._configured = $.extend( {}, options );

			// now load our current values as set by Slideshow settings controls
			self.opts = $.extend( {}, self._defaults, options );
			
			// duplicate starting config as current config - this gets changes by user
			self.current = $.extend( {}, this._defaults, options );
			
			self._touched = [];
			
			// bind the html form fields to this.current fields
			var p;
			for( p in self.current ) {
				if( typeof p !== 'function' ) {
					$( 'input[name="'+p+'"]' ).on('change', self.set_current_value );	
				}
			}
							
			$('#coop-slideshow-settings-submit').click( self.save_changes );	
			
			if( self._debug ) console.log('returning initialized coop_slideshow_settings object');
			
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
			if( self._debug ) console.log( 'save button has been clicked ' );
			
			// determine which settings are now different ( 
			var p;
			var changed = {}; 
			var keys = [];
			for( p in self.opts ) {
				if( typeof p !== 'function' ) {
					if( self.opts[p] !== self.current[p]) {
						keys.push(p);
						changed[p] = self.current[p];			
					if( self._debug ) 	console.log( 'changed: ' + p );
					}
					else {
						var i;
						for( i in self._touched ) {
							if( typeof i !== 'function' ){		
							if( self._debug ) 	console.log( '_touched[i]: ' + self._touched[i] + ' <=> ' + p );
								if( self._touched[i] == p ) {
								if( self._debug ) 	console.log( '_touched[i]: ' + self._touched[i] + ' == ' + p );
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
				if( self._debug ) 	console.log( 'nothing has changed' );
				if( self._debug ) 	alert( 'nothing to do! ' );
				return false;
			}
			// otherwise continue to build data object to send server-side
			
			changed['action'] = 'coop-save-slideshow-change';
			// because the exact changes are arbitrary, pass the array of keys as well 
			changed['keys']   = JSON.stringify(keys); 
			
			if( self._debug ) 	console.log( 'posting data' );
			
			$.post( ajaxurl, changed ).complete(function(r) {
			
				if( self._debug ) console.log( 'response returned ' );
				var res = JSON.parse(r.responseText);
				
				alert( res.feedback );
				
				self._touched = [];
				
			});
		},
		
		touched: function( id ) {
			this._touched.push( id );
			if( self._debug ) console.log( this._touched );
		},
		
		set_current_value: function() {
	
			// update self.current to reflect the user's changes
			var id = this.getAttribute('name');
			var val = this.value;
			if( val == '' ) {
				val = 'empty';
			}
		
			if( self._debug ) console.log( id + ': ' + val );
		
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
	window.slideshow_manager = jQuery().coop_slideshow_manager();
	
	jQuery('.draggable').draggable({ cursor:'move', 
									 stack:	'.slide', 
									 start:  slideshow_manager.dragstart, 
									 stop:   slideshow_manager.dragstop,
									 helper: slideshow_manager.drag_representation
								});
									 
	jQuery('.droppable').droppable({ drop:  slideshow_manager.drop_on_row,
									 over:  slideshow_manager.over_drop,
									 out:   slideshow_manager.leave_drop,
									 hoverClass: 'drop_highlight' 
								});
	
	jQuery('.returnable').droppable({ drop: slideshow_manager.return_to_source,
									  over: slideshow_manager.over_source,
									  out:	slideshow_manager.leave_source,
									  hoverClass: 'return_highlight' 
								});							
	
	
});