/**
 * @package Slideshow Setup
 * @copyright BC Libraries Coop 2013
 * version: 0.3.2
 **/

; (function ($, window) {

  var self;

  var SlideShowSetup = function () {
    this._configured = {}; // passed in options
    this.editing_node = null;
    this.opts = {}; // opts == current at start up (diverges as user changes settings)
    this.slideshow_id = 0;

    this.init();
  }

  SlideShowSetup.prototype = {

    init: function () {
      self = this;

      // init hook-ups listed more-or-less in page order
      $('button.add-new').on('click', this.init_add_new_state);

      $('#slideshow_select')
        .chosen({disable_search_threshold: 10})
        .on('change', this.fetch_selected_slideshow);

      $('#slideshow_page_selector').chosen({disable_search_threshold: 10});

      $('.slideshow-save-collection-btn').on('click', this.save_collection);
      $('.slideshow-delete-collection-btn').on('click', this.delete_this_collection);
      $('.slideshow-text-slide-cancel-btn').on('click', this.clear_text_slide_form);
      $('.slideshow-text-slide-save-btn').on('click', this.add_text_only_slide);

      $('#runtime-signal img.signals-sprite').on('click', this.runtime_calculate);

      // retrieve the currently active slideshow by default
      this.fetch_selected_slideshow();
    },

    init_add_new_state: function () {
      $('#slideshow_select').remove();
      $('#slideshow_select_chzn').remove();
      self.clear_drop_table_rows();

      // clear activated collection flag
      $('#slideshow-is-active-collection').prop('checked', false);

      // clear the input field, show and set focus
      $('.slideshow-collection-name').show().val('').focus();
    },

    add_text_only_slide: function () {
      var slideshow_collection_name = $('.slideshow-collection-name').val();
      var slideshow_id = $('#slideshow_select').val();

      if (slideshow_collection_name == '' && slideshow_id == null) {
        alert('Whoops! You need to name the slideshow first.');
        $('.slideshow-collection-name').trigger('focus');

        return false;
      }

      var title = $('#slideshow-text-slide-heading').val();
      var content = $('#slideshow-text-slide-content').val();

      var is_active = $('#slideshow-is-active-collection').val();
      if (is_active === undefined) {
        is_active = String() + '0';
      }

      if (title == '' || content == '') {
        alert('You must enter a title and a message');

        return false;
      }

      var slide_link = $('#slideshow_page_selector option:selected').val();

      var data = {
        action: 'slideshow-add-text-slide',
        slideshow_name: slideshow_collection_name,
        slideshow_id: slideshow_id,
        title: title,
        content: content,
        slide_link: slide_link,
        is_active: is_active
      };

      $.post(ajaxurl, data).done(function (res) {
        if (res.result === 'success') {
          alert('Text slide saved');
          self.clear_text_slide_form();
          self.fetch_selected_slideshow();
          // window.history.go(0);
        } else {
          if (res.feedback !== undefined) {
            alert(res.feedback);
          } else {
            alert('Unable to save the text slide.');
          }

          $('#slideshow-text-slide-heading').focus();
        }
      });
    },

    clear_text_slide_form: function () {
      $('#slideshow-text-slide-heading').empty().val('');
      $('#slideshow-text-slide-content').empty().val('');
      $('.slideshow-text-slide-link-input').empty().val('');
    },

    clear_drop_table_rows: function () {
      var rows = $('.slideshow-collection-row');
      var caption = $('<div class="slide-title"><span class="placeholder">Caption/Title</span></div>');
      var link = $('<div class="slide-link"><span class="placeholder">Link URL</span></div>');

      for (var i = 0; i < rows.length; i++) {
        $('.thumbbox', rows[i]).empty();
        $(rows[i]).data('slide-id', '');
        $(rows[i]).children().last().empty().append($(caption).clone()).append($(link).clone());
      }
    },

    /**
    * Re-use rows after deleting an entry
    **/
    clear_and_reinsert_row: function (dragged) {
      $('.thumbbox', dragged).empty();
      $(dragged).data('slide-id', '');
      $('div', dragged).empty().append(caption).append(link);

      $('.slideshow-sortable-rows').append(dragged);
    },

    delete_this_collection: function () {
      if (confirm("This is a destructive operation.\nAre you sure you want to\nremove this slideshow from the database?")) {
        // var is_active = $('#slideshow-is-active-collection').is(':checked');

        var data = {
          action: 'slideshow-delete-slide-collection',
          slideshow_id: $('#slideshow_select').val()
        };

        $.post(ajaxurl, data).done(function (res) {
          if (res.result == 'success') {
            alert(res.feedback);
            window.history.go(0);
          } else {
            if (res.feedback !== undefined) {
              alert(res.feedback);
            } else {
              alert('Unable to delete this slideshow');
            }
          }
        });
      } else {
        alert('Operation cancelled');
      }

      return false;
    },

    fetch_img_meta: function (post_id) {
      var data = {
        action: 'slideshow-fetch-img-meta',
        post_id: post_id
      }

      // return a deferred object
      return $.post(ajaxurl, data);
    },

    first_empty_row: function () {
      var rows = $('.slideshow-collection-row');
      for (var i = 0; i < rows.length; i++) {

        var txt = $('.slideshow-slide-title', rows[i]).text();
        if (txt.length == 0) {
          return rows[i];
        }
      }
    },

    fetch_selected_slideshow: function () {
      self.clear_drop_table_rows();
      var opt = $('#slideshow_select option:selected');

      $('.slideshow-collection-name').val($(opt).text());

      // TODO: This should just return image metadata too to save extra calls
      var data = {
        action: 'slideshow-fetch-collection',
        slideshow_id: opt.val()
      };

      $.post(ajaxurl, data).done(function (res) {
        var slides = res.slides;
        self.slideshow_id = opt.val();

        if (res.is_active === "1") {
          $('#slideshow-is-active-collection').prop('checked', true);
        } else {
          $('#slideshow-is-active-collection').prop('checked', false);
        }

        if (res.captions === "1") {
          $('#slideshow-show-captions').prop('checked', true);
        } else {
          $('#slideshow-show-captions').prop('checked', false);
        }

        for (var i = 0; i < slides.length; i++) {
          var row = $('.slideshow-collection-row').eq(i);

          if (slides[i].post_id == null) {
            // this is a text entry
            self.place_slide_text(slides[i].id, slides[i].text_title, slides[i].text_content, slides[i].slide_link, row);
          } else {
            // needs to include title/caption in db for image too - needs UI for setting same
            self.place_slide_img(slides[i].id, slides[i].post_id, slides[i].text_title, slides[i].slide_link, row);
          }
        }

        self.runtime_calculate();

        /* the layout and transition settings also need restoring */
        var layout = res.layout;
        var transition = res.transition;

        $('input[value="' + layout + '"][name="slideshow-layout"]').prop('checked', true);
        $('input[value="' + transition + '"][name="slideshow-transition"]').prop('checked', true);
      });
    },

    insert_inline_edit_toggle: function (opt) {
      var imgsrc = $('.slideshow-signals-preload img').attr('src');
      var $div = $('<div id="runtime-signal" class="slideshow-inline-edit-toggle slideshow-signals" />');
      var $img = $('<img class="signals-sprite pencil" src="' + imgsrc + '" />');
      $div.append($img);

      if (opt) {
        $img.addClass('active')
      }

      return $div;
    },

    inline_form_toggle: function () {
      // this is only the title
      // top level element === TD.slideshow-slide-title
      var target;
      var content_edit;

      if (this.id === 'inline-editor') {
        target = $(this);
      } else if (
        $(this).parent().hasClass('slide-title')
        || $(this).parent().hasClass('slide-title-wrap')
      ) {
        target = $(this).parent().parent();
      } else {
        target = $(this).parent();
      }

      // guard - only one active inline-editor at one time
      if (self.editing_node !== null && target.attr('id') === undefined) {
        // restore the graphic for all targets to neutral
        // $('.slideshow-inline-edit-toggle img').removeClass('active');
        return;
      }

      if (target.attr('id') === 'inline-editor') {  // state marker == active editor
        // restore NON-EDIT view
        var td = self.editing_node;

        var title_edit = $('#slide-title-edit');
        var link_edit = $('#slide-link-edit');
        content_edit = $('#slide-content-edit');

        if (title_edit.val().trim() === '') {
          alert('Slide title cannot be empty');
          title_edit.focus();
          return;
        }

        var title_div = $('<div class="slide-title" />').append(title_edit.val());

        var a = $('<a/>').attr('href', link_edit.val()).attr('target', '_blank').text(link_edit.val());
        var link_div = $('<div class="slide-link" />').append(a);

        td.empty().append(title_div).append(link_div);

        if (content_edit !== undefined) {
          var txt = content_edit.text().trim();
          if (txt !== null && txt !== undefined && txt !== '') {
            var content_div = $('<div class="slide-content" />').append(content_edit.val().trim());
            td.append(content_div);
          }
        }

        // restore click-to-edit functionality
        title_div
          .append(self.insert_inline_edit_toggle())
          .on('click', self.inline_form_toggle);

        target.replaceWith(td);

        // clear buffer for reuse (also, a null buffer is part of active-inline-editor detection)
        self.editing_node = null;
      } else {
        // convert to INLINE-EDITOR
        var inline_editor = $('<td class="inline-editor slideshow-slide-title" id="inline-editor" />');
        var title_edit = $('<input class="slide-title-edit" type="text" id="slide-title-edit" value="' + $('.slide-title', target).text() + '" placeholder="Caption/Title (required)" />');
        var div_title = $('<div class="slide-title-wrap"/>').append(title_edit).append(self.insert_inline_edit_toggle(1));
        var link_edit = $('<input class="slide-link-edit" type="text" id="slide-link-edit" value="' + $('.slide-link', target).text() + '" placeholder="/?page_id=123" />');

        inline_editor.append(div_title).append(link_edit);

        var text = $(target).children('.slide-content').first().text();
        if (text !== null && text !== undefined && text.trim() !== '') {
          content_edit = $('<textarea class="slide-content-edit" id="slide-content-edit" placeholder="text message">' + text + '</textarea>');
          inline_editor.append(content_edit);
        }
        // else {
        // console.log( 'No content for this slide content-text' );
        // }

        self.editing_node = target.replaceWith(inline_editor);
        $('.slideshow-inline-edit-toggle').on('click', self.inline_form_toggle);
        title_edit.trigger('focus');
      }
    },

    place_slide_img: function (id, post_id, slide_title, link, row) {
      if (row == null) {
        // get the first empty row ...
        row = self.first_empty_row();
      }

      self.fetch_img_meta(post_id).done(function (res) {
        var meta = res.meta;
        var src = meta['folder'] + meta['medium']['file'];
        var w = meta['medium']['width'];
        var h = meta['medium']['height'];

        $(row).data('slide-id', id);

        var this_title = meta['title'];
        if (slide_title != '' && title != meta['title']) {
          this_title = slide_title;
        }

        var img = $('<img data-img-id="' + post_id + '" src="' + src + '" width="' + w + '" height="' + h + '">');
        $(row).children().first().empty().append(img);

        var title = $('<div class="slide-title" />')
          .append(this_title)
          .append(self.insert_inline_edit_toggle())
          .on('click', self.inline_form_toggle);

        $(row).children().eq(1).empty().append(title);

        if (link !== undefined) {
          var anchor = $('<a class="slide-anchor" target="_blank"/>').text(link).attr('href', link);
          var div = $('<div class="slide-link" />').append(anchor);
          $(row).children().eq(1).append(div);
        }
      });
    },

    place_slide_text: function (id, title, content, link, row) {
      if (row == null) {
        // get the first empty row ...
        row = self.first_empty_row();
      }

      $(row).data('slide-id', id);
      $(row).children().first().empty().append($('<span class="slideshow-big-t">T</span>'));

      var titlediv = $('<div class="slide-title" />')
        .append(title)
        .append(self.insert_inline_edit_toggle())
        .on('click', self.inline_form_toggle);

      $(row).children().eq(1).empty().append(titlediv);

      if (link !== undefined) {
        var anchor = $('<a class="slide-anchor" target="_blank"/>').text(link).attr('href', link);
        var div = $('<div class="slide-link" />').append(anchor);
        $(row).children().eq(1).append(div);
      }

      $(row).children().eq(1).append($('<div class="slide-content" />').append(content));
    },

    runtime_calculate: function () {
      var children = $('.thumbbox').children();
      var msg = "There must be slides before calculating the runtime.";

      if (children !== undefined) {
        var index = children.length;
        var dwell = parseInt(window.coop_slideshow_settings.current.pause, 10) / 1000;
        var transit = parseInt(window.coop_slideshow_settings.current.speed, 10) / 1000;

        var net = index * (dwell + transit); // slideshow cycle in seconds

        msg = "There are " + index + " slides in this slideshow. Each slide will show for " + dwell + " seconds. ";
        msg += "Transition between slides will take " + transit + " seconds. ";
        msg += "The slideshow will take a total of " + net + " seconds to cycle completely.";
      }

      $('.slideshow-runtime-information').empty().text(msg);
    },

    save_collection: function () {
      var slides = [];
      var rows = $('.slideshow-collection-row');

      /* add check for an open inline-editor
        if open, toggle it closed before proceeding here
      */
      if (self.editing_node !== null && self.editing_node !== undefined) {
        // alert($(self.editing_node).attr('id'));
        var title = $('#inline-editor .slideshow-inline-edit-toggle');
        title.trigger('click');
      }

      for (var i = 0; i < rows.length; i++) {
        // remove the placeholder spans which are purely eyecandy
        $('span.placeholder', rows[i]).remove();

        // slide_id is set in the case of a collection having been reloaded into the editor
        var type = 'none';  // bias inherent in the system :-)
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

        // TODO: Get page/post ID instead
        // link? - read the link URL from the anchor
        slide_link = $(rows[i]).children().last().children('div.slide-link').children('a').attr('href');  // slide link box

        if (img_id === undefined) {
          // read the content of the content div
          text_content = $(rows[i]).children().last().children('div').last().text();
          if (text_title.length > 0 && text_content.length > 0) {
            type = 'text';
          }
        } else {
          type = 'image';
          post_id = img_id;
        }

        // if (type == 'text' && text_title == '') {

        // }

        // if this slide has already been saved it has a slide_id index
        slide_id = $(rows[i]).data('slide-id');

        if ((type === 'image' && post_id > 0) || (type == 'text' && text_title.length > 0)) {
          var slide = {
            type: type,
            slide_id: slide_id,
            text_title: text_title,
            text_content: text_content,
            slide_link: slide_link,
            post_id: post_id,
            ordering: i
          }
          slides.push(slide);
        }
      }

      var is_active = $('#slideshow-is-active-collection:checked').val();
      if (is_active === undefined) {
        is_active = String() + '0';
      }

      var layout = $('input[name="slideshow-layout"]:checked').val();
      if (layout === undefined) {
        layout = 'no-thumb';
      }

      var transition = $('input[name="slideshow-transition"]:checked').val();
      if (transition === undefined) {
        transition = window.coop_slideshow_settings.current.mode;
      }

      if ($('#slideshow-show-captions:checked')) {
        use_captions = '1';
      }

      var slideshow_id = $('#slideshow_select').val();

      var data = {
        action: 'slideshow-save-slide-collection',
        title: $('.slideshow-collection-name').val(),
        slideshow_id: slideshow_id,
        layout: layout,
        transition: transition,
        is_active: is_active,
        captions: use_captions,
        slides: slides
      };

      $.post(ajaxurl, data).done(function (res) {
        /// do something in response to the save attempt feedback ...
        if (res.result === 'success') {
          alert('Slide collection saved');
          // self.fetch_selected_slideshow();
          window.history.go(0);
        } else {
          alert(res.feedback);
        }
      });
    },

    slide_remove: function (dragged) {
      var img_id = $('img', dragged).data('img-id');
      $('#thumb' + img_id).removeClass('ghosted').parent().draggable('option', 'disabled', false);

      self.clear_and_reinsert_row(dragged);
    },

    /**
     * Drag/Drop Functions
     */
     drop_on_row: function (evt, ui) {
      var row = this.id;
      var dragged = ui.draggable;

      if ($(dragged).hasClass('slideshow-collection-row')) {
        // Existing slide, reorder
        self.drop_insert_row(this, ui);
      } else {
        // New slide, insert/replace
        self.drop_insert_thumbnail(row, dragged, this);
      }
    },

    drop_insert_row: function (row, ui) {
      var $t = $(row); // this
      var dragged = ui.draggable;
      var dropme = $(dragged).detach();

      $($t).before(dropme);

      self.runtime_calculate();
    },

    drop_insert_thumbnail: function (row, dragged, target) {
      var id = dragged.data('img-id');
      var cap = dragged.data('img-caption');
      var link = dragged.data('img-link');

      var thumb = $('#thumb' + id);
      var src = thumb.attr('src');
      var w = thumb.attr('width');
      var h = thumb.attr('height');

      // Add the thumbnail to the dropzone
      var thumbbox = $('.thumbbox', target);
      var img = $('<img data-img-id="' + id + '" src="' + src + '" class="selected" id="selected' + row + '" width="' + w + '" height="' + h + '">');
      thumbbox.empty().append(img);

      // Add text and link to the dropzone
      var textbox = thumbbox.next();

      $('div', textbox).first().empty().text(cap);
      var linkdiv = $('div', textbox).last();
      var anchor = linkdiv.children('a').first().attr('href', link);
      linkdiv.empty().append(anchor);

      // Ghost out source image and make undraggable
      $(thumb).addClass('ghosted').parent().draggable('option', 'disabled', true);

      self.runtime_calculate();
    },

    return_to_source: function (row, ui) {
      var dragged = ui.draggable;

      // Only continue if we're dragging an already placed slide
      if (!dragged.hasClass('slideshow-collection-row')) {
        return;
      }

      self.slide_remove(dragged);
      self.runtime_calculate();
    },
  }

  $.fn.coop_slideshow_manager = function (opts) {
    return new SlideShowSetup(opts);
  }

}(jQuery, window));


