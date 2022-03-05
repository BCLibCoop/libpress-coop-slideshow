; (function ($, window) {
  /**
   * @package Slideshow Setup
   * @copyright BC Libraries Coop 2013
   **/
  var SlideShowSetup = function () {
    this._configured = {}; // passed in options
    this.editing_node = null;
    this.opts = {}; // opts == current at start up (diverges as user changes settings)
    this.slideshow_id = 0;

    this.init();
  }

  SlideShowSetup.prototype = {

    init: function () {
      // init hook-ups listed more-or-less in page order
      $('button.add-new').on('click', this.init_add_new_state.bind(this));

      $('#slideshow-select')
        .chosen({disable_search_threshold: 10})
        .on('change', this.fetch_selected_slideshow.bind(this));

      $('#slideshow-page-selector').chosen({disable_search_threshold: 10});

      $('.slideshow-save-collection-btn').on('click', this.save_collection.bind(this));
      $('.slideshow-delete-collection-btn').on('click', this.delete_this_collection.bind(this));
      $('.slideshow-text-slide-cancel-btn').on('click', this.clear_text_slide_form.bind(this));
      $('.slideshow-text-slide-save-btn').on('click', this.add_text_only_slide.bind(this));

      $('#runtime-signal img.signals-sprite').on('click', this.runtime_calculate.bind(this));

      // retrieve the currently active slideshow by default
      this.fetch_selected_slideshow();

      // Set up draggable/droppable areas
      $('.draggable').draggable({
        cursor: 'move',
        stack: '.slide',
        helper: 'clone',
        opacity: 0.7,
      });

      $('.droppable').droppable({
        drop: this.drop_on_row.bind(this),
        hoverClass: 'drop-highlight'
      });

      $('.returnable').droppable({
        drop: this.return_to_source.bind(this),
        hoverClass: 'return-highlight'
      });
    },

    init_add_new_state: function () {
      $('#slideshow-select').remove();
      $('#slideshow-select_chzn').remove();
      this.clear_drop_table_rows();

      // clear activated collection flag
      $('#slideshow-is-active-collection').prop('checked', false);

      // clear the input field, show and set focus
      $('.slideshow-collection-name').show().val('').focus();
    },

    add_text_only_slide: function () {
      var slideshow_collection_name = $('.slideshow-collection-name').val();
      var slideshow_id = $('#slideshow-select').val();

      if (slideshow_collection_name == '' && slideshow_id == null) {
        alert('Whoops! You need to name the slideshow first.');
        $('.slideshow-collection-name').trigger('focus');

        return false;
      }

      var title = $('#slideshow-text-slide-heading').val();
      var content = $('#slideshow-text-slide-content').val();

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
      };

      var self = this;

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
      $('div', dragged).empty();

      $('.slideshow-sortable-rows').append(dragged);
    },

    delete_this_collection: function () {
      if (confirm("This is a destructive operation.\nAre you sure you want to\nremove this slideshow from the database?")) {
        // var is_active = $('#slideshow-is-active-collection').is(':checked');

        var data = {
          action: 'slideshow-delete-slide-collection',
          slideshow_id: $('#slideshow-select').val()
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
      this.clear_drop_table_rows();
      var opt = $('#slideshow-select option:selected');

      $('.slideshow-collection-name').val($(opt).text());

      var data = {
        action: 'slideshow-fetch-collection',
        slideshow_id: opt.val()
      };

      var self = this;

      $.post(ajaxurl, data).done(function (res) {
        var slides = res.slides;
        self.slideshow_id = opt.val();

        $('#slideshow-is-active-collection').prop('checked', (res.is_active === '1'));
        $('#slideshow-show-captions').prop('checked', (res.captions === '1'));

        /* the layout and transition settings also need restoring */
        $('input[value="' + res.layout + '"][name="slideshow-layout"]').prop('checked', true);
        $('input[value="' + res.transition + '"][name="slideshow-transition"]').prop('checked', true);

        for (var i = 0; i < slides.length; i++) {
          var row = $('.slideshow-collection-row').eq(i);

          if (slides[i].post_id == null) {
            // this is a text entry
            self.place_slide_text(slides[i], row);
          } else {
            // image slide
            self.place_slide_img(slides[i], row);
          }
        }

        self.runtime_calculate();
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

    inline_form_toggle: function (event) {
      // this is only the title
      // top level element === TD.slideshow-slide-title
      var target = event.target;
      var content_edit;

      if (target.id === 'inline-editor') {
        target = $(target);
      } else if (
        $(target).parent().hasClass('slide-title')
        || $(target).parent().hasClass('slide-title-wrap')
      ) {
        target = $(target).parent().parent();
      } else {
        target = $(target).parent();
      }

      // guard - only one active inline-editor at one time
      if (this.editing_node !== null && target.attr('id') === undefined) {
        // restore the graphic for all targets to neutral
        // $('.slideshow-inline-edit-toggle img').removeClass('active');
        return;
      }

      if (target.attr('id') === 'inline-editor') {  // state marker == active editor
        // restore NON-EDIT view
        var td = this.editing_node;

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
          .append(this.insert_inline_edit_toggle())
          .on('click', this.inline_form_toggle.bind(this));

        target.replaceWith(td);

        // clear buffer for reuse (also, a null buffer is part of active-inline-editor detection)
        this.editing_node = null;
      } else {
        // convert to INLINE-EDITOR
        var inline_editor = $('<td class="inline-editor slideshow-slide-title" id="inline-editor" />');
        var title_edit = $('<input class="slide-title-edit" type="text" id="slide-title-edit" value="' + $('.slide-title', target).text() + '" placeholder="Caption/Title (required)" />');
        var div_title = $('<div class="slide-title-wrap"/>').append(title_edit).append(this.insert_inline_edit_toggle(1));
        var link_edit = $('<input class="slide-link-edit" type="text" id="slide-link-edit" value="' + $('.slide-link', target).text() + '" placeholder="/?page_id=123" />');

        inline_editor.append(div_title).append(link_edit);

        var text = $(target).children('.slide-content').first().text();
        if (text !== undefined &&  text !== null && text.trim() !== '') {
          content_edit = $('<textarea class="slide-content-edit" id="slide-content-edit" placeholder="text message">' + text + '</textarea>');
          inline_editor.append(content_edit);
        }
        // else {
        // console.log( 'No content for this slide content-text' );
        // }

        this.editing_node = target.replaceWith(inline_editor);
        $('.slideshow-inline-edit-toggle').on('click', this.inline_form_toggle.bind(this));
        title_edit.trigger('focus');
      }
    },

    place_slide_img: function (slide, row) {
      if (slide.meta.length === 0) {
        console.log('no image metadata returned');
        return;
      }

      if (row == null) {
        // get the first empty row ...
        row = this.first_empty_row();
      }

      $(row).data('slide-id', slide.id);

      // Prefer the title of the slide rather than the image
      var this_title = slide.meta.title;
      if (slide.text_title != '' && slide.text_title != this_title) {
        this_title = slide.text_title;
      }

      var img = $('<img data-img-id="' + slide.post_id + '" src="' + slide.meta.src + '" width="' + slide.meta.width + '" height="' + slide.meta.height + '">');
      $(row).children().first().empty().append(img);

      var title = $('<div class="slide-title" />')
        .append(this_title)
        .append(this.insert_inline_edit_toggle())
        .on('click', this.inline_form_toggle.bind(this));

      $(row).children().eq(1).empty().append(title);

      if (slide.slide_link !== undefined && slide.slide_link !== null && slide.slide_link.trim() !== '') {
        var anchor = $('<a class="slide-anchor" target="_blank"/>').text(slide.slide_link).attr('href', slide.slide_link);
        var div = $('<div class="slide-link" />').append(anchor);
        $(row).children().eq(1).append(div);
      }
    },

    place_slide_text: function (slide, row) {
      if (row == null) {
        // get the first empty row ...
        row = this.first_empty_row();
      }

      $(row).data('slide-id', slide.id);
      $(row).children().first().empty().append($('<span class="slideshow-big-t">T</span>'));

      var titlediv = $('<div class="slide-title" />')
        .append(slide.text_title)
        .append(this.insert_inline_edit_toggle())
        .on('click', this.inline_form_toggle.bind(this));

      $(row).children().eq(1).empty().append(titlediv);

      if (slide.slide_link !== undefined && slide.slide_link !== null && slide.slide_link.trim() !== '') {
        var anchor = $('<a class="slide-anchor" target="_blank"/>').text(slide.slide_link).attr('href', slide.slide_link);
        var div = $('<div class="slide-link" />').append(anchor);
        $(row).children().eq(1).append(div);
      }

      $(row).children().eq(1).append($('<div class="slide-content" />').append(slide.text_content));
    },

    runtime_calculate: function () {
      var children = $('.thumbbox').children();
      var msg = 'There must be slides before calculating the runtime.';

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
      if (this.editing_node !== null && this.editing_node !== undefined) {
        var title = $('#inline-editor .slideshow-inline-edit-toggle');
        title.trigger('click');
      }

      var the_title = $('.slideshow-collection-name').val();

      if (the_title.trim().length === 0) {
        alert('Please ensure your slideshow has a name');
        return false;
      }

      var slideshow_id = $('#slideshow-select').val();

      var is_active = '0'
      if ($('#slideshow-is-active-collection:checked').length) {
        is_active = '1';
      }

      var layout = $('input[name="slideshow-layout"]:checked').val();
      if (layout === undefined) {
        layout = 'no-thumb';
      }

      var transition = $('input[name="slideshow-transition"]:checked').val();
      if (transition === undefined) {
        transition = window.coop_slideshow_settings.current.mode;
      }

      var use_captions = '0';
      if ($('#slideshow-show-captions:checked').length) {
        use_captions = '1';
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

        var img = $(rows[i]).children().first().children('img');
        var img_id = $(img).data('img-id');

        // read the title from it's box
        text_title = $(rows[i]).children().last().children('div.slide-title').first().text();

        // TODO: Get page/post ID instead
        // link? - read the link URL from the anchor
        slide_link = $(rows[i]).children().last().children('div.slide-link').children('a').attr('href');  // slide link box

        if (img_id === undefined || img_id === null || img_id === '') {
          // read the content of the content div
          text_content = $(rows[i]).children().last().children('div').last().text();
          if (text_title.length > 0 && text_content.length > 0) {
            type = 'text';
          }
        } else if (img_id > 0) {
          type = 'image';
          post_id = img_id;
        }

        // if this slide has already been saved it has a slide_id index
        slide_id = $(rows[i]).data('slide-id');

        // Only add the slide if we detected a type correctly
        if (type === 'image' || type === 'text') {
          slides.push({
            type: type,
            slide_id: slide_id,
            text_title: text_title,
            text_content: text_content,
            slide_link: slide_link,
            post_id: post_id,
            ordering: i
          });
        }
      }

      var data = {
        action: 'slideshow-save-slide-collection',
        title: the_title,
        slideshow_id: slideshow_id,
        layout: layout,
        transition: transition,
        is_active: is_active,
        captions: use_captions,
        slides: slides
      };

      $.post(ajaxurl, data).done(function (res) {
        // do something in response to the save attempt feedback ...
        if (res.result === 'success') {
          alert('Slide collection saved');
          // Doing a full refresh for now to ensure we have a good state
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

      this.clear_and_reinsert_row(dragged);
    },

    /**
     * Drag/Drop Functions
     */
     drop_on_row: function (event, ui) {
      var row = this.id;
      var dragged = ui.draggable;

      if ($(dragged).hasClass('slideshow-collection-row')) {
        // Existing slide, reorder
        this.drop_insert_row(event.target, ui);
      } else {
        // New slide, insert/replace
        this.drop_insert_thumbnail(row, dragged, event.target);
      }
    },

    drop_insert_row: function (row, ui) {
      var $t = $(row); // this
      var dragged = ui.draggable;
      var dropme = $(dragged).detach();

      $($t).before(dropme);

      this.runtime_calculate();
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

      this.runtime_calculate();
    },

    return_to_source: function (row, ui) {
      var dragged = ui.draggable;

      // Only continue if we're dragging an already placed slide
      if (!dragged.hasClass('slideshow-collection-row')) {
        return;
      }

      this.slide_remove(dragged);
      this.runtime_calculate();
    },
  }

  /**
   * @package Slideshow Settings
   * @copyright BC Libraries Coop 2013
   **/
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

  /**
   * Ready
   */
  $(function() {
    window.coop_slideshow_settings = new SlideShowSettings();

    if (window.pagenow === 'site-manager_page_top-slides') {
      window.slideshow_manager = new SlideShowSetup();
    }
  });
}(jQuery, window));
