(function ($, window) {
  /**
   * Ready
   */
  $(function () {
    if (window.coopSlideshowOptions) {
      const $flickity = $(".hero-carousel");

      $flickity.on("ready.flickity change.flickity", function () {
        fitty(".fit");
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
