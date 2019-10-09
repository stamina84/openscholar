/**
 * Pairs a link and a block of content. The link will toggle the appearance of
 * that block content
 */

(function ($) {
  Drupal.behaviors.cpTaxonomyToggleTerms = {
    attach: function (ctx) {
      // Configure/customize these variables.
      var moretext = Drupal.t('More') + " <span>&#x25BC;</span>";
      var lesstext = Drupal.t('Less') + " <span>&#x25B2;</span>";

      $('.more-tags:not(.processed)').each(function () {
        var content = $(this).html();
        var html = content + '<span>,</span>&nbsp;&nbsp;<a  class="morelink togglemore">' + moretext + '</a>';
        $(this).html(html);
        $(this).addClass('processed');
      });

      $(".morelink").once('more_link_element').click(function () {
        if ($(this).hasClass('togglemore')) {
          $(this).html(lesstext);
        }
        if ($(this).hasClass('toggleless')) {
          $(this).html(moretext);
        }
        $(this).toggleClass('togglemore').toggleClass('toggleless');
        $(this).prevAll(".morecontent").children(".morechildren").toggle();
        return false;
      });
    }
  };
})(jQuery);
