<?php

namespace Oro\Bundle\DPDBundle\Method\Identifier;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface DPDMethodTypeIdentifierGeneratorInterface
{
    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return string
     */
    public function generateIdentifier(Channel $channel, ShippingService $service);
}