/**
 * @package Slideshow Settings
 * @copyright BC Libraries Coop 2013
 *
 **/

; (function ($, window) {
  var SlideShowSettings = function (options) {
    this._debug = false;
    this._configured = {}; // passed in options
    this.current = {};  // _defaults + _configured
    this._defaults = {};  // bxSlider factory settings
    this._touched = [];   // record keys of fields altered until a save
    this.opts = {};   // opts == current at start up (diverges as user changes settings)

    this.init(options);
  }

  SlideShowSettings.prototype = {

    init: function (options) {
      // load the definitional default set by bxSlider
      this._defaults = $.extend({}, this._defaults, window.coop_bx_defaults);

      // split out default from tuples (first in list)
      this.clean_up_defaults();

      // capture and save the configuration we were started up with (options as passed in)
      this._configured = $.extend({}, options);

      // now load our current values as set by Slideshow settings controls
      this.opts = $.extend({}, this._defaults, options);

      // duplicate starting config as current config - this gets changes by user
      this.current = $.extend({}, this._defaults, options);

      // bind the html form fields to this.current fields
      for (var p in this.current) {
        if (typeof p !== 'function') {
          $('input[name="' + p + '"]').on('change', this.set_current_value.bind(this));
        }
      }

      $('#coop-slideshow-settings-submit').on('click', this.save_changes.bind(this));

      if (this._debug) {
        console.log('returning initialized coop_slideshow_settings object');
      }

      return this;
    },

    clean_up_defaults: function () {
      /**
      * Some of the defaults are spec'd as csv alternate string values
      * The first in the tuple is the default value. Find and set that.
      **/
      for (var p in this._defaults) {
        if (typeof p !== 'function') {
          var v = this._defaults[p];
          var comma = ",";

          if (typeof v === 'string') {
            var a = v.split(comma);

            if (a.length > 1) {
              this._defaults[p] = a[0];
            }
          }
        }
      }
    },

    save_changes: function () {
      // save button has been clicked
      if (this._debug) {
        console.log('save button has been clicked');
      }

      // determine which settings are now different (
      var changed = {};
      var keys = [];

      for (var p in this.opts) {
        if (typeof p !== 'function') {
          if (this.opts[p] !== this.current[p]) {
            keys.push(p);
            changed[p] = this.current[p];
            if (this._debug) console.log('changed: ' + p);
          } else {
            for (var i in this._touched) {
              if (typeof i !== 'function') {
                if (this._debug) console.log('_touched[i]: ' + this._touched[i] + ' <=> ' + p);
                if (this._touched[i] == p) {
                  if (this._debug) console.log('_touched[i]: ' + this._touched[i] + ' == ' + p);
                  keys.push(p);
                  changed[p] = this.current[p];
                  break;
                }
              }
            }
          }
        }
      }

      // if changed is still an empty object ...
      if (changed === {} || keys.length === 0) {
        alert('No changes found, not updating settings');

        return false;
      }

      // otherwise continue to build data object to send server-side
      changed['action'] = 'coop-save-slideshow-change';
      changed['keys'] = keys;

      if (this._debug) console.log('posting data');

      var self = this;
      $.post(ajaxurl, changed).done(function (res) {
        if (self._debug) console.log('response returned');

        alert(res.feedback);

        self._touched = [];
      });
    },

    touched: function (id) {
      this._touched.push(id);
      if (this._debug) console.log(this._touched);
    },

    set_current_value: function (event) {
      // update self.current to reflect the user's changes
      var id = event.target.getAttribute('name');
      var val = event.target.value;

      if (val == '') {
        val = 'empty';
      }

      if (this._debug) console.log(id + ': ' + val);

      this.current[id] = val;
      this.touched(id);
    }
  }

  $.fn.coop_slideshow_settings = function () {
    return new SlideShowSettings();
  }
}(jQuery, window));

jQuery().ready(function () {
  window.coop_slideshow_settings = jQuery().coop_slideshow_settings();

  if (window.pagenow === 'site-manager_page_top-slides') {
    window.slideshow_manager = jQuery().coop_slideshow_manager();

    jQuery('.draggable').draggable({
      cursor: 'move',
      stack: '.slide',
      opacity: 0.7,
      helper: 'clone',
    });

    jQuery('.droppable').droppable({
      drop: slideshow_manager.drop_on_row,
      hoverClass: 'drop_highlight'
    });

    jQuery('.returnable').droppable({
      drop: slideshow_manager.return_to_source,
      hoverClass: 'return_highlight'
    });
  }
});
