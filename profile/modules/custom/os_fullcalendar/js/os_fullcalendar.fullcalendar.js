/**
 * @file
 * Fullcalendar customizations for OpenScholar.
 */

(function ($, Drupal) {
  "use strict";

  Drupal.fullcalendar.plugins.os_fullcalendar = {
    options: function (fullcalendar, settings) {

      if (!settings.os_fullcalendar) {
        return;
      }

      function makeTodaybtnActive() {
        let $fcTodayButton = $('.fullcalendar button.fc-today-button');
        $fcTodayButton.removeAttr('disabled');
        $fcTodayButton.removeClass('ui-state-disabled');
      }

      return $.extend({
        eventRender: function (event, element) {
          makeTodaybtnActive();
        },
        eventAfterAllRender: function (view) {
          makeTodaybtnActive();
          if(view.name == 'today') {
            $('.fullcalendar').fullCalendar('today');
            makeTodaybtnActive();
            $('.fc-prev-button').hide();
            $('.fc-next-button').hide();
          } else {
            $('.fc-prev-button').show();
            $('.fc-next-button').show();
          }
        },
        views: {
          today: {
            type: 'list',
            buttonText: Drupal.t('Today'),
            listDayFormat: 'YYYY MMM DD',
          },
        },
        'buttonText': {
          month: Drupal.t('Month'),
          listWeek: Drupal.t('Week'),
          listDay: Drupal.t('Day'),
        },
      }, settings.os_fullcalendar);
    }
  };

  /**
   * Alters modal title.
   *
   * The title is displayed as plain text. It is enforced to be rendered as HTML
   * here.
   */
  function showModalEventRegisterHandler() {
    $('#drupal-modal').once().on('show.bs.modal', function () {
      let $modalTitleElement = $(this).find('.modal-title');
      let eventUrl = $(this).find('.modal-body article').attr('about');
      let modalTitleText = $modalTitleElement.text();

      $modalTitleElement.html('<a href="' + eventUrl + '">' + modalTitleText + '</a>');
    });
  }

  Drupal.behaviors.events = {
    attach: function () {
      showModalEventRegisterHandler();
    }
  };

})(jQuery, Drupal);
