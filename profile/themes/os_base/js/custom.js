(function ($, Drupal) {
  Drupal.behaviors.custom = {
    attach: function (context, settings) {
      $('.dropmenu-child', context).click(function(e) {
        e.preventDefault();
        $(this).siblings('.dropdown-menu').toggleClass("mopen");
        $(this).toggleClass("mopen");
      });
    }
  };
  Drupal.behaviors.Accordion = {
    attach: function (context) {
      $('.block--type-accordion .collapse', context).once().on('shown.bs.collapse', function () {
        $(this).parent().find(".glyphicon-plus").removeClass("glyphicon-plus").addClass("glyphicon-minus");
      }).once().on('hidden.bs.collapse', function () {
        $(this).parent().find(".glyphicon-minus").removeClass("glyphicon-minus").addClass("glyphicon-plus");
      });
    }
  }

})(jQuery, Drupal);
