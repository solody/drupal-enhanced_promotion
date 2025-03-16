<?php

declare(strict_types=1);

namespace Drupal\enhanced_promotion\Plugin\rest\resource;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;

/**
 * Represents Check coupons availability records as resources.
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
  id: 'enhanced_promotion_check_coupons_availability',
  label: new TranslatableMarkup('Check coupons availability'),
  uri_paths: [
    'create' => '/api/rest/enhanced-promotion/check-coupons-availability',
  ],
)]
final class CheckCouponsAvailabilityResource extends ResourceBase {

  /**
   * Responds to POST requests and saves the new record.
   */
  public function post(array $data): ModifiedResourceResponse {
    $coupons = Coupon::loadMultiple($data['coupon_ids']);
    $order = Order::load($data['order_id']);
    $rs = [];
    foreach ($coupons as $coupon) {
      $rs[(int) $coupon->id()] = $coupon->available($order);
    }
    // Return the newly created record in the response body.
    return new ModifiedResourceResponse(empty($rs) ? '{}' : $rs, 200);
  }

}
