<?php


namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\layout_builder\Normalizer\SectionDataNormalizer;
use Drupal\layout_builder\Plugin\DataType\SectionData;
use Drupal\layout_builder\Section;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * @coversDefaultClass \Drupal\layout_builder\Normalizer\SectionDataNormalizer
 * @group layout_builder
 */
class SectionDataNormalizerTest extends UnitTestCase {

  /**
   * The normalizer.
   *
   * @var \Drupal\layout_builder\Normalizer\SectionDataNormalizer
   */
  protected $normalizer;

  /**
   * @covers ::supportsNormalization
   */
  public function testSupportsNormalization() {
    $section_data = $this->prophesize(SectionData::class);
    $this->assertTrue($this->normalizer->supportsNormalization($section_data->reveal()));
  }

  /**
   * @covers ::supportsDenormalization
   */
  public function testSupportsDenormalization() {
    $this->assertTrue($this->normalizer->supportsDenormalization([], SectionData::class));
  }

  /**
   * Tests the normalize function.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $data = ['foo'];
    $section_data = $this->prophesize(SectionData::class);
    $section = $this->prophesize(Section::class);
    $section->toArray()
      ->willReturn($data);
    $section_data->getValue()
      ->willReturn($section);
    $this->assertArrayEquals($data, $this->normalizer->normalize($section_data->reveal()));
  }

  /**
   * Tests the denormalize function.
   *
   * @covers ::denormalize
   */
  public function testDenormalize() {
    $data = ['foo'];
    $target_instance = $this->prophesize(FieldItemInterface::class);
    $target_instance->getDataDefinition()
      ->willReturn($this->prophesize(DataDefinitionInterface::class));
    $context = [
      'target_instance' => [
        $target_instance,
      ],
    ];
    $this->setExpectedException(InvalidArgumentException::class);
    $this->normalizer->denormalize($data, SectionData::class);
    $section_data = $this->normalizer->denormalize($data, SectionData::class, $context);
    $this->assertArrayEquals($data, $section_data->getValue()->toArray());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->normalizer = new SectionDataNormalizer();
  }

}
