<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Entity;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\File\File;

class DPDTransportTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testAccessors()
    {
        self::assertPropertyAccessors(new DPDTransport(), [
            ['dpdTestMode', false],
            ['cloudUserId', 'some string'],
            ['cloudUserToken', 'some string'],
            ['unitOfWeight', new WeightUnit()],
            ['ratePolicy', DPDTransport::FLAT_RATE_POLICY],
            ['flatRatePriceValue', '1.000'],
            ['ratesCsv', new File('path', false)],
            ['labelSize', 'some string'],
            ['labelStartPosition', 'some string'],
            ['invalidateCacheAt', new \DateTime('2020-01-01')],
        ]);
        self::assertPropertyCollections(new DPDTransport(), [
            ['applicableShippingServices', new ShippingService()],
            ['rates', new Rate()],
            ['labels', new LocalizedFallbackValue()],
        ]);
    }

    public function testGetSettingsBag()
    {
        $entity = $this->getEntity(
            DPDTransport::class,
            [
                'dpdTestMode' => false,
                'cloudUserId' => 'some cloud user id',
                'cloudUserToken' => 'some cloud user token',
                'unitOfWeight' => ((new WeightUnit())->setCode('kg')),
                'ratePolicy' => DPDTransport::FLAT_RATE_POLICY,
                'flatRatePriceValue' => '1.000',
                'labelSize' => DPDTransport::PDF_A4_LABEL_SIZE,
                'labelStartPosition' => DPDTransport::UPPERLEFT_LABEL_START_POSITION,
                'invalidate_cache_at' => new \DateTime('2020-01-01'),
                'applicableShippingServices' => [new ShippingService()],
                'labels' => [(new LocalizedFallbackValue())->setString('DPD')],
            ]
        );

        $result = $entity->getSettingsBag();

        self::assertFalse($result->get('test_mode'));
        self::assertEquals('some cloud user id', $result->get('cloud_user_id'));
        self::assertEquals('some cloud user token', $result->get('cloud_user_token'));
        self::assertEquals(((new WeightUnit())->setCode('kg')), $result->get('unit_of_weight'));
        self::assertEquals(DPDTransport::FLAT_RATE_POLICY, $result->get('rate_policy'));
        self::assertEquals('1.000', $result->get('flat_rate_price_value'));
        self::assertEquals(DPDTransport::PDF_A4_LABEL_SIZE, $result->get('label_size'));
        self::assertEquals(DPDTransport::UPPERLEFT_LABEL_START_POSITION, $result->get('label_start_position'));
        self::assertEquals(new \DateTime('2020-01-01'), $result->get('invalidate_cache_at'));

        self::assertEquals(
            $result->get('applicable_shipping_services'),
            $entity->getApplicableShippingServices()->toArray()
        );
        self::assertEquals(
            $result->get('rates'),
            $entity->getRates()->toArray()
        );
        self::assertEquals(
            $result->get('labels'),
            $entity->getLabels()->toArray()
        );
    }
}
