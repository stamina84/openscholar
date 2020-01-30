/**
 * @file
 * Parses contributor name.
 */

(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.parseAuthor = {
    attach: function (context, settings) {

      var parse_label = {
        'prefix' : '<strong>' + Drupal.t('Prefix') + '</strong>',
        'first_name' : '<strong>' + Drupal.t('FirstName') + '</strong>',
        'middle_name': '<strong>' + Drupal.t('MiddleName') + '</strong>',
        'last_name': '<strong>' + Drupal.t('LastName') + '</strong>',
        'suffix' : '<strong>' + Drupal.t('Suffix') + '</strong>',
      };
      var vsite = settings.spaces.url;

      $('#author-values input[name^="author"]', context).once('parseAuthor').each(function() {
        $(this).after('<div class="os-author-parse-info"></div>');
      });
      $('#author-values input[name^="author"]', context).once().keyup(function() {
        var ele = $(this);
        var name = ele.val();
        if (name.length > 2) {
          $.ajax({
          url: vsite + 'contributor/parse/autocomplete/' + name,
          dataType: 'json',
          success: function (data) {
            var output = '';
            $.each(data, function(key, value) {
              if (parse_label[key]) {
                output += parse_label[key] + ': ' + value.trim() + ' ';
              }
            });
            ele.siblings('.os-author-parse-info').html(output);
          }
        });
        }
      });
    }
  };

})(jQuery, Drupal);
