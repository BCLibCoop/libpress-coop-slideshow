<hr>

<div class="slideshow-controls">
    <?php foreach (self::$show_options as $control_section_name => $control_section) : ?>
        <?php if (!empty($control_section['hide'])) continue; //phpcs:ignore Generic.ControlStructures.InlineControlStructure.NotAllowed ?>
        <div class="wp-clearfix">
            <h2><?= $control_section['label'] ?></h2>
            <?php if (!empty($control_section['description'])) : ?>
                <p class="description"><?= $control_section['description'] ?></p>
            <?php endif; ?>
            <div class="form-wrap">
                <?php foreach ($control_section['options'] as $index => $control_option) : ?>
                    <div class="form-field slideshow-control <?= !empty($control_option['image']) ? 'has-image' : '' ?>
                    <?= count($control_section['options']) === 1 ? 'single' : '' ?>
                    ">
                        <?php if (!empty($control_option['image'])) : ?>
                            <label for="slideshow-control-<?= $control_section_name ?>-<?= $index ?>">
                                <img
                                src="<?= plugins_url('/assets/imgs/' . $control_option['image'], COOP_SLIDESHOW_PLUGIN) ?>"
                                class="slideshow-control-img">
                            </label>
                        <?php endif; ?>
                        <label class="input-label">
                            <?php if (is_bool($control_option['value'])) : ?>
                                <input type="checkbox" id="slideshow-control-<?= $control_section_name ?>-<?= $index ?>"
                                name="slideshow-<?= $control_section_name ?>"
                                value="true">
                            <?php else : ?>
                                <input type="radio" id="slideshow-control-<?= $control_section_name ?>-<?= $index ?>"
                                    name="slideshow-<?= $control_section_name ?>"
                                    value="<?= $control_option['value'] ?>">
                            <?php endif; ?>
                            <?= $control_option['label'] ?>
                        </label>
                        <?php if (!empty($control_option['description'])) : ?>
                            <label for="slideshow-control-<?= $control_section_name ?>-<?= $index ?>">
                                <div class="slideshow-control-description"><?= $control_option['description'] ?></div>
                            </label>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
