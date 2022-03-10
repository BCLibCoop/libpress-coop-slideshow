<?php if (!empty($slides)) : ?>
    <div class="hero row <?= $this->show->layout ?>" role="banner">
        <div id="slider" class="slider">
            <?php foreach ($slides as $slide) : ?>
                <div class="slide <?= $slide['type'] ?>">
                    <?php if (!empty($slide['slide_permalink'])) : ?>
                        <a href="<?= $slide['slide_permalink'] ?>">
                    <?php endif; ?>

                    <?php if ($slide['type'] === 'image') : ?>
                        <img src="<?= $slide['meta']['sizes']['full']['src'] ?>"
                            alt="<?= esc_attr($slide['text_title']) ?>"
                            title="<?= esc_attr($slide['text_title']) ?>"
                        >
                    <?php elseif ($slide['type'] === 'text') : ?>
                        <h2><?= htmlspecialchars($slide['text_title']) ?></h2>
                        <p><?= htmlspecialchars($slide['text_content']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($slide['slide_permalink'])) : ?>
                        </a>
                    <?php endif; ?>
                </div><!-- .slide.<?= $slide['type'] ?> -->
            <?php endforeach; ?>
        </div><!-- #slider.row.slider -->

        <?php if ($this->show->layout !== 'no-thumb') : ?>
            <div class="row <?= $pager_class ?> <?= $this->show->layout ?>">
                <?php foreach ($slides as $slide) : ?>
                    <div class="pager-box slide-index-<?= $slide['ordering'] ?>">
                        <a href="" data-slide-index="<?= $slide['ordering'] ?>">
                            <div class="thumb <?= $slide['type'] ?>">
                                <?php if ($slide['type'] === 'image') : ?>
                                    <img class="pager-thumb"
                                        alt="<?= esc_attr($slide['text_title']) ?>"
                                        src="<?= $slide['meta']['sizes']['thumbnail']['src'] ?>"
                                    >
                                <?php elseif ($slide['type'] === 'text') : ?>
                                    <div class="pager-thumb text-thumb">T</div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div><!-- .pager-box -->
                <?php endforeach; ?>
            </div><!-- end of pager -->
        <?php endif; ?>
    </div><!-- .hero.row -->
<?php else : ?>
    <!-- No Slides/Slideshow Found -->
<?php endif;
