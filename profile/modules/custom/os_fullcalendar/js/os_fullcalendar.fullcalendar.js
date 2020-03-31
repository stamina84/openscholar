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
        let $fcTodayButton = $('.full-calendar-view .fullcalendar button.fc-today-button');
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
            $('.full-calendar-view .fullcalendar').fullCalendar('today');
            makeTodaybtnActive();
            $('.full-calendar-view .fc-prev-button').hide();
            $('.full-calendar-view .fc-next-button').hide();
          } else {
            $('.full-calendar-view .fc-prev-button').show();
            $('.full-calendar-view .fc-next-button').show();
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
