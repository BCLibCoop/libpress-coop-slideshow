(function ($, window) {
  /**
   * Ready
   */
  $(function () {
    if (window.coopSlideshowOptions) {
      const $flickity = $(".hero-carousel");

      $flickity.on("ready.flickity change.flickity", function () {
        fitty(".fit", { maxSize: 48, minSize: 22 });
      });

      $flickity.flickity(window.coopSlideshowOptions);

      $(".hero-carousel-pager").flickity({
        asNavFor: ".hero-carousel",
        contain: true,
        pageDots: false,
        prevNextButtons: false,
        draggable: false,
      });
    }
  });
})(jQuery, window);
