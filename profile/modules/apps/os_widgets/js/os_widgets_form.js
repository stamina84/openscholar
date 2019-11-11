(function ($) {

  Drupal.behaviors.osWidgetsForm = {
    attach: function (ctx) {

      // Select All option for publication types.
      let $selectAll = $('.field--name-field-publication-types input[name ="field_publication_types[all]"]');
      let $fieldWrapper = $('.field--name-field-publication-types');
      $selectAll.on('click', function () {
        $fieldWrapper.find('input').each(function () {
          if ($selectAll.prop('checked')) {
            $(this).prop('checked', true);
          }
          else {
            $(this).prop('checked', false);
          }
        });
      });

    }
  }

})(jQuery);
