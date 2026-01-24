<?php

namespace Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Comment\Provider;

use Oro\Bundle\DPDBundle\Entity\DPDTransaction;

/**
 * Defines the contract for providing comments for DPD shipping attachment.
 */
interface OrderShippingAttachmentCommentProviderInterface
{
    /**
     * @param DPDTransaction $transaction
     *
     * @return string
     */
    public function getAttachmentComment(DPDTransaction $transaction);
}
