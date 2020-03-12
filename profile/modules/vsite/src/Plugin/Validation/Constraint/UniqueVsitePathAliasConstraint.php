<?php

namespace Drupal\vsite\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for unique path alias values.
 *
 * @Constraint(
 *   id = "UniqueVsitePathAliasConstraint",
 *   label = @Translation("Unique vsite path alias.", context = "Validation"),
 * )
 */
class UniqueVsitePathAliasConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The alias %alias is already in use in this language.';

  /**
   * Violation message when the path alias exists with different capitalization.
   *
   * @var string
   */
  public $differentCapitalizationMessage = 'The alias %alias could not be added because it is already in use in this language with different capitalization: %stored_alias.';

}
