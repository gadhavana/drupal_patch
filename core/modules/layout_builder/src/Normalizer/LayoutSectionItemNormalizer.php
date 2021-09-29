<?php

namespace Drupal\layout_builder\Normalizer;

use Drupal\layout_builder\Plugin\DataType\SectionData;
use Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer;

/**
 * Normalizes section lists.
 *
 * @todo Remove in https://www.drupal.org/node/issues/2957385
 *
 * @internal
 */
class LayoutSectionItemNormalizer extends FieldItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = LayoutSectionItem::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    /** @var \Drupal\layout_builder\Plugin\DataType\SectionData $section_data */
    $section_data = $this->serializer->denormalize($data['section'], SectionData::class, $format, $context);;
    $section_data = method_exists($section_data, 'getValue') ? $section_data->getValue() : $data['section'];
    return parent::denormalize($section_data, $class, $format, $context);
  }

}
