(function ($) {

  Drupal.behaviors.osWidgetsForm = {
    attach: function (ctx, drupalSettings) {

      // Select All option for publication types.
      let $selectAll = $('.field--name-field-publication-types input[name ="field_publication_types[all]"]');
      let $fieldWrapper = $('.field--name-field-publication-types');
      $selectAll.once().on('click', function () {
        $fieldWrapper.find('input').each(function () {
          if ($selectAll.prop('checked')) {
            $(this).prop('checked', true);
          }
          else {
            $(this).prop('checked', false);
          }
        });
      });

      let $contentType = $('.block-list-of-posts-form select[name="field_content_type"]');
      let $uriField = $('.block-list-of-posts-form input[name="field_url_for_the_more_link[0][uri]"]');
      let nodeMapping = drupalSettings.pathMapping.node;
      let pubMapping = drupalSettings.pathMapping.bibcite_reference;

      $contentType.once().on('change', function () {
        $uriField.val(nodeMapping[$contentType.val()]);
      });

    }
  }

})(jQuery);
