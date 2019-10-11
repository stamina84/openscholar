/**
 * jQuery for creating a popup box to display citation examples.
 */
(function ($) {
  Drupal.behaviors.profile_style_hover = {
    attach: function (ctx) {
      var moveLeft = 0;
      var moveDown = 0;

      //on tabbing popup
      $('#edit-display-type--wrapper').once('focusBehavior').focusout(function () {
        $('.stylebox').hide();
      });
      $('a.profile-pop').once('keyupBehavior').on('keyup', function (e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode == 9) {
          var target = '#' + ($(this).attr('data-popbox'));
          $('.stylebox').hide();
          $(target).show();
        }
      });

      $('a.profile-pop').hover(function(e) {

        var target = '#' + ($(this).attr('data-popbox'));
        $(target).show();
        moveLeft = $(this).outerWidth();
        moveDown = ($(target).outerHeight());
      }, function() {
        var target = '#' + ($(this).attr('data-popbox'));
        $(target).hide();
      });


      $('a.profile-pop').mousemove(function (e) {
        var target = '#' + ($(this).attr('data-popbox'));

        leftD = e.pageX + parseInt(moveLeft);
        maxRight = leftD + $(target).outerWidth();
        windowLeft = $(window).width() - 40;
        windowRight = 0;
        maxLeft = e.pageX - (parseInt(moveLeft) + $(target).outerWidth() + 20);

        if(maxRight > windowLeft && maxLeft > windowRight) {
          leftD = maxLeft;
        }

        topD = e.pageY - parseInt(moveDown);

        maxBottom = parseInt(e.pageY + parseInt(moveDown) + 20);
        windowBottom = parseInt(parseInt($(document).scrollTop()) + parseInt($(window).height()));
        maxTop = topD;
        windowTop = parseInt($(document).scrollTop());
        if(maxBottom > windowBottom) {
          topD = windowBottom - $(target).outerHeight() - 20;
        } else if(maxTop < windowTop) {
          topD = windowTop + 20;
        }
        // Top & left values are too extreme, scale them back a little.
        topD = topD - 50;
        leftD = leftD - 10;
        // Set the CSS top & left.
        $(target).css('top', topD).css('left', leftD);
      });

      // When clicking on the name of the display mode we need to check the
      // radio button.
      $('a.profile-pop').once('customBehavior').on('keydown click', function (e) {
        if (e.which === 13 || e.type === 'click') {
          var target = "#" + $(this).parent().attr('for');
          $(target).click();
        }
      });
    }
  }
})(jQuery);
