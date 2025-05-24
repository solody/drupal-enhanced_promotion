<?php

namespace Drupal\enhanced_promotion;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Item list for a computed field that displays the current company.
 *
 * @see \Drupal\enhanced_promotion\Plugin\Field\FieldType\CouponStockItem
 */
class CouponUsageItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue(): void {
    if (!isset($this->list[0])) {
      /** @var \Drupal\commerce_promotion\Entity\CouponInterface $coupon */
      $coupon = $this->getEntity();
      /** @var \Drupal\commerce_promotion\PromotionUsageInterface $usage */
      $usage = \Drupal::service('commerce_promotion.usage');
      $this->list[0] = $this->createItem(0, $usage->loadByCoupon($coupon));
    }
  }

}
