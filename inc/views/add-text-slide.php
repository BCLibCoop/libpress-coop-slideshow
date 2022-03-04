<hr>

<div class="form-wrap">
    <h3>Add Text-only Slide</h3>
    <div class="form-field">
        <input type="text" id="slideshow-text-slide-heading" class="slideshow-text-slide-heading"
            name="slideshow-text-slide-heading" value="" placeholder="Headline">
    </div>

    <div class="form-field">
        <textarea id="slideshow-text-slide-content" class="slideshow-text-slide-content"
            name="slideshow-text-slide-content" placeholder="Message text"></textarea>
    </div>

    <div class="form-field">
        <?php echo $this->targetPagesSelector(); ?>
    </div>

    <p>Items listed in blue are blog posts. Items in green are pages.</p>

    <p class="submit">
        <button class="button button-primary slideshow-text-slide-save-btn">Add Text Slide</button>
        <button class="button slideshow-text-slide-cancel-btn">Cancel</button>
    </p>
</div>
