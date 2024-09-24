<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Transaction\File\Name\Provider\Basic;

use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Transaction\File\Name\Provider\Basic\BasicTransactionFileNameProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Contracts\Translation\TranslatorInterface;

class BasicTransactionFileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testGetAttachmentComment()
    {
        $messageId = 'oro_dpd.message';

        $orderIdentifier = 'orderNum';

        $order = $this->createMock(Order::class);

        $order->expects(self::once())
            ->method('getIdentifier')
            ->willReturn($orderIdentifier);

        $translatedMessage = 'File comment';

        $transaction = $this->createMock(SetOrderResponse::class);
        $transaction->expects(self::once())
            ->method('getParcelNumbers')
            ->willReturn([1, 4, '5']);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($messageId, [
                '%orderNumber%' => $orderIdentifier,
                '%parcelNumbers%' => '1, 4, 5',
            ])
            ->willReturn($translatedMessage);

        $provider = new BasicTransactionFileNameProvider($messageId, $this->translator);

        $expectedName = $translatedMessage.'.pdf';

        self::assertEquals($expectedName, $provider->getTransactionFileName($order, $transaction));
    }
}
