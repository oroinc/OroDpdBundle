<?php

namespace Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Comment\Provider\Basic;

use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Comment\Provider\OrderShippingAttachmentCommentProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides an attachment comment
 */
class BasicOrderShippingAttachmentCommentProvider implements OrderShippingAttachmentCommentProviderInterface
{
    /**
     * @var string
     */
    private $messageId;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(string $messageId, TranslatorInterface $translator)
    {
        $this->messageId = $messageId;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttachmentComment(DPDTransaction $transaction)
    {
        return $this->translator->trans($this->messageId, [
            '%parcelNumbers%' => implode(', ', $transaction->getParcelNumbers()),
        ]);
    }
}
