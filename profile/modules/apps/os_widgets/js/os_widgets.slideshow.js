(function ($, Drupal) {

  var breakpoint_uri;

  Drupal.behaviors.osWidgetsSlideshow = {
    attach: function (context) {
      /*$('.block--type-slideshow .slick')
        .once('os_widgets_slick_breakpoint')
        .on('breakpoint', Drupal.osWidgetsSlideshow.onSlickBreakpointChange);*/
      $('.block--type-slideshow .slick')
        .once('os_widgets_slick_setPosition')
        .on('setPosition', Drupal.osWidgetsSlideshow.onSlickSetPosition);
    }
  };

  Drupal.osWidgetsSlideshow = Drupal.osWidgetsSlideshow || {};

  /*Drupal.osWidgetsSlideshow.onSlickBreakpointChange = function(event, slick, breakpoint) {
    console.log(event);
    console.log(slick);
    Drupal.osWidgetsSlideshow.replaceResponsiveImage(event, slick, breakpoint, $(this));
  };*/

  Drupal.osWidgetsSlideshow.onSlickSetPosition = function(event, slick) {
    Drupal.osWidgetsSlideshow.replaceResponsiveImage(event, slick, slick.activeBreakpoint, $(this));
  };

  Drupal.osWidgetsSlideshow.replaceResponsiveImage = function(event, slick, breakpoint, element) {
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
