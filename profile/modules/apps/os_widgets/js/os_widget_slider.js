(function ($) {
  Drupal.behaviors.osSliderWidget = {
    attach: function (context, drupalSettings) {
      $('.widget-slider').each(function () {
        id = $(this).attr('id');
        bid = id.replace('slider-',"");
        sliderSettings = drupalSettings.sliderWidget[bid];
        $('#'+id, context).once('osSliderWidget').slick({
          autoplay: true,
          dots: true,
          arrows: sliderSettings.field_display_arrows,
          autoplaySpeed: sliderSettings.field_transition_speed ? (sliderSettings.field_transition_speed * 1000) : 3000,
          speed: sliderSettings.field_duration ? (sliderSettings.field_duration * 1000) : 300,
          appendArrows: '#dots-'+bid,
          appendDots: '#dots-'+bid,
          responsive: [
            {
              breakpoint: 768,
              settings: {
                arrows: false,
              }
            }
          ]
        });
      });
    }
  }
})(jQuery, drupalSettings);
