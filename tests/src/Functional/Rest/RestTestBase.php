<?php

namespace Drupal\Tests\enhanced_promotion\Functional\Rest;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\Tests\rest\Functional\ResourceTestBase;

/**
 * Rest test base.
 */
abstract class RestTestBase extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource([static::$format], $auth, ['POST']);
  }

  /**
   * Get request url of specific method.
   *
   * @param string $method
   *   The method.
   * @param array $parameters
   *   The route parameters.
   *
   * @return \Drupal\Core\Url
   *   The url to request.
   */
  protected function getRequestUrl(string $method, array $parameters = []): Url {
    return Url::fromRoute(
      'rest.' . static::$resourceConfigId . ".$method",
      $parameters + [
        '_format' => static::$format,
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    if (static::$auth === FALSE) {
      return;
    }
    switch ($method) {
      case 'POST':
        $this->grantPermissionsToTestedRole(['restful post ' . static::$resourceConfigId]);
        break;

      default:
        throw new \UnexpectedValueException();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    return (new CacheableMetadata())
      ->setCacheTags([
        '4xx-response',
        'config:user.role.anonymous',
        'http_response',
      ])
      ->setCacheContexts(['user.permissions']);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options): void {}

}
