<?php

namespace Oro\Bundle\DPDBundle\Method\Identifier;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class DPDMethodTypeIdentifierGenerator implements DPDMethodTypeIdentifierGeneratorInterface
{
    #[\Override]
    public function generateIdentifier(Channel $channel, ShippingService $service)
    {
        return $service->getCode();
    }
}
