/**
 * Allows users to add posts to their manual lists without an additional
 * page load on top of the ajax call
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.os_widgets_follow_me = {
    attach: function (ctx, drupalSettings) {
      if ($('#follow-links-list', ctx).length == 0) return;	// do nothing if our table doesn't exist

      var $form = $('.os-widgets-follow-me-form'),
        template = '<tr class="draggable">'+$('input[name="links[blank][title]"]').parents('tr').hide().html()+'</tr>',
        tableDrag = Drupal.tableDrag['follow-links-list'],
        count = $('input[type="hidden"][name="count"]'),
        new_id = parseInt(count.val());

      // add a new row to the table, set' all its form elements to the right values and make it draggable
      $('.add_new', $form).click(function (e) {
        e.preventDefault();
        var edit_link_to_add = $('#edit-link-to-add', $form),
          patt = /^https?:\/\/([^\/]+)/,
          val = edit_link_to_add.val(),
          matches = patt.exec(val),
          new_row, id, i, fd, weight = 0;

        // Empty field check.
        if (matches != null) {
          var domain = matches[1],
            domains = drupalSettings.follow_networks;

          // get domain
          for (i in domains) {
            fd = domains[i];
            if (domain.indexOf(fd.domain) != -1) {
              domain = i;
              break;
            }
          }

          // if we don't have a valid domain, don't make a new row
          if (domain != matches[1]) {
            id = new_id++;
            new_row = $(template.replace(/blank/g, id));
            count.val(parseInt(count.val())+1);

            // get the new weight
            $('.field-weight', $form).each(function () {
              var weight_val = $(this).val();
              if (weight_val > weight) {
                weight = parseInt(weight_val);
              }
            });

            // set all the form elements in the new row
            var field_weight  = $('.default-weight', new_row);
            var edit_link = $('#edit-links-'+id+'-weight', new_row);
            var title = domains[i]['title'];
            $('span.rrssb-text', new_row).text(val);
            $('li', new_row).addClass('rrssb-'+domain);
            $('input[name="links['+id+'][title]"]', new_row).val(title);
            $('input[name="links['+id+'][domain]"]', new_row).val(val);
            field_weight.addClass('field-weight').val(weight+1);
            field_weight.parents('div').css('display', 'none');
            edit_link.addClass('field-weight').val(weight+1);
            edit_link.parents('div').css('display', 'none');
            $('table tbody', $form).append(new_row);
            new_row = $('input[name="links['+id+'][title]"]', $form).parents('tr');

            setup_remove(new_row);
            tableDrag.makeDraggable(new_row[0]);

            // refreshes the variable
            $form = $('.os-widgets-follow-me-form');
          }
          else {
            // alert the user that the domain was not invalid.
            alert(Drupal.t(' @val is not from a valid social media domain.', {'@val': val}));
          }
        }
        else {
          alert(Drupal.t(' @val is not from a valid social media domain.', {'@val': val}));
        }
        edit_link_to_add.val('');
      });

      // set up remove links.
      function setup_remove(ctx) {
        $('.remove', ctx).click(function () {
          var $this = $(this);
          $this.parents('tr').remove();

          // decrement counter
          count.val(parseInt(count.val())-1);

          return false;
        });
      }
      // call function on document load.
      setup_remove($form);
    }
  };
})(jQuery, Drupal);