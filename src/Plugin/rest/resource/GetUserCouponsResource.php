<?php

declare(strict_types=1);

namespace Drupal\enhanced_promotion\Plugin\rest\resource;

use Drupal\commerce_promotion\PromotionUsageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Represents Get user coupons records as resources.
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively, you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
#[RestResource(
  id: 'enhanced_promotion_get_user_coupons',
  label: new TranslatableMarkup('Get user coupons'),
  uri_paths: [
    'create' => '/api/rest/enhanced-promotion/get-user-coupons/{user}',
  ],
)]
final class GetUserCouponsResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PromotionUsageInterface $promotionUsage,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager'),
      $container->get('commerce_promotion.usage'),
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   */
  public function post(UserInterface $user): ModifiedResourceResponse {
    // Return the newly created record in the response body.
    $query = $this->entityTypeManager->getStorage('commerce_promotion_coupon')->getQuery();
    $query->condition('user_id', $user->id());
    $ids = $query->accessCheck(FALSE)->execute();

    $coupons = [];
    if (!empty($ids)) {
      /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons_all */
      $coupons_all = $this->entityTypeManager->getStorage('commerce_promotion_coupon')->loadMultiple($ids);
      foreach ($coupons_all as $coupon) {
        $usage = $this->promotionUsage->loadByCoupon($coupon);
        if ($coupon->getUsageLimit() <= 0) {
          $coupons[] = $coupon;
        }
        else {
          if ($usage < $coupon->getUsageLimit()) {
            $coupons[] = $coupon;
          }
        }
      }
    }

    return new ModifiedResourceResponse($coupons, 200);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method): Route {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Set ID validation pattern.
    if ($method !== 'POST') {
      $route->setRequirement('user', '\d+');
    }
    $parameters = $route->getOption('parameters') ?: [];
    $parameters['user']['type'] = 'entity:user';
    $route->setOption('parameters', $parameters);
    return $route;
  }

}
