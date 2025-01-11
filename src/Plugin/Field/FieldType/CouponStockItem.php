<?php

declare(strict_types=1);

namespace Drupal\enhanced_promotion\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'CouponStockItem' field type.
 */
#[FieldType(
  id: 'coupon_stock',
  label: new TranslatableMarkup('Stock of coupons'),
  description: new TranslatableMarkup('Stock info of coupons of the promotion.'),
  default_widget: 'string_textfield',
  default_formatter: 'string',
)]
final class CouponStockItem extends MapItem {

  /**
   * Whether the value has been calculated.
   *
   * @var bool
   */
  protected $isCalculated = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    $this->ensureCalculated();
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureCalculated();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureCalculated();
    return parent::getValue();
  }

  /**
   * Calculates the value of the field and sets it.
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $entity */
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        $total = count($entity->getCoupons());
        $claimed = 0;
        foreach ($entity->getCoupons() as $coupon) {
          if (!$coupon->get('user_id')->isEmpty()) {
            $claimed++;
          }
        }
        $this->setValue([
          'total' => $total,
          'claimed' => $claimed,
          'available' => $total - $claimed,
        ]);
      }
      $this->isCalculated = TRUE;
    }
  }

}
