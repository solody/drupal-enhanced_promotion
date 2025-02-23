<?php

declare(strict_types=1);

namespace Drupal\enhanced_promotion\Plugin\rest\resource;

use Drupal\commerce_promotion\CouponCodeGeneratorInterface;
use Drupal\commerce_promotion\CouponCodePattern;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Route;

/**
 * Represents Claim coupon records as resources.
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
  id: 'enhanced_promotion_claim_coupon',
  label: new TranslatableMarkup('Claim coupon'),
  uri_paths: [
    'create' => '/api/rest/enhanced-promotion/promotion/{promotion}/claim-coupon',
  ],
)]
final class ClaimCouponResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CouponCodeGeneratorInterface $couponCodeGenerator,
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
      $container->get('commerce_promotion.coupon_code_generator'),
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   */
  public function post(PromotionInterface $promotion, array $data): ModifiedResourceResponse {
    // Check the limitation.
    if (!$promotion->requiresCoupon()) {
      throw new BadRequestHttpException('The promotion can not has any coupons.');
    }
    $coupons = $promotion->getCoupons();
    if ($promotion->getUsageLimit() > 0 && count($coupons) >= $promotion->getUsageLimit()) {
      throw new BadRequestHttpException('The promotion touch usage limitation.');
    }
    $coupons_of_current_user = [];
    foreach ($coupons as $coupon) {
      $owner = $coupon->get('user_id')->entity;
      if ($owner instanceof UserInterface && (int) \Drupal::currentUser()->id() === (int) $owner->id()) {
        $coupons_of_current_user[] = $coupon;
      }
    }
    if ($promotion->getCustomerUsageLimit() > 0 && count($coupons_of_current_user) >= $promotion->getCustomerUsageLimit()) {
      throw new BadRequestHttpException('The promotion touch customer usage limitation.');
    }

    /** @var \Drupal\commerce_promotion\CouponStorageInterface $coupon_storage */
    $coupon_storage = $this->entityTypeManager->getStorage('commerce_promotion_coupon');
    $pattern = new CouponCodePattern('alphanumeric', '', '', 18);
    $codes = $this->couponCodeGenerator->generateCodes($pattern, 5);
    $new_coupon = $coupon_storage->create([
      'user_id' => \Drupal::currentUser()->id(),
      'code' => reset($codes),
      'promotion_id' => $promotion->id(),
      'usage_limit' => 1,
      'usage_limit_customer' => 1,
    ]);
    $new_coupon->save();
    return new ModifiedResourceResponse($new_coupon, 201);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method): Route {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Set ID validation pattern.
    if ($method !== 'POST') {
      $route->setRequirement('promotion', '\d+');
    }
    $parameters = $route->getOption('parameters') ?: [];
    $parameters['promotion']['type'] = 'entity:commerce_promotion';
    $route->setOption('parameters', $parameters);
    return $route;
  }

}
