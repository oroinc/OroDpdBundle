<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Method\DPDHandler;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Factory for DPD handler service
 */
class DPDHandlerFactory implements DPDHandlerFactoryInterface
{
    private DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator;
    private DPDTransport $transport;
    private PackageProvider $packageProvider;
    private DPDRequestFactory $dpdRequestFactory;
    private CacheInterface $zipCodeRulesCache;
    private OrderShippingLineItemConverterInterface $shippingLineItemConverter;

    public function __construct(
        DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        DPDTransport $transport,
        PackageProvider $packageProvider,
        DPDRequestFactory $dpdRequestFactory,
        CacheInterface $zipCodeRulesCache,
        OrderShippingLineItemConverterInterface $shippingLineItemConverter
    ) {
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->transport = $transport;
        $this->packageProvider = $packageProvider;
        $this->dpdRequestFactory = $dpdRequestFactory;
        $this->zipCodeRulesCache = $zipCodeRulesCache;
        $this->shippingLineItemConverter = $shippingLineItemConverter;
    }

    public function create(Channel $channel, ShippingService $service): DPDHandler
    {
        return new DPDHandler(
            $this->getIdentifier($channel, $service),
            $service,
            $this->getSettings($channel),
            $this->transport,
            $this->packageProvider,
            $this->dpdRequestFactory,
            $this->zipCodeRulesCache,
            $this->shippingLineItemConverter
        );
    }

    private function getIdentifier(Channel $channel, ShippingService $service): string
    {
        return $this->typeIdentifierGenerator->generateIdentifier($channel, $service);
    }

    private function getSettings(Channel $channel): Transport|DPDSettings
    {
        return $channel->getTransport();
    }
}
