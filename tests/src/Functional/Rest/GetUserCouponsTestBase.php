<?php

namespace Drupal\Tests\enhanced_promotion\Functional\Rest;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_store\StoreCreationTrait;
use GuzzleHttp\RequestOptions;

/**
 * Test for BindPhone rest resource.
 *
 * @coversDefaultClass \Drupal\user_phone\Plugin\rest\resource\BindPhone
 */
abstract class GetUserCouponsTestBase extends RestTestBase {

  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['enhanced_promotion', 'basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'enhanced_promotion_get_user_coupons';

  /**
   * Do testing.
   *
   * @covers ::post
   */
  public function testPost() {
    $this->initAuthentication();
    $this->setUpAuthorization('POST');

    $user = $this->createUser();
    $store = $this->createStore('Default store', 'admin@example.com');
    $promotion = Promotion::create([
      'name' => 'Promotion 1',
      'order_types' => ['default'],
      'stores' => [$store->id()],
      'offer' => [
        'target_plugin_id' => 'order_fixed_amount_off',
        'target_plugin_configuration' => [
          'amount' => [
            'number' => '25.00',
            'currency_code' => 'USD',
          ],
        ],
      ],
      'start_date' => '2019-11-15T10:14:00',
      'status' => TRUE,
    ]);
    $promotion->save();

    $coupon = Coupon::create([
      'promotion_id' => $promotion->id(),
      'code' => $this->randomMachineName(),
      'usage_limit' => 1,
      'usage_limit_customer' => 1,
      'user_id' => $user->id(),
      'status' => TRUE,
    ]);
    $coupon->save();

    $request_options = $this->getAuthenticationRequestOptions('POST');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;
    $response = $this->request(
      'POST',
      $this->getRequestUrl('POST', ['user' => $user->id()]),
      $request_options,
    );

    if (static::$auth) {
      // Response of ModifiedResourceResponse doesn't have any cached.
      $this->assertResourceResponse(
        200,
        FALSE,
        $response
      );
      $coupons = json_decode($response->getBody()->getContents(), TRUE);
      $this->assertCount(1, $coupons);
      $coupon = reset($coupons);
      $this->assertEquals($promotion->getName(), $coupon['name'][0]['value']);
    }
    else {
      $this->assertResourceResponse(
        403,
        FALSE,
        $response
      );
    }
  }

}
