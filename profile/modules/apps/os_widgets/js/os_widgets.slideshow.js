(function ($, Drupal) {

  var breakpoint_uri;

  Drupal.behaviors.osWidgetsSlideshow = {
    attach: function (context) {
      $('.block--type-slideshow .slick')
        .once('os_widgets_slick_breakpoint')
        .on('breakpoint', Drupal.osWidgetsSlideshow.onSlickBreakpointChange);
      $('.block--type-slideshow .slick')
        .once('os_widgets_slick_init')
        .on('init', Drupal.osWidgetsSlideshow.onSlickInit);
    }
  };

  Drupal.osWidgetsSlideshow = Drupal.osWidgetsSlideshow || {};

  Drupal.osWidgetsSlideshow.onSlickBreakpointChange = function(event, slick, breakpoint) {
    Drupal.osWidgetsSlideshow.replaceResponsiveImage(event, slick, breakpoint, $(this));
  };

  Drupal.osWidgetsSlideshow.onSlickInit = function(event, slick) {
    Drupal.osWidgetsSlideshow.replaceResponsiveImage(event, slick, slick.activeBreakpoint, $(this));
  };

  Drupal.osWidgetsSlideshow.replaceResponsiveImage = function(event, slick, breakpoint, element) {
    if (breakpoint === null) {
      return;
    }
    $.each(element.find('.paragraph'), function () {
      breakpoint_uri = $(this).data('breakpoint_uri');
      if (!(breakpoint in breakpoint_uri)) {
        console.log('Invalid key in breakpoint_uri array: ' + breakpoint);
        return;
      }
      var img = $(this).find('.field--name-field-media-image img');
      img.attr('src', breakpoint_uri[breakpoint].uri);
    });
  }

})(jQuery, Drupal);
