<?php

namespace Drupal\cp_taxonomy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\cp_taxonomy\Plugin\Action\AddTermsNodeAction;

/**
 * Add terms to node entities form.
 */
class AddTermsToNodeForm extends ManageTermsNodeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_terms_to_node_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->tempStore = $this->tempStoreFactory->get(AddTermsNodeAction::TEMPSTORE_KEY);
    $form['#title'] = $this->t('Apply Terms to Content');
    return parent::buildForm($form, $form_state, $entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if ($button['#type'] == 'submit' && !empty($this->entityInfo)) {
      $vocabulary_storage = $this->entityTypeManager->getStorage('taxonomy_vocabulary');
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
      $selected_vocabulary = $form_state->getValue('vocabulary');
      $terms_to_apply = $form_state->getValue('terms');
      $terms = $term_storage->loadMultiple($terms_to_apply);
      $term_names = [];
      foreach ($terms as $term) {
        $term_names[] = $term->label();
      }
      $allowed_types = $vocabulary_storage->load($selected_vocabulary)->get('allowed_vocabulary_reference_types');
      $vocab_entities = $this->taxonomyHelper->explodeEntityBundles($allowed_types);
      $handled_bundles = $vocab_entities[$this->entityTypeId];

      $entities = $storage->loadMultiple(array_keys($this->entityInfo));
      $skipped_titles = [];
      $applied_titles = [];
      foreach ($entities as $entity) {
        $bundle = $entity->bundle();
        if (!in_array($bundle, $handled_bundles)) {
          $skipped_titles[] = $entity->label();
          continue;
        }
        /** @var \Drupal\Core\Field\FieldItemList $current_terms */
        $current_terms = $entity->get('field_taxonomy_terms');
        $attached_terms = [];
        foreach ($current_terms->getValue() as $value) {
          $attached_terms[] = $value['target_id'];
        }
        foreach ($terms as $term) {
          // Prevent append if exists.
          if (in_array($term->id(), $attached_terms)) {
            continue;
          }
          $current_terms->appendItem($term);
        }
        $entity->set('field_taxonomy_terms', $current_terms->getValue());
        $entity->save();
        $applied_titles[] = $entity->label();
      }

      $params = [
        '%terms' => implode(", ", $term_names),
      ];
      // Notify the user on the skipped nodes (nodes whose bundle is not
      // associated with the selected vocabulary).
      if (!empty($skipped_titles)) {
        $message = [
          [
            '#markup' => $this->formatPlural(count($terms_to_apply), 'Taxonomy term %terms could not be applied on the content:', '@count taxonomy terms %terms could not be applied on the content:', $params),
          ],
          [
            '#theme' => 'item_list',
            '#items' => $skipped_titles,
          ],
        ];
        $this->messenger()->addWarning($this->renderer->renderPlain($message));
      }

      // Notify the user on the applied nodes.
      if (!empty($applied_titles)) {
        $message = [
          [
            '#markup' => $this->formatPlural(count($terms_to_apply), 'Taxonomy term %terms was applied on the content:', '@count taxonomy terms %terms were applied on the content:', $params),
          ],
          [
            '#theme' => 'item_list',
            '#items' => $applied_titles,
          ],
        ];

        $this->messenger()->addStatus($this->renderer->renderPlain($message));
      }

      $this->tempStore->delete($this->currentUser->id());
    }

    $form_state->setRedirect('cp.content.collection');
  }

}
