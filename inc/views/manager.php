
<div class="wrap">

    <h1 class="wp-heading-inline">Slideshow Collection Manager</h1>
    <hr class="wp-header-end">

    <p>
        This page supports the creation of Slideshows: a series of images / text slides which rotate
        automatically from one to the next. A slideshow can comprise up to five slides
        (for best viewing effect). An image suitable for use in the slideshow is 1000 pixels wide x 300
        pixels high. Images should be prepared under the Media menu, and must be given a Media Tag of:
        <b>slide</b>.
    </p>

    <table class="slideshow-header-controls">
        <tr>
            <td class="slideshow-name">
                <a class="button add-new" href="">Add new</a>&nbsp;
                <input type="text" class="slideshow-collection-name" name="slideshow-collection-name"
                    value="" placeholder="Enter a name for a new slideshow">
            </td>

            <td class="slideshow-gutter">&nbsp;</td>

            <td class="slideshow-controls">
                <a href="" class="button button-primary slideshow-save-collection-btn">Save collection</a>
                <a href="" class="button slideshow-delete-collection-btn">Delete the loaded slideshow</a>
            </td>
        </tr>

        <tr>
            <td class="slideshow-name">
                <?php echo $this->slideshowCollectionSelector(); ?>
            </td>

            <td class="slideshow-gutter">&nbsp;</td>

            <td class="slideshow-signal-preload"></td>
        </tr>
    </table>

    <table class="slideshow-drag-drop-layout">
        <tr class="master-row">
            <td class="slideshow-dropzone">
                <table class="slideshow-sortable-rows">
                    <tr class="head-row">
                        <th></th>
                        <th>
                            <div class="slideshow-controls-right">
                                <input type="checkbox" id="slideshow-is-active-collection"
                                    class="slideshow-is-active-collection" value="1">
                                <label for="slideshow-is-active-collection" class="slideshow-activate-collection">
                                    This is the active slideshow
                                </label>
                            </div>

                            Caption/Title<br/>
                            <span class="slideshow-slide-link-header">Slide Link</span>
                        </th>
                    </tr>

                    <?php for ($i = 0; $i < 5; $i++) : ?>
                        <tr id="row<?= $i ?>" class="slideshow-collection-row draggable droppable" id="dropzone<?= $i ?>">
                            <td class="thumbbox">&nbsp;</td>
                            <td class="slideshow-slide-title">
                                <div class="slide-title"><span class="placeholder">Caption/Title</span></div>
                                <div class="slide-link"><span class="placeholder">Link URL</span></div>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </table><!-- .slideshow-sortable-rows -->

                <div id="runtime-signal" class="slideshow-signals">
                    <img src="<?= $this->sprite ?>" class="signals-sprite">
                </div>

                <h3 class="slideshow-runtime-heading">Runtime information:</h3>

                <div class="slideshow-runtime-information"></div>

                <?php echo $this->textSlideCreateForm(); ?>

                <?php echo $this->quickSetLayoutControls(); ?>

            </td><!-- .slideshow-dropzone -->

            <td class="slideshow-gutter">&nbsp;</td>

            <td class="slideshow-dragzone">
                <table class="slideshow-drag-table">
                    <tr>
                        <th class="alignleft slide-heading">Your Slide Images</th>
                    </tr>

                    <tr>
                        <td id="slide-remove-local" class="slideshow-draggable-items returnable local">
                            <?php echo implode("\n", $this->fetchSlides(null)); ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="alignleft slide-heading shared-slides">Shared Slide Images</th>
                    </tr>

                    <tr>
                        <td id="slide-remove-shared" class="slideshow-draggable-items returnable shared">
                            <?php
                            switch_to_blog(1);
                            echo implode("\n", $this->fetchSlides());
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="alignleft bc-slides">British Columbia</th>
                    </tr>

                    <tr>
                        <td id="slide-remove-shared" class="slideshow-draggable-items returnable shared">
                            <?php echo implode("\n", $this->fetchSlides('BC')); ?>
                        </td>
                    </tr>

                    <tr>
                        <th class="alignleft mb-slides">Manitoba</th>
                    </tr>

                    <tr>
                        <td id="slide-remove-shared" class="slideshow-draggable-items returnable shared">
                            <?php
                            echo implode("\n", $this->fetchSlides('MB'));
                            restore_current_blog();
                            ?>
                        </td>
                    </tr>
                </table>
            </td><!-- .slideshow-dragzone -->
        </tr><!-- .master-row -->
    </table><!-- .slideshow-drag-drop-layout -->

    <div class="slideshow-signals-preload">
        <img src="<?= $this->sprite ?>" width="362" height="96">
    </div>
</div>
