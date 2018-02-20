<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;

class DPDShippingMethodFactory implements IntegrationShippingMethodFactoryInterface
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var DPDShippingMethodTypeFactoryInterface
     */
    private $methodTypeFactory;

    /**
     * @var DPDHandlerFactoryInterface
     */
    private $handlerFactory;

    /**
     * @var IntegrationIconProviderInterface
     */
    private $integrationIconProvider;

    /**
     * @param LocalizationHelper                      $localizationHelper
     * @param IntegrationIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param DPDShippingMethodTypeFactoryInterface   $methodTypeFactory
     * @param DPDHandlerFactoryInterface              $handlerFactory
     * @param IntegrationIconProviderInterface        $integrationIconProvider
     */
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
     * {@inheritdoc}
     */
    public function create(Channel $channel)
    {
        return new DPDShippingMethod(
            $this->getIdentifier($channel),
            $this->getLabel($channel),
            $channel->isEnabled(),
            $this->getIcon($channel),
            $this->createTypes($channel),
            $this->createHandlers($channel)
        );
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getIdentifier(Channel $channel)
    {
        return $this->methodIdentifierGenerator->generateIdentifier($channel);
    }

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getLabel(Channel $channel)
    {
        $settings = $this->getSettings($channel);

        return (string) $this->localizationHelper->getLocalizedValue($settings->getLabels());
    }

    /**
     * @param Channel $channel
     *
     * @return \Oro\Bundle\IntegrationBundle\Entity\Transport|DPDSettings
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }

    /**
     * @param Channel $channel
     *
     * @return array
     */
    private function createTypes(Channel $channel)
    {
        $applicableShippingServices = $this->getSettings($channel)->getApplicableShippingServices()->toArray();

        return array_map(function (ShippingService $shippingService) use ($channel) {
            return $this->methodTypeFactory->create($channel, $shippingService);
        }, $applicableShippingServices);
    }

    /**
     * @param Channel $channel
     *
     * @return array
     */
    private function createHandlers(Channel $channel)
    {
        $applicableShippingServices = $this->getSettings($channel)->getApplicableShippingServices()->toArray();

        return array_map(function (ShippingService $shippingService) use ($channel) {
            return $this->handlerFactory->create($channel, $shippingService);
        }, $applicableShippingServices);
    }

    /**
     * @param Channel $channel
     *
     * @return string|null
     */
    private function getIcon(Channel $channel)
    {
        return $this->integrationIconProvider->getIcon($channel);
    }
}
