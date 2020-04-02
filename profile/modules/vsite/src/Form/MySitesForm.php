<?php

namespace Drupal\vsite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Implements a MySites form.
 */
class MySitesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'my_sites_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['my_vsite_search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search My Sites'),
      '#title_display' => 'invisible',
      '#ajax' => [
        'callback' => '::filterMyVsites',
        'event' => 'keyup',
        'wrapper' => 'sites-wrapper',
      ],
    ];
    $form['sites'] = [
      '#type' => 'container',
      '#prefix' => '<div id="sites-wrapper">',
      '#suffix' => '</div>',
    ];

    $my_vsite_search = $form_state->getValue('my_vsite_search');
    $view_name = 'user_vsites';
    $display_ids = ['my_vsites_owner', 'my_vsites_member'];
    foreach ($display_ids as $display_id) {
      $view = Views::getView($view_name);
      if (is_object($view)) {
        $view->setDisplay($display_id);
        $filters = $view->display_handler->getOption('filters');
        $filters['label']['value'] = $my_vsite_search;
        $view->display_handler->overrideOption('filters', $filters);
        $view->preExecute();
        $view->execute();
        $form['sites'][$display_id] = $view->buildRenderable($display_id);
      }
    }

    return $form;
  }

  /**
   * Ajax callback for filtering vsite results.
   */
  public function filterMyVsites(array $form, FormStateInterface $form_state) {
    return $form['sites'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
