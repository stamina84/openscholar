<?php

namespace Drupal\Tests\vsite\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\vsite\PathProcessor\VsiteOutboundPathProcessor;
use Symfony\Component\HttpFoundation\Request;
use Drupal\vsite\Plugin\VsiteContextManager;

/**
 * Class VsiteOutboundPathProcessorTest.
 *
 * @group unit
 * @group vsite
 * @coversDefaultClass \Drupal\vsite\PathProcessor\VsiteOutboundPathProcessor
 */
class VsiteOutboundPathProcessorTest extends UnitTestCase {

  /**
   * The object to test.
   *
   * @var \Drupal\vsite\PathProcessor\VsiteOutboundPathProcessor
   */
  protected $pathProcessor;

  /**
   * Mock for the ContextManager.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Set up testing object and mocks.
   */
  public function setUp() {
    $this->vsiteContextManager = $this->createMock(VsiteContextManager::class);

    $this->pathProcessor = new VsiteOutboundPathProcessor($this->vsiteContextManager);
  }

  /**
   * Tests the paths that don't get purls added.
   *
   * @dataProvider nonPurlPathsProvider
   */
  public function testNonPurlPaths($path): void {
    $options = [];
    $output_path = $this->pathProcessor->processOutbound($path, $options);
    $this->assertArrayHasKey('purl_context', $options);
    $this->assertEquals(FALSE, $options['purl_context']);
    $this->assertEquals($path, $output_path);

    $options = [];
    $output_path = $this->pathProcessor->processOutbound($path, $options);
    $this->assertArrayHasKey('purl_context', $options);
    $this->assertEquals(FALSE, $options['purl_context']);
    $this->assertEquals($path, $output_path);
  }

  /**
   * Test data provider - Non purl paths.
   *
   * @return array
   *   The paths.
   */
  public function nonPurlPathsProvider(): array {
    return [
      ['/admin'],
      ['/admin/foo'],
      ['/user'],
      ['/user/bar'],
    ];
  }

  /**
   * Test that urls outside of vsites don't get purls added.
   */
  public function testOutsideVsite(): void {
    $this->vsiteContextManager->method('getActivePurl')
      ->willReturn(FALSE);

    $options = [];
    $output_path = $this->pathProcessor->processOutbound('bar', $options);
    $this->assertArrayNotHasKey('purl_exit', $options);
    $this->assertEquals('bar', $output_path);

    /** @var \Symfony\Component\HttpFoundation\Request|\PHPUnit\Framework\MockObject\MockObject $request */
    $request = $this->createMock(Request::class);
    $request->method('get')
      ->willReturn(FALSE);

    $options = [];
    $output_path = $this->pathProcessor->processOutbound('bar', $options, $request);
    $this->assertEquals('bar', $output_path);
  }

  /**
   * Test that urls in vsites are handled properly.
   */
  public function testInVsite(): void {
    $this->vsiteContextManager->method('getActivePurl')
      ->willReturn('foo');

    /** @var \Symfony\Component\HttpFoundation\Request|\PHPUnit\Framework\MockObject\MockObject $request */
    $request = $this->createMock(Request::class);
    $request->method('get')
      ->willReturn(TRUE);

    $options = [];
    $output_path = $this->pathProcessor->processOutbound('foo/bar', $options);
    $this->assertEquals('foo/bar', $output_path);

    $options = [
      'purl_exit' => TRUE,
    ];
    $output_path = $this->pathProcessor->processOutbound('bar', $options, $request);
    $this->assertEquals('bar', $output_path);

    $options = [
      'purl_context' => FALSE,
    ];
    $output_path = $this->pathProcessor->processOutbound('bar', $options, $request);
    $this->assertEquals('bar', $output_path);
  }

}
