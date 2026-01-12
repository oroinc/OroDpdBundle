<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDHandlerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Defines the contract for creating DPD handler instances.
 */
interface DPDHandlerFactoryInterface
{
    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return DPDHandlerInterface
     */
    public function create(Channel $channel, ShippingService $service);
}
