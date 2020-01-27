/**
 * @file
 * Performs alterations in contextual links for widgets.
 */

(function ($, Drupal) {

  function alterDestinationParameter() {
    let origin = window.location.origin;
    let loc = window.location.href;
    let dest = loc.replace(origin, '');
    $(document).once('alterDeleteLinks').bind('drupalContextualLinkAdded', function (event, data) {
      let $deleteLink = data.$el.find('li.block-contentblock-delete');
      if($deleteLink.length) {
        let $link = $deleteLink.find('a');
        let url = new URL($link.attr('href'), origin);
        url.searchParams.set('destination', dest);
        $link.attr('href', decodeURIComponent(url.toString()));
      }
    });
  }

  Drupal.behaviors.osWidgetsContextualChanges = {
    attach: function () {
      alterDestinationParameter();
    },
  };
})(jQuery, Drupal);
