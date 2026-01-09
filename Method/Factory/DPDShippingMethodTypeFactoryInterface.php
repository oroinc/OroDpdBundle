<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Defines the contract for creating DPD shipping method type instances.
 */
interface DPDShippingMethodTypeFactoryInterface
{
    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return DPDShippingMethodType
     */
    public function create(Channel $channel, ShippingService $service);
}
