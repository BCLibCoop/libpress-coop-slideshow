<?php

$control_sections = [
    'layout' => [
        'label' => 'Slideshow Layout',
        'options' => [
            [
                'value' => 'no-thumb',
                'label' => 'No Thumbnails',
                'annotation' => 'Previous/Next arrows',
                'image' => 'NoThumbnails.png',
            ],
            [
                'value' => 'horizontal',
                'label' => 'Horizontal Thumbnails',
                'annotation' => 'Clickable thumbnails displayed horizontally below the slideshow',
                'image' => 'HorizontalThumbnails.png',
            ],
        ],
    ],
    'transition' => [
        'label' => 'Transitions',
        'options' => [
            [
                'value' => 'horizontal',
                'label' => 'Slide Horizontal',
                'annotation' => 'Slides enter from the right and exit to the left',
                'image' => 'HorizontalSlide.png',
            ],
            [
                'value' => 'fade',
                'label' => 'Cross-fade',
                'annotation' => 'One slide dissolves into the next',
                'image' => 'Fade.png',
            ],
        ],
    ],
    // TODO: Time, both stay and
];
?>
<hr>

<div class="wp-clearfix">
    <h2>Display Captions</h2>
    <div class="form-wrap">
        <div class="form-field">
            <label>
                <input type="checkbox" id="slideshow-show-captions" value="true">
                Enable caption display for slideshow
            </label>
        </div>
    </div>
</div>

<?php foreach ($control_sections as $control_section_name => $control_section) : ?>
    <div class="wp-clearfix">
        <h2><?= $control_section['label'] ?></h2>
        <div class="form-wrap">
            <?php foreach ($control_section['options'] as $index => $control_option) : ?>
                <div class="form-field slideshow-control">
                    <label for="slideshow-control-<?= $control_section_name ?>-<?= $index ?>">
                        <img src="<?= plugins_url('/assets/imgs/' . $control_option['image'], COOP_SLIDESHOW_PLUGIN) ?>" class="slideshow-control-img">
                    </label>
                    <label class="input-label">
                        <input type="radio" name="slideshow-<?= $control_section_name ?>" id="slideshow-control-<?= $control_section_name ?>-<?= $index ?>" value="<?= $control_option['value'] ?>">
                        <?= $control_option['label'] ?>
                    </label>
                    <label for="slideshow-control-<?= $control_section_name ?>-<?= $index ?>">
                        <div class="slideshow-control-annotation"><?= $control_option['annotation'] ?></div>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
