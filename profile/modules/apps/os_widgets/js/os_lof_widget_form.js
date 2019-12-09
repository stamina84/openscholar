/**
 * Helper code for widget forms such LOP,LOF.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.osLofWidgetForm = {
    attach: function (ctx) {
      // Column field changes dependent on other fields LOF widget.
      let $fileType = $(ctx).find('.form-item-field-file-type select[name="field_file_type"]');
      let $layout = $(ctx).find('.form-item-field-layout select[name="field_layout"]');
      let $columnsWrapper = $(ctx).find('.field--name-field-columns');
      let $columns = $(ctx).find('.form-item-field-columns select[name="field_columns"]');

      // Keep the field hidden and disabled when form loads based on dependent field.
      if (($fileType.val() !== 'image' && $fileType.val() !== 'oembed') || $layout.val() !== 'grid') {
        $columnsWrapper.hide();
        $columns.attr('disabled', 'disabled');
      }

      // Attach on change behaviour to dependent fields.
      $fileType.once('file-type-field').on('change', function () {
        columnFieldChanges();
      });

      $layout.once('layout-field').on('change', function () {
        columnFieldChanges();
      });

      function columnFieldChanges() {
        if (($fileType.val() === 'image' || $fileType.val() === 'oembed') && $layout.val() === 'grid') {
          $columnsWrapper.show();
          $columns.attr('disabled', false);
        }
        else {
          $columnsWrapper.hide();
          $columns.attr('disabled', 'disabled');
        }
      }
    }
  };


})(jQuery, Drupal);
