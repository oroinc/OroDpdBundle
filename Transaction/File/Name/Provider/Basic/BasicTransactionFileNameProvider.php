<?php

namespace Oro\Bundle\DPDBundle\Transaction\File\Name\Provider\Basic;

use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Transaction\File\Name\Provider\TransactionFileNameProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides transaction file name
 */
class BasicTransactionFileNameProvider implements TransactionFileNameProviderInterface
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

    #[\Override]
    public function getTransactionFileName(Order $order, SetOrderResponse $response)
    {
        return $this->translator->trans($this->messageId, [
            '%orderNumber%' => $order->getIdentifier(),
            '%parcelNumbers%' => implode(', ', $response->getParcelNumbers()),
        ]) . '.pdf';
    }
}
