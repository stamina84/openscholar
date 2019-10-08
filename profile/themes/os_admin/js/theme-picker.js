(function ($, Drupal) {
  Drupal.behaviors.theme_picker = {
    attach: function (context, settings) {
      $('.theme-selector').on('keydown click', function (e) {
        if (e.which === 13 || e.type === 'click') {
          console.log('d');
          let parent = $(this);
          $('.theme-selector').removeClass('checked');
          if (!parent.hasClass('theme-default')) {
            parent.addClass('checked');
          }
          var selectedTheme = parent.attr('data-attr');
          $("#edit-theme").val(selectedTheme).change();
        }
      });

    }
  };
})(jQuery, Drupal);
