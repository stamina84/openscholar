(function ($, Drupal) {
  Drupal.behaviors.osDataverseSearchBox = {
    attach: function (context, settings) {
      // TODO: describe what is being done.
      $('.dataverse_search_button', context).click(function (e) {
        e.preventDefault();
        let searchQueryString = Drupal.checkPlain($(this).parents('form').find('.dataverse_search_input').val());
        let dataverseSearchURL = 'https://dataverse.harvard.edu/dataverse.xhtml?alias=king&q=';
        // TODO: build URL from widget configurations.
        window.open(dataverseSearchURL + searchQueryString, '_blank');
      });
    }
  }

})(jQuery, Drupal);
