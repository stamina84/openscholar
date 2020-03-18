/**
 * @file
 * Performs alterations in contextual links for widgets.
 */

(function ($, Drupal) {

  /**
   * Method to alter contextual links destination parameters.
   */
  function alterDestinationParameter() {
    let origin = window.location.origin;
    let loc = window.location.href;
    let dest = loc.replace(origin, '');
    $(document).once('alterDeleteLinks').bind('drupalContextualLinkAdded', function (event, data) {
      let $deleteLink = data.$el.find('li.block-contentblock-delete');
      let $sectionOutlineLink = data.$el.find('li.section-outline');
      if($deleteLink.length) {
        alterDestinationInLink($deleteLink, dest, origin);
      }
      // Alter section outline destination parameter.
      if($sectionOutlineLink.length) {
        alterDestinationInLink($sectionOutlineLink, dest, origin);
      }
    });
  }

  /**
   * Method to set the destination param.
   * @param $link
   * @param destination
   * @param origin
   */
  function alterDestinationInLink($link, destination, origin) {
    let $element = $link.find('a');
    let url = new URL($element.attr('href'), origin);
    url.searchParams.set('destination', destination);
    $element.attr('href', decodeURIComponent(url.toString()));
  }

  Drupal.behaviors.osWidgetsContextualChanges = {
    attach: function () {
      alterDestinationParameter();
    },
  };
})(jQuery, Drupal);
