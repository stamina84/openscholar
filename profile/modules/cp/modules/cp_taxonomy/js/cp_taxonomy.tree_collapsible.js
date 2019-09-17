(function ($) {

  /**
   * Expand Tree term reference widget.
   */
  Drupal.behaviors.treeTaxonomyReference = {
    attach: function () {
      // Expand link.
      $('.field--widget-reference-taxonomy-terms .toggle-wrapper a.expand').click(function(event) {
        $(this).parents('.item-list').next('.form-wrapper').find('.term-reference-tree-button.term-reference-tree-collapsed').trigger('click');
        event.preventDefault();
      });

      // Collapse link.
      $('.field--widget-reference-taxonomy-terms .toggle-wrapper a.collapse').click(function(event) {
        $(this).parents('.item-list').next('.form-wrapper').find('.term-reference-tree-button:not(.term-reference-tree-collapsed)').trigger('click');
        event.preventDefault();
      })
    }
  };

})(jQuery);
