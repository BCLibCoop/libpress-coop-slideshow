
<div class="wrap">

    <h1 class="wp-heading-inline">Slideshow Collection Manager</h1>
    <hr class="wp-header-end">

    <p>
        This page supports the creation of Slideshows: a series of up to 5 slide images (or text slides) that rotate
        automatically from one to the next. Slide dimensions should be 960 x 280 pixels. Ensure that you add metadata
        in the Media library for a concise Title, appropriate Alt Text, and a Tag of: <b>slide</b>
        (this will allow your slide to show up on this page for you to add to the slideshow).
        See the <a href="https://help.libraries.coop/libpress/7-slideshow-overview/">LibPress Manual</a> for more information.
    </p>

    <div id="col-container" class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <p class="submit">
                    <button class="button add-new">Add New</button>
                </p>

                <div class="form-wrap slideshow-collection-controls">
                    <div class="form-field">
                        <input type="text" class="slideshow-collection-name" name="slideshow-collection-name"
                        value="" placeholder="Enter a name for a new slideshow">
                    </div>
                    <div class="form-field">
                        <?php echo $this->slideshowCollectionSelector(); ?>
                    </div>
                    <div class="form-field">
                        <label for="slideshow-is-active-collection" class="slideshow-activate-collection">
                            <input type="checkbox" id="slideshow-is-active-collection"
                                class="slideshow-is-active-collection" value="1">
                            This is the active slideshow
                        </label>
                    </div>
                </div>

                <table class="slideshow-sortable-rows">
                    <tr class="head-row">
                        <th></th>
                        <th>
                            Alt Text/Text Slide Title<br/>
                            <span class="slideshow-slide-link-header">Slide Link</span>
                        </th>
                    </tr>

                    <?php for ($i = 0; $i < 5; $i++) : ?>
                        <tr id="row<?= $i ?>" class="slideshow-collection-row draggable droppable"
                            id="dropzone<?= $i ?>">
                            <td class="thumbbox">&nbsp;</td>
                            <td class="slideshow-slide-title">
                                <div class="slide-title"><span class="placeholder">Alt Text/Text Slide Title</span></div>
                                <div class="slide-link"><span class="placeholder">Slide Link</span></div>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </table><!-- .slideshow-sortable-rows -->

                <?php require 'add-text-slide.php'; ?>

                <?php require 'slideshow-options.php'; ?>
            </div>
        </div>

        <div id="col-right">
            <div class="col-wrap">
                <p class="submit">
                    <button class="button button-primary slideshow-save-collection-btn">Save Collection</button>
                    <button class="button slideshow-delete-collection-btn">Delete the Loaded Slideshow</button>
                </p>

                <?php foreach (self::$media_sources as $region_id => $region_name) : ?>
                    <div class="wp-clearfix">
                        <h3><?= $region_name ?></h3>
                        <div class="slideshow-draggable-items returnable <?= $region_id ?>">
                            <?php echo implode("\n", self::fetchSlideImages($region_id)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="slideshow-signals-preload">
        <img src="<?= $this->sprite ?>" width="362" height="96">
    </div>
</div>
