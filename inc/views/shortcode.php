<?php if (!empty($this->show) && !empty($this->show->slides)) : ?>
    <section class="hero" aria-roledescription="carousel" aria-label="Library Information">
        <div class="hero-carousel carousel-<?= esc_attr($this->show->layout) ?> <?= $has_text_slides ? 'has-text-slides' : '' ?>">
            <?php foreach ($this->show->slides as $index => $slide) : ?>
                <div class="slide <?= esc_attr($slide['type']) ?>">
                    <div class="slide-inner" data-track-content data-content-name="Slideshow">
                        <?php if (!empty($slide['slide_permalink'])) : ?>
                            <a href="<?= $slide['slide_permalink'] ?>" <?= !empty($slide['slide_target']) ? "target=\"{$slide['slide_target']}\"" : '' ?>>
                        <?php endif; ?>

                        <?php if ($slide['type'] === 'image') : ?>
                            <img
                                <?= $index === 0 ? '' : 'loading="lazy"' ?>
                                decoding="async"
                                src="<?= $slide['meta']['sizes']['full']['src'] ?>"
                                alt="<?= esc_attr($slide['text_title']) ?>"
                                width="<?= $slide['meta']['sizes']['full']['width'] ?>"
                                height="<?= $slide['meta']['sizes']['full']['height'] ?>"
                            >
                        <?php elseif ($slide['type'] === 'text') : ?>
                            <h2 class="fit">
                                <?= htmlspecialchars($slide['text_title']) ?>
                            </h2>
                            <p><?= htmlspecialchars($slide['text_content']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($slide['slide_permalink'])) : ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div><!-- .slide.<?= esc_attr($slide['type']) ?> -->
            <?php endforeach; ?>
        </div><!-- #slider.slider -->

        <?php if ($this->show->layout !== 'no-thumb') : ?>
            <div class="hero-carousel-pager carousel-pager-<?= esc_attr($this->show->layout) ?>">
                <?php foreach ($this->show->slides as $slide) : ?>
                    <div class="pager-box slide-index-<?= esc_attr($slide['ordering']) ?>">
                        <div class="thumb <?= esc_attr($slide['type']) ?>">
                            <img class="pager-thumb"
                                alt="<?= esc_attr($slide['text_title']) ?>"
                                src="<?= $slide['meta']['sizes']['thumbnail']['src'] ??
                                    plugins_url('/assets/imgs/info-thumb.png', COOP_SLIDESHOW_PLUGIN); ?>"
                                width="50"
                                height="50"
                            >
                        </div>
                    </div><!-- .pager-box -->
                <?php endforeach; ?>
            </div><!-- end of pager -->
        <?php endif; ?>
    </section><!-- .hero -->
<?php else : ?>
    <!-- No Slides/Slideshow Found -->
<?php endif;
