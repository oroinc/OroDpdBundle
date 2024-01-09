<?php

namespace Oro\Bundle\DPDBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDTransportEntity;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

/**
 * Provides rate value for DPD shipping method.
 */
class RateProvider
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var MeasureUnitConversion */
    protected $measureUnitConversion;

    /**
     * RateTablePriceProvider constructor.
     */
    public function __construct(
        ManagerRegistry $registry,
        MeasureUnitConversion $measureUnitConversion
    ) {
        $this->registry = $registry;
        $this->measureUnitConversion = $measureUnitConversion;
    }

    /**
     * @param DPDTransportEntity $transport
     * @param ShippingService    $shippingService
     * @param AddressInterface   $shippingAddress
     *
     * @return null|string
     */
    public function getRateValue(
        DPDTransportEntity $transport,
        ShippingService $shippingService,
        AddressInterface $shippingAddress
    ) {
        $rateValue = null;
        if ($transport->getRatePolicy() === DPDTransportEntity::FLAT_RATE_POLICY) {
            $rateValue = $transport->getFlatRatePriceValue();
        } elseif ($transport->getRatePolicy() === DPDTransportEntity::TABLE_RATE_POLICY) {
            /** @var RateRepository $rateRepository */
            $rateRepository =
                $this->registry->getManagerForClass(Rate::class)->getRepository(Rate::class);
            $rate =
                $rateRepository->findFirstRateByServiceAndDestination($transport, $shippingService, $shippingAddress);

            if ($rate !== null) {
                $rateValue = $rate->getPriceValue();
            }
        }

        return $rateValue;
    }
}
