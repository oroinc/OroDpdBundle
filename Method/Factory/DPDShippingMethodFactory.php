<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

/**
 * The factory to create DPD shipping method.
 */
class DPDShippingMethodFactory implements IntegrationShippingMethodFactoryInterface
{
    private LocalizationHelper $localizationHelper;
    private IntegrationIdentifierGeneratorInterface $methodIdentifierGenerator;
    private DPDShippingMethodTypeFactoryInterface $methodTypeFactory;
    private DPDHandlerFactoryInterface $handlerFactory;
    private IntegrationIconProviderInterface $integrationIconProvider;

    public function __construct(
        LocalizationHelper $localizationHelper,
        IntegrationIdentifierGeneratorInterface $methodIdentifierGenerator,
        DPDShippingMethodTypeFactoryInterface $methodTypeFactory,
        DPDHandlerFactoryInterface $handlerFactory,
        IntegrationIconProviderInterface $integrationIconProvider
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->methodTypeFactory = $methodTypeFactory;
        $this->handlerFactory = $handlerFactory;
        $this->integrationIconProvider = $integrationIconProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel): ShippingMethodInterface
    {
        /** @var DPDSettings $transport */
        $transport = $channel->getTransport();
        $types = [];
        $handlers = [];
        $applicableShippingServices = $transport->getApplicableShippingServices()->toArray();
        foreach ($applicableShippingServices as $shippingService) {
            $types[] = $this->methodTypeFactory->create($channel, $shippingService);
            $handlers[] = $this->handlerFactory->create($channel, $shippingService);
        }

        return new DPDShippingMethod(
            $this->methodIdentifierGenerator->generateIdentifier($channel),
            $channel->getName(),
            (string)$this->localizationHelper->getLocalizedValue($transport->getLabels()),
            $channel->isEnabled(),
            $this->integrationIconProvider->getIcon($channel),
            $types,
            $handlers
        );
    }
}
