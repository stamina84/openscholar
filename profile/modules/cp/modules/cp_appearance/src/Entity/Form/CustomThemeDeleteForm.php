<?php

namespace Drupal\cp_appearance\Entity\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Custom theme delete form.
 */
final class CustomThemeDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Delete %name custom theme?', [
      '%name' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\cp_appearance\Entity\CustomThemeInterface $entity */
    $entity = $this->getEntity();

    return $this->t("The theme %name will be uninstalled. If this theme is set as default, then it's parent theme %parent_theme will be set as default. All of the theme files will be deleted. This action cannot be undone. Are you sure you want to proceed?", [
      '%name' => $entity->label(),
      '%parent_theme' => $entity->getBaseTheme(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('cp.appearance.themes');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->getEntity()->delete();

    $form_state->setRedirect('cp.appearance.themes');
  }

}