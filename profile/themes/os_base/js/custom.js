(function ($, Drupal) {
  Drupal.behaviors.custom = {
    attach: function (context, settings) {
      $('.dropmenu-child', context).click(function (e) {
        e.preventDefault();
        $(this).siblings('.dropdown-menu').toggleClass("mopen");
        $(this).toggleClass("mopen");
      });
    }
  };
})(jQuery, Drupal);
