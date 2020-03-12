<?php

namespace Drupal\vsite\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Constraint validator for a unique path alias.
 */
class UniqueVsitePathAliasConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new UniquePathAliasConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\path_alias\PathAliasInterface $entity */
    $path = $entity->getPath();
    $alias = $entity->getAlias();
    $langcode = $entity->language()->getId();

    $storage = $this->entityTypeManager->getStorage('path_alias');
    $alias_with_group = $alias;
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContext */
    $vsiteContext = \Drupal::service('vsite.context_manager');
    if ($group = $vsiteContext->getActiveVsite()) {
      $group_prefix = '/[vsite:' . $group->id() . ']';
      if (strpos($alias, $group_prefix) === FALSE) {
        $alias_with_group = $group_prefix . $alias;
      }
    }
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('alias', $alias_with_group, '=')
      ->condition('langcode', $langcode, '=');

    if (!$entity->isNew()) {
      $query->condition('id', $entity->id(), '<>');
    }
    if ($path) {
      $query->condition('path', $path, '<>');
    }

    if ($result = $query->range(0, 1)->execute()) {
      $existing_alias_id = reset($result);
      $existing_alias = $storage->load($existing_alias_id);

      if ($existing_alias->getAlias() !== $alias && $alias_with_group == $alias) {
        $this->context->buildViolation($constraint->differentCapitalizationMessage, [
          '%alias' => $alias,
          '%stored_alias' => $existing_alias->getAlias(),
        ])->addViolation();
      }
      else {
        $this->context->buildViolation($constraint->message, [
          '%alias' => $alias,
        ])->addViolation();
      }
    }
  }

}
