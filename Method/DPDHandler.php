<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCacheKey;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Handler for DPD service, sends order there, calculates if shipDate is a valid pickup day
 */
class DPDHandler implements DPDHandlerInterface
{
    private const CACHE_LIFETIME = 86400;

    protected string $identifier;
    protected DPDSettings $transport;
    protected DPDTransportProvider $transportProvider;
    protected ShippingService $shippingService;
    protected PackageProvider $packageProvider;
    protected DPDRequestFactory $dpdRequestFactory;
    protected CacheInterface $zipCodeRulesCache;
    protected OrderShippingLineItemConverterInterface $shippingLineItemConverter;
    protected ?\DateTime $today = null;

    /**
     * @param $identifier
     * @param ShippingService                         $shippingService
     * @param DPDSettings                             $transport
     * @param DPDTransportProvider                    $transportProvider
     * @param PackageProvider                         $packageProvider
     * @param DPDRequestFactory                       $dpdRequestFactory
     * @param CacheInterface                          $zipCodeRulesCache
     * @param OrderShippingLineItemConverterInterface $shippingLineItemConverter
     * @param \DateTime                               $today
     */
    public function __construct(
        $identifier,
        ShippingService $shippingService,
        DPDSettings $transport,
        DPDTransportProvider $transportProvider,
        PackageProvider $packageProvider,
        DPDRequestFactory $dpdRequestFactory,
        CacheInterface $zipCodeRulesCache,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter,
        ?\DateTime $today = null
    ) {
        $this->identifier = $identifier;
        $this->shippingService = $shippingService;
        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->packageProvider = $packageProvider;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->zipCodeRulesCache = $zipCodeRulesCache;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
        $this->today = $today;
        if (null === $this->today) {
            $this->today = new \DateTime('today');
        }
    }

    public function getIdentifier(): string|int
    {
        return $this->identifier;
    }

    public function shipOrder(Order $order, \DateTime $shipDate): ?SetOrderResponse
    {
        $convertedLineItems = $this->shippingLineItemConverter->convertLineItems($order->getLineItems());
        $packageList = $this->packageProvider->createPackages($convertedLineItems);
        if (!$packageList || count($packageList) !== 1) {
            return null;
        }

        $setOrderRequest = $this->dpdRequestFactory->createSetOrderRequest(
            $this->transport,
            $this->shippingService,
            SetOrderRequest::START_ORDER_ACTION,
            $shipDate,
            $order->getId(),
            $order->getShippingAddress(),
            $order->getEmail(),
            $packageList
        );

        $setOrderResponse = $this->transportProvider->getSetOrderResponse($setOrderRequest, $this->transport);

        return $setOrderResponse;
    }

    public function getNextPickupDay(\DateTime $shipDate): \DateTime
    {
        while (($addHint = $this->checkShipDate($shipDate)) !== 0) {
            $shipDate->add(new \DateInterval('P'.$addHint.'D'));
        }

        return $shipDate;
    }

    /**
     * Check if shipDate is a valid pickup day.
     *
     * @return int 0 if shipDate is valid pickup day or a number of days to increase shipDate for a possible valid date
     */
    private function checkShipDate(\DateTime $shipDate): int
    {
        $zipCodeRulesResponse = $this->fetchZipCodeRules();

        $shipDateMidnight = clone $shipDate;
        $shipDateMidnight->setTime(0, 0, 0);
        $diff = $this->today->diff($shipDateMidnight);
        $diffDays = (int) $diff->format('%R%a');
        if ($diffDays === 0) {
            $cutOffDate = clone $this->today;
            $off = $zipCodeRulesResponse->getClassicCutOff();
            if ($this->shippingService->isExpressService()) {
                $off = $zipCodeRulesResponse->getExpressCutOff();
            }
            if (!$off) {
                return 0;
            }
            list($cutOffHour, $cutOffMin) = explode(':', $off);
            $cutOffDate->setTime((int) $cutOffHour, $cutOffMin);
            if ($shipDate > $cutOffDate) {
                return 1;
            }
        }

        // check if shipDate is saturday or sunday
        $shipDateWeekDay = (int) $shipDate->format('N');
        switch ($shipDateWeekDay) {
            case 6://saturday
                return 2;
            case 7://sunday
                return 1;
        }

        // check if shipDate inside noPickupDays
        if ($zipCodeRulesResponse->isNoPickupDay($shipDate)) {
            return 1;
        }

        return 0;
    }

    public function fetchZipCodeRules(): ?ZipCodeRulesResponse
    {
        $zipCodeRulesRequest = $this->dpdRequestFactory->createZipCodeRulesRequest();
        $zipCodeRulesKey = $this->createKey($this->transport, $zipCodeRulesRequest);
        $cacheKey = $this->generateStringKey($zipCodeRulesKey);
        return $this->zipCodeRulesCache->get($cacheKey, function (ItemInterface $item) use ($zipCodeRulesKey) {
            $interval = 0;
            $invalidateCacheAt = $zipCodeRulesKey->getTransport()->getInvalidateCacheAt();
            if ($invalidateCacheAt) {
                $interval = $invalidateCacheAt->getTimestamp() - time();
            }
            if ($interval <= 0) {
                $interval = static::CACHE_LIFETIME;
            }
            $item->expiresAfter($interval);
            return $this->transportProvider->getZipCodeRulesResponse($this->transport);
        });
    }

    private function createKey(
        DPDTransport $transport,
        ZipCodeRulesRequest $zipCodeRulesRequest
    ): ZipCodeRulesCacheKey {
        return (new ZipCodeRulesCacheKey())
            ->setTransport($transport)
            ->setZipCodeRulesRequest($zipCodeRulesRequest);
    }

    private function generateStringKey(ZipCodeRulesCacheKey $key): string
    {
        $invalidateAt = '';
        if ($key->getTransport() && $key->getTransport()->getInvalidateCacheAt()) {
            $invalidateAt = $key->getTransport()->getInvalidateCacheAt()->getTimestamp();
        }

        return implode('_', [
            $key->generateKey(),
            $invalidateAt,
        ]);
    }
}
