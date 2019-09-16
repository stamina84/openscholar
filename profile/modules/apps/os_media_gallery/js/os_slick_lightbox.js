(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.osSlickLightbox = {
    attach: function (context) {
      $('.slick--optionset--slick-media-gallery .slick-track', context).slickLightbox({
        caption: 'caption'
      });
      $('.field--name-field-gallery-media.field--mode-grid .field--items', context).slickLightbox();
    }
  };

}(jQuery, Drupal));
