/**
 * @file
 * Performs alterations in contextual links for book pages.
 */

(function ($, Drupal) {

  function removeDestinationParameter() {
    $(document).once().bind('drupalContextualLinkAdded', function (event, data) {
      let $subPage = data.$el.find('li.add-subpage');
      if($subPage.length) {
        let $link = $subPage.find('a');
        let url = new URL($link.attr('href'), window.location.origin);
        url.searchParams.delete('destination');
        $link.attr('href', decodeURIComponent(url.toString()));
      }

      let $outline = data.$el.find('li.outline');
      if($outline.length) {
        let $link = $outline.find('a');
        let url = new URL($link.attr('href'), window.location.origin);
        let pathName = url.pathname;
        var word = '/outline';
        var newWord = '/book-outline';
        var n = pathName.lastIndexOf(word);
        url.pathname = pathName.slice(0, n) + pathName.slice(n).replace(word, newWord);
        url.searchParams.delete('destination');
        $link.attr('href', decodeURIComponent(url.toString()));
      }
    });
  }

  Drupal.behaviors.osPagesContextualChanges = {
    attach: function () {
      removeDestinationParameter();
    },
  };
})(jQuery, Drupal);
