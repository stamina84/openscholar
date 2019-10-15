<?php

namespace Drupal\cp_users\Form;

/**
 * Form for adding new vsite member.
 */
class CpUsersAddNewMemberForm extends CpUsersAddMemberFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cp_users_add_new_member';
  }

}
