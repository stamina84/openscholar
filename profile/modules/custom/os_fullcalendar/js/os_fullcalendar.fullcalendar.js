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
          if (element.hasClass('fc-event-future') && !element.hasClass('fc-day-grid-event')) {
            let userOffsetInSeconds = drupalSettings['os_events']['offsetInM']/3600;
            let userOffsetInHM = drupalSettings['os_events']['offsetInHm'];
            let dateString = event['start']['_i'] + userOffsetInHM;
            let date = new Date(dateString).getTime()/1000;
            let eventDate = (date - (userOffsetInSeconds*3600));
            let nid = event.eid;
            element.html(drupalSettings['os_events']['node'][nid]);
            element.find('#events_signup_modal_form').attr('href', '/events/signup/' + nid + '/' + eventDate);
          }
          else if (element.hasClass('fc-event-past') && !element.hasClass('fc-day-grid-event')) {
            let nid = event.eid;
            element.html(drupalSettings[nid]);
          }
        },
        eventAfterAllRender: function (view) {
          makeTodaybtnActive();
          if(view.name == 'today') {
            $('.fullcalendar').fullCalendar('today');
            makeTodaybtnActive();
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
