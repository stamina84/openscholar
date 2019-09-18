(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.osSlickLightbox = {
    attach: function (context) {
      $('.slick--optionset--slick-media-gallery .slick-track').once('myCustomBehavior').slickLightbox({
        caption: 'caption'
      });
      $('.field--name-field-gallery-media.field--mode-grid .field--items', context).slickLightbox({
        caption: function (element, info) {
          let imageAttribute = $(element).find('img').attr('title');
          return imageAttribute ? imageAttribute : '';
        }
      });
    }
  };

}(jQuery, Drupal));
