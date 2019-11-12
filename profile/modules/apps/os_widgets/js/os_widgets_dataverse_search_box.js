(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.osDataverseSearchBox = {
    attach: function (context, settings) {
      $('.dataverse_search_button', context).click(function (e) {
        // 1. We prevent a regular form submission on click
        e.preventDefault();

        // 2. Instead we read dataverse related config values
        let dataverseSearchBaseurl = drupalSettings.osWidgets.dataverseSearchBaseurl;
        let dataverseIdentifier = drupalSettings.osWidgets.dataverseIdentifier;

        // 3. Then we open dataverse search page in a new window/tab, with escaped query string.
        let rawSearchQueryString = $(this).parents('form').find('.dataverse_search_input').val();
        let escapedSearchQueryString = Drupal.checkPlain(rawSearchQueryString);
        let dataverseSearchURL = dataverseSearchBaseurl + '?alias=' + dataverseIdentifier + '&q=' + escapedSearchQueryString;
        window.open(dataverseSearchURL, '_blank');
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
