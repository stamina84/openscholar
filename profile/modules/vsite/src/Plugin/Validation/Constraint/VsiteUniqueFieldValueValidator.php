<?php

namespace Drupal\vsite\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field value is unique within a specific vsite.
 *
 * If the request is not in a vsite, it will check globally.
 */
class VsiteUniqueFieldValueValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    $fieldData['field_name'] = $items->getFieldDefinition()->getName();
    $fieldData['item_first'] = $item;

    /** @var \Drupal\vsite\Helper\VsiteFieldValidateHelper $validate_helper */
    $validate_helper = \Drupal::service('vsite.validate_helper');

    if ($validate_helper->uniqueFieldValueValidator($fieldData, $entity)) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@entity_type' => $entity->getEntityType()->getLowercaseLabel(),
        '@field_name' => mb_strtolower($items->getFieldDefinition()->getLabel()),
      ]);
    }
  }

}
