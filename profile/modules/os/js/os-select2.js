/**
 * @file
 * OS Select2 integration.
 */
(function ($, drupalSettings) {
  'use strict';

  Drupal.behaviors.os_select2 = {
    attach: function (context) {
      // TODO: not working with popup angular modal.
      $('.os-select2-widget', context).once('os-select2-init').each(function () {
        $(this).select2();
      });
    }
  };

})(jQuery, drupalSettings);
