<?php

namespace Drupal\Tests\vsite\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\vsite\Plugin\Purl\Method\VsitePrefixMethod;
use Drupal\vsite\Plugin\VsiteContextManager;

/**
 * @coversDefaultClass \Drupal\vsite\Plugin\Purl\Method\VsitePrefixMethod
 *
 * @group unit
 * @group vsite
 */
class VsitePrefixMethodTest extends UnitTestCase {

  /**
   * Vsite prefix method.
   *
   * @var \Drupal\vsite\Plugin\Purl\Method\VsitePrefixMethod
   */
  protected $vsitePrefixMethod;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->vsiteContextManager = $this->createMock(VsiteContextManager::class);
    $this->vsitePrefixMethod = new VsitePrefixMethod([], 'vsite_prefix', [], $this->vsiteContextManager);
  }

  /**
   * @covers ::enterContext
   */
  public function testWithoutPrefix(): void {
    $this->vsiteContextManager->method('getActivePurl')
      ->willReturn('db');

    $options = [];
    $modified_path = $this->vsitePrefixMethod->enterContext('drab', '/majesty', $options);
    $this->assertTrue(isset($options['purl_exit']));
    $this->assertFalse($options['purl_exit']);
    $this->assertEquals('/drab/majesty', $modified_path);
  }

  /**
   * @covers ::enterContext
   */
  public function testWithPrefix(): void {
    $this->vsiteContextManager->method('getActivePurl')
      ->willReturn('db');

    $options = [];
    $modified_path = $this->vsitePrefixMethod->enterContext('drab', '/db/majesty', $options);
    $this->assertTrue(isset($options['purl_exit']));
    $this->assertTrue($options['purl_exit']);
    $this->assertEquals('/db/majesty', $modified_path);
  }

  /**
   * @covers ::enterContext
   */
  public function testInActivePurl(): void {
    $this->vsiteContextManager->method('getActivePurl')
      ->willReturn('');

    $options = [];
    $modified_path = $this->vsitePrefixMethod->enterContext('drab', '/majesty', $options);
    $this->assertTrue(isset($options['purl_exit']));
    $this->assertFalse($options['purl_exit']);
    $this->assertEquals('/drab/majesty', $modified_path);
  }

}
