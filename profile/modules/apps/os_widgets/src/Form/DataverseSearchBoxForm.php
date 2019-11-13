<?php

namespace Drupal\os_widgets\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the dataverse search box form.
 */
class DataverseSearchBoxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dataverse_search_box_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $params = []) {
    $form['search'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#title_display' => 'invisible',
      '#placeholder' => isset($params['dataverse_search_box_placeholder']) ? $params['dataverse_search_box_placeholder'] : $this->t('Search My Dataverse'),
      '#attributes' => [
        'class' => ['dataverse_search_input'],
        'title' => $this->t('Enter the terms you wish to search for.'),
      ],
      '#required' => TRUE,
    ];
    // TODO: Discuss with team how bootstrap's `Plugin/Process/Search.php`
    // forces a visible description.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['dataverse_search_button'],
      ],
    ];
    // TODO: Talk to team, learn how we automatically get search icon here.
    $form['#attached']['library'][] = 'os_widgets/dataverse_search_box';
    $form['#attached']['drupalSettings']['osWidgets']['dataverseIdentifier'] = $params['dataverse_identifier'];
    $form['#attached']['drupalSettings']['osWidgets']['dataverseSearchBaseurl'] = $params['dataverse_search_baseurl'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Do we want server handling for this form if JS is turned off?
  }

}
