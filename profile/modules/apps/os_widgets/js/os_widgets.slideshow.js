(function ($) {

  Drupal.behaviors.osWidgetsSlideshow = {
    attach: function (ctx) {

      $('.block.block--type-slideshow .slick').on('breakpoint', function(event, slick, breakpoint) {
        //console.log($(this).find('.slide-image').append(breakpoint));
        console.log('breakpoint ' + breakpoint);
      });

    }
  }

})(jQuery);
