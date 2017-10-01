;(function($) {
  $(document).ready(function() {
    $('.owl-carousel').each(function() {
      var that = $(this);
      var settings = that.data('owl-options');

      if (settings.autoPlay == 0) {
        settings.autoPlay = false;
      }

      that.owlCarousel(settings);
    });
  });
})(jQuery);
