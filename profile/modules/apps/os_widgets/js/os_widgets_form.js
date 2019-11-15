/**
 * Toggle functionality for Select all field under publication types for LOP widget
 * form. Also adds Show More link dynamically based on content selection.
 */

(function ($, Drupal) {

  Drupal.behaviors.osWidgetsForm = {
    attach: function (ctx, drupalSettings) {

      // Select All option for publication types.
      let $selectAll = $(ctx).find('.field--name-field-publication-types input[name ="field_publication_types[all]"]');
      let $fieldWrapper = $(ctx).find('.field--name-field-publication-types');
      $selectAll.once('select-all-checkbox').on('click', function () {
        $fieldWrapper.find('input').each(function () {
          if ($selectAll.prop('checked')) {
            $(this).prop('checked', true);
          }
          else {
            $(this).prop('checked', false);
          }
        });
      });

      let $contentType = $(ctx).find('.form-item-field-content-type select[name="field_content_type"]');
      let $uriField = $(ctx).find('.field--name-field-url-for-the-more-link  input[name="field_url_for_the_more_link[0][uri]"]');
      let nodeMapping = drupalSettings.pathMapping.node;
      let pubMapping = drupalSettings.pathMapping.bibcite_reference;

      $contentType.once('content-type-field').on('change', function () {
        let bundle = $contentType.val();
        if ($contentType.val() === 'publications') {
          bundle = '*';
          $uriField.val('/' + pubMapping[bundle]);
        }
        else {
          if (typeof nodeMapping[bundle] === 'undefined' ) {
            $uriField.val('');
          }
          else {
            $uriField.val('/' + nodeMapping[bundle]);
          }
        }
      });
    }
  }

})(jQuery, Drupal);
