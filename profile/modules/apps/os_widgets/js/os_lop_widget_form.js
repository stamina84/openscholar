/**
 * Helper code for widget forms such LOP,LOF.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.osLopWidgetForm = {
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

      // Path mapping for LOP path autofill based on apps.
      let $contentType = $(ctx).find('.form-item-field-content-type select[name="field_content_type"]');
      let $uriField = $(ctx).find('.field--name-field-url-for-the-more-link  input[name="field_url_for_the_more_link[0][uri]"]');
      let nodeMapping = drupalSettings.pathMapping.node;
      let pubMapping = drupalSettings.pathMapping.bibcite_reference;

      $contentType.on('change', function () {
        let bundle = $contentType.val();
        if ($contentType.val() === 'publications') {
          bundle = '*';
          $uriField.val('/' + pubMapping[bundle]);
        }
        else {
          if (!nodeMapping[bundle] ) {
            $uriField.val('/');
          }
          else {
            $uriField.val('/' + nodeMapping[bundle]);
          }
        }
      });

      // Events should expire/appear field changes dependent on other fields.
      let $sortedBy = $(ctx).find('.form-item-field-sorted-by select[name="field_sorted_by"]');
      let $eventAppearExpireWrapper = $(ctx).find('.field--name-field-events-should-expire');
      let $eventAppearExpireSelect = $(ctx).find('.field--name-field-events-should-expire select[name="field_events_should_expire"]');
      let $eventAppearExpireLabel = $(ctx).find('.field--name-field-events-should-expire label');
      let $eventShow = $(ctx).find('.form-item-field-show select[name="field_show"]');

      if ($sortedBy.val() === 'sort_event_desc') {
        $eventAppearExpireLabel.text(Drupal.t('Event should appear'));
      }

      // Keep the field hidden and disabled when form loads based on dependent fields.
      eventsFieldChanges();

      // Attach on change behaviour to dependent fields.
      $sortedBy.once('sorted-by-field').on('change', function () {
        eventsFieldChanges();
      });

      $eventShow.once('event-show-field').on('change', function () {
        eventsFieldChanges();
      });

      // Common code for handling events should expire/appear field.
      function eventsFieldChanges() {
        let bundle = $contentType.val();
        let sort = $sortedBy.val();
        if (bundle === 'events') {
          if (sort === 'sort_event_asc') {
            if ($eventShow.val() === 'upcoming_events') {
              $eventAppearExpireLabel.text(Drupal.t('Event should expire'));
              $eventAppearExpireWrapper.show();
              $eventAppearExpireSelect.attr('disabled', false);
            }
            else if ($eventShow.val() !== 'upcoming_events') {
              $eventAppearExpireWrapper.hide();
              $eventAppearExpireSelect.attr('disabled', true);
            }
          }
          else if (sort === 'sort_event_desc') {
            if ($eventShow.val() === 'past_events') {
              $eventAppearExpireLabel.text(Drupal.t('Event should appear'));
              $eventAppearExpireWrapper.show();
              $eventAppearExpireSelect.attr('disabled', false);
            }
            else if ($eventShow.val() !== 'past_events') {
              $eventAppearExpireWrapper.hide();
              $eventAppearExpireSelect.attr('disabled', true);
            }
          }
          else {
            $eventAppearExpireWrapper.hide();
            $eventAppearExpireSelect.attr('disabled', true);
          }
        }
      }
    }
  };

})(jQuery, Drupal);
