(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.osSlickLightbox = {
    attach: function (context) {
      $('.slick--optionset--slick-media-gallery .slick-track', context).slickLightbox({
        caption: 'caption'
      });
      $('.field--name-field-gallery-media.field--mode-grid .field--items', context).slickLightbox({
        caption: function (element, info) {
          return $(element).find('img').attr('title') ? $(element).find('img').attr('title') : '';
        }
      });
    }
  };

}(jQuery, Drupal));
