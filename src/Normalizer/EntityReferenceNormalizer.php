<?php

namespace Drupal\enhanced_promotion\Normalizer;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;

/**
 * Expands entity reference field values to their referenced entity.
 */
class EntityReferenceNormalizer extends EntityReferenceFieldItemNormalizer {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new EntityReferenceNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, RouteMatchInterface $route_match) {
    parent::__construct($entity_repository);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL, array $context = []): bool {
    $supported = parent::supportsNormalization($data, $format, $context);
    if ($supported) {
      $route = $this->routeMatch->getRouteName();
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $data */
      $name = $data->getFieldDefinition()->getName();
      return $route === 'rest.enhanced_promotion_get_user_coupons.POST' && $name === 'promotion_id';
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    assert($field_item instanceof EntityReferenceItem);
    $entity = $field_item->get('entity')->getValue();
    return $this->serializer->normalize($entity, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      EntityReferenceItem::class => FALSE,
    ];
  }

}
