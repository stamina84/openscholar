<?php

namespace Drupal\Tests\os\ExistingSite;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\os\AccessHelper;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\user\EntityOwnerInterface;

/**
 * AccessHelperTest.
 *
 * @group kernel
 * @group os
 * @coversDefaultClass \Drupal\os\AccessHelper
 */
class AccessHelperTest extends OsExistingSiteTestBase {

  /**
   * @covers ::checkCreateAccess
   */
  public function testCheckCreateAccess(): void {
    // Setup.
    /** @var \Drupal\os\AccessHelperInterface $access_helper */
    $access_helper = $this->container->get('os.access_helper');
    $account = $this->createUser();

    // Negative tests.
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkCreateAccess($account, 'not_relevant'));

    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');

    $vsite_context_manager->activateVsite($this->group);
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkCreateAccess($account, 'non_existing_group_plugin'));

    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkCreateAccess($account, 'group_node:class'));

    // Positive tests.
    $this->addGroupEnhancedMember($account, $this->group);
    $this->assertInstanceOf(AccessResultAllowed::class, $access_helper->checkCreateAccess($account, 'group_node:class'));
  }

  /**
   * Tests access for node entities update.
   *
   * @covers ::checkAccess
   */
  public function testCheckAccessNodeUpdate(): void {
    $node = $this->createNode([
      'type' => 'news',
      'field_date' => [
        'value' => '2019-09-09',
      ],
    ]);
    $account = $this->createUser();

    $this->assertCheckEntityOwnerTypeAccess($node, 'update', $account);
  }

  /**
   * Tests access for node entities delete.
   *
   * @covers ::checkAccess
   */
  public function testCheckAccessNodeDelete(): void {
    $node = $this->createNode([
      'type' => 'news',
      'field_date' => [
        'value' => '2019-09-09',
      ],
    ]);
    $account = $this->createUser();

    $this->assertCheckEntityOwnerTypeAccess($node, 'delete', $account);
  }

  /**
   * Tests access for non-node entities update.
   *
   * @covers ::checkAccess
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckAccessEntityOwnerTypeUpdate(): void {
    $reference = $this->createReference();
    $account = $this->createUser();

    $this->assertCheckEntityOwnerTypeAccess($reference, 'update', $account);
  }

  /**
   * Tests access for non-node entities delete.
   *
   * @covers ::checkAccess
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckAccessEntityOwnerTypeDelete(): void {
    $reference = $this->createReference();
    $account = $this->createUser();

    $this->assertCheckEntityOwnerTypeAccess($reference, 'delete', $account);
  }

  /**
   * Tests access for non-owner type entities update.
   *
   * @covers ::checkAccess
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckAccessNonEntityOwnerTypeUpdate(): void {
    $reference = $this->createBlockContent();
    $account = $this->createUser();

    $this->assertCheckNonEntityOwnerTypeAccess($reference, 'update', $account);
  }

  /**
   * Tests access for non-owner type entities delete.
   *
   * @covers ::checkAccess
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckAccessNonEntityOwnerTypeDelete(): void {
    $reference = $this->createBlockContent();
    $account = $this->createUser();

    $this->assertCheckNonEntityOwnerTypeAccess($reference, 'delete', $account);
  }

  /**
   * @covers ::checkBlocksAccess
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckRestrictedBlockAccessOutsideVsite(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\os\AccessHelperInterface $access_helper */
    $access_helper = $this->container->get('os.access_helper');
    /** @var \Drupal\Core\Session\AccountInterface $userOne */
    $userOne = $entity_type_manager->getStorage('user')->load('1');
    $adminUser = $this->createAdminUser();
    $vsiteAdmin = $this->createUser();
    $this->addGroupAdmin($vsiteAdmin, $this->group);
    $vsiteMember = $this->createUser();
    $this->group->addMember($vsiteMember);

    foreach (AccessHelper::RESTRICTED_BLOCKS as $restricted_block_id) {
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'update', $userOne));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'update', $adminUser));
      $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($restricted_block_id, 'update', $vsiteAdmin));

      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'delete', $userOne));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'delete', $adminUser));
      $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($restricted_block_id, 'delete', $vsiteAdmin));

      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $userOne));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $adminUser));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $vsiteAdmin));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $vsiteMember));
    }
  }

  /**
   * @covers ::checkBlocksAccess
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckAllowedBlockAccessOutsideVsite(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\os\AccessHelperInterface $access_helper */
    $access_helper = $this->container->get('os.access_helper');
    /** @var \Drupal\Core\Session\AccountInterface $userOne */
    $userOne = $entity_type_manager->getStorage('user')->load('1');
    $allowed_block_id = 'addthis';
    $adminUser = $this->createAdminUser();
    $vsiteAdmin = $this->createUser();
    $this->addGroupAdmin($vsiteAdmin, $this->group);
    $vsiteMember = $this->createUser();
    $this->group->addMember($vsiteMember);

    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'update', $userOne));
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'update', $adminUser));
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'update', $vsiteMember));

    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'delete', $userOne));
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'delete', $adminUser));
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'delete', $vsiteMember));
  }

  /**
   * @covers ::checkBlocksAccess
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckRestrictedBlockAccessInsidePrivateVsite(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\os\AccessHelperInterface $access_helper */
    $access_helper = $this->container->get('os.access_helper');
    /** @var \Drupal\Core\Session\AccountInterface $userOne */
    $userOne = $entity_type_manager->getStorage('user')->load('1');
    $adminUser = $this->createAdminUser();
    $vsiteAdmin = $this->createUser();
    $this->addGroupAdmin($vsiteAdmin, $this->group);
    $vsiteMember = $this->createUser();
    $this->group->addMember($vsiteMember);
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $privateVsite = $this->createPrivateGroup();
    $privateVsiteMember = $this->createUser();
    $privateVsite->addMember($privateVsiteMember);
    $privateVsiteAdmin = $this->createUser();
    $this->addGroupAdmin($privateVsiteAdmin, $privateVsite);

    $vsite_context_manager->activateVsite($privateVsite);

    foreach (AccessHelper::RESTRICTED_BLOCKS as $restricted_block_id) {
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'update', $userOne));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'update', $adminUser));
      $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($restricted_block_id, 'update', $vsiteAdmin));
      $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($restricted_block_id, 'update', $privateVsiteAdmin));

      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'delete', $userOne));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'delete', $adminUser));
      $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($restricted_block_id, 'delete', $vsiteAdmin));
      $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($restricted_block_id, 'delete', $privateVsiteAdmin));

      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $userOne));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $adminUser));
      $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $vsiteAdmin));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $privateVsiteAdmin));
      $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($restricted_block_id, 'view', $privateVsiteMember));
    }
  }

  /**
   * @covers ::checkBlocksAccess
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testCheckAllowedBlockAccessInsidePrivateVsite(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\os\AccessHelperInterface $access_helper */
    $access_helper = $this->container->get('os.access_helper');
    /** @var \Drupal\Core\Session\AccountInterface $userOne */
    $userOne = $entity_type_manager->getStorage('user')->load('1');
    $allowed_block_id = 'addthis';
    $adminUser = $this->createAdminUser();
    $vsiteAdmin = $this->createUser();
    $this->addGroupAdmin($vsiteAdmin, $this->group);
    $vsiteMember = $this->createUser();
    $this->group->addMember($vsiteMember);
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    $privateVsite = $this->createPrivateGroup();
    $privateVsiteMember = $this->createUser();
    $privateVsite->addMember($privateVsiteMember);
    $privateVsiteAdmin = $this->createUser();
    $this->addGroupAdmin($privateVsiteAdmin, $privateVsite);

    $vsite_context_manager->activateVsite($privateVsite);

    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'update', $userOne));
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'update', $adminUser));
    $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($allowed_block_id, 'update', $vsiteAdmin));
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'update', $privateVsiteMember));

    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'delete', $userOne));
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'delete', $adminUser));
    $this->assertInstanceOf(AccessResultForbidden::class, $access_helper->checkBlocksAccess($allowed_block_id, 'delete', $vsiteAdmin));
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkBlocksAccess($allowed_block_id, 'delete', $privateVsiteMember));
  }

  /**
   * Asserts whether an entity of owner type has access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation to be performed on the entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user trying to access the entity.
   */
  protected function assertCheckEntityOwnerTypeAccess(EntityInterface $entity, string $operation, AccountInterface $account): void {
    /** @var \Drupal\os\AccessHelperInterface $access_helper */
    $access_helper = $this->container->get('os.access_helper');

    $this->assertInstanceOf(EntityOwnerInterface::class, $entity);

    // Negative tests.
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkAccess($entity, $operation, $account));

    $this->addGroupContent($entity, $this->group);
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkAccess($entity, $operation, $account));

    // Positive tests.
    $this->addGroupEnhancedMember($account, $this->group);
    $entity->setOwner($account)->save();
    $this->assertInstanceOf(AccessResultAllowed::class, $access_helper->checkAccess($entity, $operation, $account));
    $this->addGroupAdmin($account, $this->group);
    $this->assertInstanceOf(AccessResultAllowed::class, $access_helper->checkAccess($entity, $operation, $account));
  }

  /**
   * Asserts whether an entity of non-owner type has access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access to.
   * @param string $operation
   *   The operation to be performed on the entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user trying to access the entity.
   */
  protected function assertCheckNonEntityOwnerTypeAccess(EntityInterface $entity, string $operation, AccountInterface $account): void {
    /** @var \Drupal\os\AccessHelperInterface $access_helper */
    $access_helper = $this->container->get('os.access_helper');

    // Negative tests.
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkAccess($entity, $operation, $account));

    $this->addGroupContent($entity, $this->group);
    $this->assertInstanceOf(AccessResultNeutral::class, $access_helper->checkAccess($entity, $operation, $account));

    // Positive tests.
    $this->addGroupAdmin($account, $this->group);
    $this->assertInstanceOf(AccessResultAllowed::class, $access_helper->checkAccess($entity, $operation, $account));
  }

}
