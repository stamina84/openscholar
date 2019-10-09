/**
 * @file
 * Performs alterations in contextual links for full-view pages.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Makes sure that after delete user is redirected to listing.
   *
   * @param $el
   *   The delete contextual link element.
   * @param redirectLocation
   *   The location where user will be redirected.
   */
  function alterDeleteDestination($el, redirectLocation) {
    let $link = $el.find('a');
    let url = new URL($link.attr('href'), window.location.origin);
    let newDestination = drupalSettings.spaces.url + redirectLocation;

    url.searchParams.set('destination', newDestination);

    $link.attr('href', decodeURIComponent(url.toString()));
  }

  /**
   * Makes sure that afterwards, user is redirected back to the current page.
   *
   * @param $el
   *   The edit contextual link element.
   */
  function alterDestinationToCurrent($el) {
    let $link = $el.find('a');
    let url = new URL($link.attr('href'), window.location.origin);
    let currentPath = window.location.pathname;

    url.searchParams.set('destination', currentPath);

    $link.attr('href', decodeURIComponent(url.toString()));
  }

  /**
   * Initializes the alterations.
   */
  function init() {
    if (drupalSettings.spaces !== undefined) {
      registerDrupalContextualLinkAddedEvent();
    }
  }

  /**
   * Registers to event `drupalContextualLinkAdded`.
   */
  function registerDrupalContextualLinkAddedEvent() {
    $(document).once().bind('drupalContextualLinkAdded', function (event, data) {
      let $deleteOption = data.$el.find('li.entitynodedelete-form, li.entitybibcite-referencedelete-form');

      if ($deleteOption.length) {
        let entityMapping = drupalSettings.entitySetting.mapping[drupalSettings.entitySetting.type];
        let bundle = drupalSettings.entitySetting.bundle;

        if (drupalSettings.entitySetting.type === 'bibcite_reference') {
          bundle = '*';
        }
        let redirectLocation = window.location.pathname;

        alterDeleteDestination($deleteOption, redirectLocation);
      }

      let $blockDeleteOption = data.$el.find('li.block-contentblock-delete');
      if ($blockDeleteOption.length) {
        // Current page.
        alterDestinationToCurrent($blockDeleteOption);
      }

      let $editOption = data.$el.find('li.entitynodeedit-form, li.entitybibcite-referenceedit-form');

      if ($editOption.length) {
        alterDestinationToCurrent($editOption);
      }
    });
  }

  Drupal.behaviors.vsiteContextualFullView = {
    attach: function () {
      init();
    },
  };
})(jQuery, Drupal, drupalSettings);
