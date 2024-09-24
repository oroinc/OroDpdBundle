<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Oro\Bundle\DPDBundle\Method\Identifier\DPDMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * The factory to create DPD shipping method type.
 */
class DPDShippingMethodTypeFactory implements DPDShippingMethodTypeFactoryInterface
{
    private DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator;
    private PackageProvider $packageProvider;
    private RateProvider $rateProvider;

    public function __construct(
        DPDMethodTypeIdentifierGeneratorInterface $typeIdentifierGenerator,
        PackageProvider $packageProvider,
        RateProvider $rateProvider
    ) {
        $this->typeIdentifierGenerator = $typeIdentifierGenerator;
        $this->packageProvider = $packageProvider;
        $this->rateProvider = $rateProvider;
    }

    #[\Override]
    public function create(Channel $channel, ShippingService $service)
    {
        return new DPDShippingMethodType(
            $this->typeIdentifierGenerator->generateIdentifier($channel, $service),
            $service->getDescription(),
            $service,
            $channel->getTransport(),
            $this->packageProvider,
            $this->rateProvider
        );
    }
}
