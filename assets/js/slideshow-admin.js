; (function ($, window) {
  /**
   * @package Slideshow Setup
   * @copyright BC Libraries Coop 2013
   **/
  var SlideshowAdmin = function () {
    this.editing_node = null;

    this.init();
  }

  SlideshowAdmin.prototype = {

    init: function () {
      // init hook-ups listed more-or-less in page order
      $('button.add-new').on('click', this.addNewShow.bind(this));

      $('#slideshow-select').chosen({disable_search_threshold: 10})
        .on('change', this.fetchSelectedSlideshow.bind(this));

      $('#slideshow-page-selector').chosen({disable_search_threshold: 10});

      $('.slideshow-save-collection-btn').on('click', this.saveCollection.bind(this));
      $('.slideshow-delete-collection-btn').on('click', this.deleteCollection.bind(this));
      $('.slideshow-text-slide-cancel-btn').on('click', this.clearTextSlideForm.bind(this));
      $('.slideshow-text-slide-save-btn').on('click', this.addTextOnlySlide.bind(this));

      // retrieve the currently active slideshow by default
      this.fetchSelectedSlideshow();

      // Set up draggable/droppable areas
      $('.draggable').draggable({
        cursor: 'move',
        stack: '.slide',
        helper: 'clone',
        opacity: 0.7,
      });

      $('.droppable').droppable({
        drop: this.dropOnRow.bind(this),
        hoverClass: 'drop-highlight'
      });

      $('.returnable').droppable({
        drop: this.returnToSource.bind(this),
        hoverClass: 'return-highlight'
      });

      // Thumbnail Hover
      $('.slideshow-draggable-items').tooltip({
        items: '.draggable',
        content: function () {
          return $('<img />')
            .attr('src', $(this).data('tooltip-src'))
            .attr('width', $(this).data('tooltip-w'))
            .attr('height', $(this).data('tooltip-h'))
            .css('display', 'block');
        },
        position: {
          my: 'center bottom',
          at: 'center top',
          collision: 'fit',
        },
        show: {
          delay: 700,
        },
      });
    },

    addNewShow: function (event) {
      $('#slideshow_select_chosen').hide();
      $('button.add-new').parent().hide();
      this.clearDropTableRows();

      // clear activated collection flag
      $('#slideshow-is-active-collection').prop('checked', false);

      // clear the input field, show and set focus
      $('.slideshow-collection-name').show().val('').trigger('focus');
    },

    addTextOnlySlide: function () {
      var $emptyRow = this.getFirstEmptyRow();

      if ($emptyRow === null) {
        alert('Please remove an existing slide before attempting to add a text slide.');
        return false;
      }

      var title = $('#slideshow-text-slide-heading').val().trim();
      var content = $('#slideshow-text-slide-content').val().trim();

      if (title === '' || content === '') {
        alert('You must enter a title and a message');

        return false;
      }

      // Get post ID and permalink
      var $selectedLink = $('#slideshow-page-selector option:selected')
      var slide_link = $selectedLink.val().trim();
      var slide_permalink = $selectedLink.data('permalink');

      var slide = {
        id: '',
        post_id: null,
        slide_link: slide_link,
        slide_permalink: slide_permalink,
        text_title: title,
        text_content: content,
      };

      this.placeSlide(slide, $emptyRow);
      this.clearTextSlideForm();
    },

    clearTextSlideForm: function () {
      $('#slideshow-text-slide-heading').empty().val('');
      $('#slideshow-text-slide-content').empty().val('');
      $('#slideshow-page-selector').val('').trigger('chosen:updated');
    },

    clearDropTableRows: function () {
      var self = this;
      var $rows = $('.slideshow-collection-row');

      $rows.each(function () {
        self.clearAndReinsertRow($(this), true)
      });
    },

    /**
    * Re-use rows after deleting an entry
    **/
    clearAndReinsertRow: function ($row, skipAppend) {
      // TODO: Extend placeSlide to do placeholders and replace this?
      var $caption = $('<div class="slide-title"><span class="placeholder">Alt Text/Text Slide Title</span></div>');
      var $link = $('<div class="slide-link"><span class="placeholder">Slide Link</span></div>');

      $row.data('slide-id', '');
      $row.find('.thumbbox').empty();
      $row.find('.slideshow-slide-title')
        .empty()
        .append($caption.clone())
        .append($link.clone());

      if (!skipAppend) {
        $('.slideshow-sortable-rows').append($row);
      }
    },

    deleteCollection: function () {
      if (confirm("This is a destructive operation.\nAre you sure you want to\nremove this slideshow from the database?")) {
        var $slideshowSelect = $('#slideshow-select');
        var showId = $slideshowSelect.val();

        var data = {
          action: 'slideshow-delete-slide-collection',
          slideshow_id: showId,
          _ajax_nonce: coop_slideshow.nonce,
        };

        $.post(ajaxurl, data).done(function (res) {
          if (res.result == 'success') {
            // alert(res.feedback);
            $slideshowSelect.find('option[value="' + showId + '"').remove();
            $slideshowSelect.val('').trigger('chosen:updated');
            this.clearDropTableRows();
          } else {
            if (res.feedback !== undefined) {
              alert(res.feedback);
            } else {
              alert('Unable to delete this slideshow');
            }
          }
        });
      } else {
        // alert('Operation cancelled');
      }

      return false;
    },

    getFirstEmptyRow: function () {
      var $rows = $('.slideshow-collection-row');
      var $emptyRow = null;

      $rows.each(function () {
        var $row = $(this);
        if (
          $emptyRow === null
          && $row.find('.slideshow-slide-title .slide-title').contents().filter(function() {return this.nodeType === 3}).text().trim().length === 0
        ) {
          $emptyRow = $row;
        }
      });

      return $emptyRow;
    },

    fetchSelectedSlideshow: function () {
      this.clearDropTableRows();
      var $opt = $('#slideshow-select option:selected');

      // If we got the placeholder (aka no active show), don't continue
      if ($opt.val() === '') {
        return;
      }

      $('.slideshow-collection-name').val($opt.text());

      var data = {
        action: 'slideshow-fetch-collection',
        slideshow_id: $opt.val(),
        _ajax_nonce: coop_slideshow.nonce,
      };

      var self = this;

      $.post(ajaxurl, data).done(function (show) {
        self.slideshow_id = show.id;

        $('#slideshow-is-active-collection').prop('checked', show.is_active);

        $('input[name="slideshow-captions"]').prop('checked', show.captions);

        // Make bespoke options part of array
        show.options['layout'] = show.layout;
        show.options['transition'] = show.transition;

        // Restore all options
        for (var option in show.options) {
          $('input[name="slideshow-' + option + '"][value="' + show.options[option] + '"]').prop('checked', true);
        }

        // Populate slides
        show.slides.forEach(function(slide, index) {
          var $row = $('.slideshow-collection-row').eq(index);

          self.placeSlide(slide, $row);
        });
      });
    },

    insertInlineEditToggle: function (opt) {
      // TODO: If everything gets moved into placeSlide, this can probably go along with it
      var imgsrc = $('.slideshow-signals-preload img').attr('src');
      var $div = $('<div id="row-signal" class="slideshow-inline-edit-toggle slideshow-signals" />');
      var $img = $('<img class="signals-sprite pencil" src="' + imgsrc + '" />');

      if (opt) {
        $img.addClass('active')
      }

      $div.append($img);

      return $div;
    },

    toggleInlineForm: function (event) {
      // Get the parent TD
      var $target = $(event.target).parents('.slideshow-slide-title');

      // guard - only one active inline-editor at one time
      if (this.editing_node !== null && $target.attr('id') === undefined) {
        // TODO: Flip the active editor to the one clicked?
        return;
      }


      if ($target.attr('id') === 'inline-editor') {
        // restore NON-EDIT view
        // TODO: Replace with call to placeSlide()
        var $td = this.editing_node;

        var title_edit = $('#slide-title-edit').val().trim();
        var $link_edit = $('#slide-link-edit');
        var $content_edit = $('#slide-content-edit');

        if (title_edit.length === 0) {
          alert('Slide title cannot be empty');
          return;
        }

        var $title_div = $('<div class="slide-title" />').text(title_edit);

        var $a = $('<a/>')
          .attr('href', $link_edit.val().trim())
          .attr('target', '_blank')
          // Restore link data
          .data('slide-link', $link_edit.data('slide-link'))
          .data('slide-permalink', $link_edit.data('slide-permalink'))
          .text($link_edit.val().trim());
        var $link_div = $('<div class="slide-link" />').append($a);

        $td
          .empty()
          .append($title_div)
          .append($link_div);

        if ($content_edit.length) {
          var txt = $content_edit.text().trim();

          if (txt.length) {
            var $content_div = $('<div class="slide-content" />').text(txt);
            $td.append($content_div);
          }
        }

        // restore click-to-edit functionality
        $title_div
          .append(this.insertInlineEditToggle())
          .on('click', this.toggleInlineForm.bind(this));

        $target.replaceWith($td);

        // clear buffer for reuse (also, a null buffer is part of active-inline-editor detection)
        this.editing_node = null;
      } else {
        // convert to INLINE-EDITOR
        var $inline_editor = $('<td class="inline-editor slideshow-slide-title" id="inline-editor" />');
        var $title_edit = $('<input class="slide-title-edit" type="text" id="slide-title-edit" placeholder="Alt Text/Text Slide Title (required)" />')
          .val($target.find('.slide-title').text());
        var $div_title = $('<div class="slide-title-wrap" />')
          .append($title_edit)
          .append(this.insertInlineEditToggle(1));
        var $anchor = $target.find('.slide-link a');
        var $link_edit = $('<input class="slide-link-edit" type="text" id="slide-link-edit" placeholder="/explore/" />')
          .val($anchor.attr('href'))
          // Keep link data
          .data('slide-link', $anchor.data('slide-link'))
          .data('slide-permalink', $anchor.data('slide-permalink'));

        $inline_editor
          .append($div_title)
          .append($link_edit);

        var $content = $target.find('.slide-content');

        if ($content.length) {
          var txt = $content.text().trim();

          if (txt.length) {
            var $content_edit = $('<textarea class="slide-content-edit" id="slide-content-edit" placeholder="text message" />')
              .text(txt);
            $inline_editor.append($content_edit);
          }
        }

        this.editing_node = $target.replaceWith($inline_editor);
        $('.slideshow-inline-edit-toggle').on('click', this.toggleInlineForm.bind(this));
        $title_edit.trigger('focus');
      }
    },

    placeSlide: function (slide, $row) {
      if ($row == null) {
        // get the first empty row
        $row = this.getFirstEmptyRow();
      }

      $row.data('slide-id', slide.id);
      var $thumbbox = $row.find('.thumbbox');
      var $titletd = $row.find('.slideshow-slide-title');

      // If we don't have a slide title but do have an image title, use it
      var this_title = slide.text_title;
      if (slide.text_title === '' && slide.meta && slide.meta.title) {
        this_title = slide.meta.title;
      }

      if (slide.post_id == null) {
        var $thumbContent = $('<span class="slideshow-big-t">T</span>');
      } else {
        var $thumbContent = $('<img />')
          .attr('src', slide.meta.src)
          .attr('width', slide.meta.width)
          .attr('height', slide.meta.height)
          .data('img-id', slide.post_id);
      }

      $thumbbox
        .empty()
        .append($thumbContent);

      var $title = $('<div class="slide-title" />')
        .text(this_title)
        .append(this.insertInlineEditToggle())
        .on('click', this.toggleInlineForm.bind(this));

      $titletd
        .empty()
        .append($title);

      if (
        slide.slide_permalink !== undefined
        && slide.slide_permalink !== null
        && slide.slide_permalink.trim() !== ''
      ) {
        var $anchor = $('<a class="slide-anchor" target="_blank" />')
          .attr('href', slide.slide_permalink)
          // Store original link values for later checks
          .data('slide-link', slide.slide_link)
          .data('slide-permalink', slide.slide_permalink)
          .text(slide.slide_permalink);

        $titletd
          .append($('<div class="slide-link" />')
          .append($anchor));
      }

      if (slide.text_content !== undefined) {
        $titletd.append($('<div class="slide-content" />').text(slide.text_content));
      }
    },

    saveCollection: function () {
      var $rows = $('.slideshow-collection-row');

      var data = {
        action: 'slideshow-save-slide-collection',
        slideshow_id: 0,
        title: '',
        is_active: 0,
        slides: [],
        _ajax_nonce: coop_slideshow.nonce,
      };

      /* add check for an open inline-editor
        if open, toggle it closed before proceeding here
      */
      if (this.editing_node !== null && this.editing_node !== undefined) {
        var title = $('#inline-editor .slideshow-inline-edit-toggle');
        title.trigger('click');
      }

      /**
       * Global Slideshow Settings
       */

      if ($('#slideshow_select_chosen:visible').length) {
        data.slideshow_id = parseInt($('#slideshow-select').val());
      }

      var the_title = $('.slideshow-collection-name').val().trim();

      if (the_title.length === 0) {
        alert('Please ensure your slideshow has a name');
        return false;
      } else {
        // Don't include the IDs in the saved name or else they will just keep growing
        data.title = the_title.replaceAll(/ \(ID \d+\)/g, '');
      }

      if ($('#slideshow-is-active-collection').is(':checked')) {
        data.is_active = 1;
      }

      // Collect all settings
      $('.slideshow-controls input').each(function(index) {
        var $setting = $(this);
        var settingName = $setting.attr('name').replace('slideshow-', '');

        if ($setting.is(':checked')) {
          data[settingName] = $setting.val();
        }
      });

      /**
       * Individual Slides
       */
      $rows.each(function (index) {
        var $row = $(this);

        // remove the placeholder spans which are purely eyecandy
        $row.find('span.placeholder').remove();

        // If the link hasn't been changed and the original value is
        // an ID, pass that along
        var $anchor = $row.find('.slide-link a');
        var slide_link = $anchor.attr('href');
        if (
          $anchor.data('slide-permalink') === slide_link
          && parseInt($anchor.data('slide-link')) > 0
        ) {
          slide_link = $anchor.data('slide-link');
        }

        var slide_data = {
          type: 'none',
          slide_id: $row.data('slide-id'),
          text_title: $row.find('.slide-title').text(),
          text_content: '',
          slide_link: slide_link,
          post_id: '',
          ordering: index,
        };

        var img_id = $row.find('img').data('img-id');

        if (img_id === undefined || img_id.length === 0) {
          // read the content of the content div
          slide_data.text_content = $row.find('.slide-content').text();

          if (slide_data.text_title.length > 0 && slide_data.text_content.length > 0) {
            slide_data.type = 'text';
          }
        } else if (img_id !== undefined && img_id > 0) {
          slide_data.type = 'image';
          slide_data.post_id = img_id;
        }

        // Only add the slide if we detected a type correctly
        if (slide_data.type === 'image' || slide_data.type === 'text') {
          data.slides.push(slide_data);
        }
      });

      var self = this;

      $.post(ajaxurl, data).done(function (res) {
        // do something in response to the save attempt feedback ...
        if (res.result === 'success') {
          $slideshowSelect = $('#slideshow-select');

          // Add to the dropdown if this is a new show
          if ($slideshowSelect.find('option[value="' + res.slideshow_id + '"]').length === 0) {
            $slideshowSelect.append($('<option>', {value: res.slideshow_id, text: the_title}));
            $slideshowSelect.val(res.slideshow_id).trigger('chosen:updated');
          }

          // Restore the select dropdown and add new button if they were hidden
          $('.slideshow-collection-name').hide();
          $('#slideshow_select_chosen').show();

          $('button.add-new').parent().show();

          self.fetchSelectedSlideshow();

          alert('Slide collection saved');
        } else {
          alert(res.feedback);
        }
      });
    },

    removeSlide: function ($dragged) {
      var img_id = $dragged.find('img').data('img-id');
      $('#thumb' + img_id).parent()
        .removeClass('ghosted');
        // .draggable('option', 'disabled', false);

      this.clearAndReinsertRow($dragged);
    },

    /**
     * Drag/Drop Functions
     */
    dropOnRow: function (event, ui) {
      var $row = $(event.target);
      var $dragged = $(ui.draggable);

      if ($dragged.hasClass('slideshow-collection-row')) {
        // Existing slide, reorder
        this.dropInsertRow($row, $dragged);
      } else {
        // New slide, insert/replace
        this.dropInsertThumbnail($row, $dragged);
      }
    },

    dropInsertRow: function ($row, $dragged) {
      var $rows = $('.slideshow-collection-row');
      var $dropme = $dragged.detach();

      if ($rows.index($dragged) > $rows.index($row)) {
        $row.before($dropme);
      } else {
        $row.after($dropme);
      }
    },

    dropInsertThumbnail: function ($row, $dragged) {
      // TODO: Replace most of this with placeSlide too?
      var id = $dragged.data('img-id');
      var cap = $dragged.data('img-caption');

      var $thumb = $dragged.find('img');
      var src = $thumb.attr('src');
      var w = $thumb.attr('width');
      var h = $thumb.attr('height');

      // Add the thumbnail to the dropzone
      var $thumbbox = $row.find('.thumbbox');
      var $img = $('<img />')
        .attr('src',src)
        .attr('width', w)
        .attr('height', h)
        .data('img-id', id);

      $thumbbox
        .empty()
        .append($img);

      // Add text to the dropzone
      var $textbox = $row.find('.slideshow-slide-title');

      $textbox
        .empty()
        .append($('<div class="slide-title" />').text(cap))
        .append(this.insertInlineEditToggle())
        .on('click', this.toggleInlineForm.bind(this));

      // Ghost out source image and make undraggable
      $dragged
        .addClass('ghosted');
        // .draggable('option', 'disabled', true); // Not making un-dragable

    },

    returnToSource: function (event, ui) {
      var $dragged = (ui.draggable);

      // Only continue if we're dragging an already placed slide
      if (!$dragged.hasClass('slideshow-collection-row')) {
        return;
      }

      this.removeSlide($dragged);
    },
  }

  /**
   * Ready
   */
  $(function() {
    window.slideshow_admin = new SlideshowAdmin();
  });
}(jQuery, window));
