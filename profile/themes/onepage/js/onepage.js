(function ($, Drupal) {
  Drupal.behaviors.onePage = {
    attach: function (context) {
      /* display posts horizontally when set to 2, 3, 4 or 6 posts. */
      let lop = $('.path-front .block--type-list-of-posts');
      let tabs = $('.path-front .block--type-tabs');
      $([lop, tabs]).once().each(function () {
        let count = $('ul[id^="list-of-posts"] > li', this).length;
        $(this).addClass('lopz-' + count);
      });
      /* fixed menu bar on scroll */
      let $menuBar = $('#navbar-collapse', context);
      let num = $menuBar.offset().top;
      $(window, context).once().scroll(function () {
        if ($(window).scrollTop() > num) {
          $menuBar.addClass('fixed');
        } else {
          $menuBar.removeClass('fixed');
        }
      });
    }
  }
})(jQuery, Drupal);
