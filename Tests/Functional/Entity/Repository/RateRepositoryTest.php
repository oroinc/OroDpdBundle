<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;
use Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures\LoadRates;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RateRepositoryTest extends WebTestCase
{
    private RateRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadRates::class]);

        $this->repository = self::getContainer()->get('doctrine')->getRepository(Rate::class);
    }

    /**
     * @dataProvider testFindRatesByServiceAndDestinationDataProvider
     */
    public function testFindRatesByServiceAndDestination(
        string $transportRef,
        string $shippingServiceRef,
        string $countryRef,
        string $regionRef,
        array $expectedRatesRefs
    ) {
        /** @var Rate[] $expectedRates */
        $expectedRates = $this->getEntitiesByReferences($expectedRatesRefs);

        $transport = $this->getReference($transportRef);
        $shippingService = $this->getReference($shippingServiceRef);
        /** @var Country $country */
        $country = $this->getReference($countryRef);
        /** @var Region $region */
        $region = $this->getReference($regionRef);

        $shippingAddress = $this->createMock(AddressInterface::class);
        $shippingAddress->expects(self::any())
            ->method('getCountryIso2')
            ->willReturn($country->getIso2Code());
        $shippingAddress->expects(self::any())
            ->method('getRegionCode')
            ->willReturn($region->getCode());

        $rates = $this->repository->findRatesByServiceAndDestination($transport, $shippingService, $shippingAddress);

        self::assertEquals($expectedRates, $rates);
    }

    /**
     * @dataProvider testFindRatesByServiceAndDestinationDataProvider
     */
    public function testFindFirstRateByServiceAndDestination(
        string $transportRef,
        string $shippingServiceRef,
        string $countryRef,
        string $regionRef,
        array $expectedRatesRefs
    ) {
        /** @var Rate $expectedRate */
        $expectedRate = $this->getEntitiesByReferences($expectedRatesRefs)[0];

        $transport = $this->getReference($transportRef);
        $shippingService = $this->getReference($shippingServiceRef);
        /** @var Country $country */
        $country = $this->getReference($countryRef);
        /** @var Region $region */
        $region = $this->getReference($regionRef);

        $shippingAddress = $this->createMock(AddressInterface::class);
        $shippingAddress->expects(self::any())
            ->method('getCountryIso2')
            ->willReturn($country->getIso2Code());
        $shippingAddress->expects(self::any())
            ->method('getRegionCode')
            ->willReturn($region->getCode());

        $rate = $this->repository->findFirstRateByServiceAndDestination($transport, $shippingService, $shippingAddress);

        self::assertEquals($expectedRate, $rate);
    }

    public function testFindRatesByServiceAndDestinationDataProvider(): array
    {
        return [
            [
                'transportRef' => 'dpd.transport.1',
                'shippingServiceRef' => 'dpd.shipping_service.1',
                'countryRef' => 'dpd.shipping_country.1',
                'regionRef' => 'dpd.shipping_region.1',
                'expectedRatesRefs' => [
                    'dpd.rate.1',
                    'dpd.rate.3',
                ],
            ],
            [
                'transportRef' => 'dpd.transport.1',
                'shippingServiceRef' => 'dpd.shipping_service.1',
                'countryRef' => 'dpd.shipping_country.1',
                'regionRef' => 'dpd.shipping_region.2',
                'expectedRatesRefs' => [
                    'dpd.rate.2',
                    'dpd.rate.3',
                ],
            ],
            [
                'transportRef' => 'dpd.transport.1',
                'shippingServiceRef' => 'dpd.shipping_service.1',
                'countryRef' => 'dpd.shipping_country.1',
                'regionRef' => 'dpd.shipping_region.3',
                'expectedRatesRefs' => [
                    'dpd.rate.3',
                ],
            ],
            [
                'transportRef' => 'dpd.transport.1',
                'shippingServiceRef' => 'dpd.shipping_service.2',
                'countryRef' => 'dpd.shipping_country.1',
                'regionRef' => 'dpd.shipping_region.3',
                'expectedRatesRefs' => [
                    'dpd.rate.4',
                ],
            ],
        ];
    }

    private function getEntitiesByReferences(array $rules): array
    {
        return array_map(function ($ruleReference) {
            return $this->getReference($ruleReference);
        }, $rules);
    }
}
