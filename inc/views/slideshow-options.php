<hr>

<h2>Display Captions</h2>
<div class="form-wrap">
    <div class="form-field">
        <label for="slideshow-show-captions">
            <input type="checkbox" id="slideshow-show-captions" value="true">
            Enable caption display for slideshow
        </label>
    </div>
</div>

<h2>Slideshow Layout</h2>
<table>
    <tr>
        <td>
            <table class="slideshow-control">
                <tr>
                    <td></td>
                    <td>
                        <img src="<?= plugins_url('/imgs/NoThumbnails.png', dirname(__FILE__)) ?>" data-id="slideshow-control-1" class="slideshow-control-img">
                    </td>
                </tr>
                <tr>
                    <td class="radio-box">
                        <input type="radio" name="slideshow-layout" id="slideshow-control-1" value="no-thumb">
                    </td>
                    <td>
                        <label for="slideshow-control-1">No thumbnails</label>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="slideshow-control-annotation">
                        Previous / Next arrows
                    </td>
                </tr>
            </table><!-- .slideshow-control -->

        </td>
        <td>
            <table class="slideshow-control">
                <tr>
                    <td></td>
                    <td>
                        <img src="<?= plugins_url('/imgs/VerticalThumbnails.png', basename(__FILE__)) ?>" data-id="slideshow-control-2" class="slideshow-control-img">
                    </td>
                </tr>
                <tr>
                    <td class="radio-box">
                        <input type="radio" name="slideshow-layout" id="slideshow-control-2" value="vertical">
                    </td>
                    <td>
                        <label for="slideshow-control-2">Vertical thumbnails</label>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="slideshow-control-annotation">
                        Clickable thumbnails displayed vertically on the left-hand side
                    </td>
                </tr>
            </table><!-- .slideshow-control -->
        </td>
        <td>
            <table class="slideshow-control">
                <tr>
                    <td></td>
                    <td>
                        <img src="<?= plugins_url('/imgs/HorizontalThumbnails.png', dirname(__FILE__)) ?>" data-id="slideshow-control-3" class="slideshow-control-img">
                    </td>
                </tr>
                <tr>
                    <td class="radio-box">
                        <input type="radio" name="slideshow-layout" id="slideshow-control-3" value="horizontal">
                    </td>
                    <td>
                        <label for="slideshow-control-3">Horizontal thumbnails</label>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="slideshow-control-annotation">
                        Clickable thumbnails displayed horizontally below the slideshow
                    </td>
                </tr>
            </table><!-- .slideshow-control -->
        </td>
    </tr>
</table>

<h2>Transitions</h2>
<table>
    <tr>
        <td>
            <table class="slideshow-control">
                <tr>
                    <td></td>
                    <td>
                        <img src="<?= plugins_url('/imgs/HorizontalSlide.png', dirname(__FILE__)) ?>" data-id="slideshow-control-4" class="slideshow-control-img">
                    </td>
                </tr>
                <tr>
                    <td class="radio-box">
                        <input type="radio" name="slideshow-transition" id="slideshow-control-4" value="horizontal">
                    </td>
                    <td>
                        <label for="slideshow-control-4">Slide Horizontal</label>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="slideshow-control-annotation">
                        Slides enter from the right and exit to the left
                    </td>
                </tr>
            </table><!-- .slideshow-control -->
        </td>
        <td>
            <table class="slideshow-control">
                <tr>
                    <td></td>
                    <td>
                        <img src="<?= plugins_url('/imgs/VerticalSlide.png', dirname(__FILE__)) ?>" data-id="slideshow-control-5" class="slideshow-control-img">
                    </td>
                </tr>
                <tr>
                    <td class="radio-box">
                        <input type="radio" name="slideshow-transition" id="slideshow-control-5" value="vertical">
                    </td>
                    <td>
                        <label for="slideshow-control-5">Slide Vertical</label>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="slideshow-control-annotation">
                        Slides enter below and exit above
                    </td>
                </tr>
            </table><!-- .slideshow-control -->
        </td>
        <td>
            <table class="slideshow-control">
                <tr>
                    <td></td>
                    <td>
                        <img src="<?= plugins_url('/imgs/Fade.png', dirname(__FILE__)) ?>" data-id="slideshow-control-6" class="slideshow-control-img">
                    </td>
                </tr>
                <tr>
                    <td class="radio-box">
                        <input type="radio" name="slideshow-transition" id="slideshow-control-6" value="fade">
                    </td>
                    <td>
                        <label for="slideshow-control-6">Cross-fade</label>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="slideshow-control-annotation">
                        One slide dissolves into the next
                    </td>
                </tr>
            </table><!-- .slideshow-control -->
        </td>
    </tr>
</table>
