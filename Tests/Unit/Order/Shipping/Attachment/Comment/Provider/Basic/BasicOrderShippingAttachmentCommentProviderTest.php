<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Order\Shipping\Attachment\Comment\Provider\Basic;

use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Comment\Provider\Basic\BasicOrderShippingAttachmentCommentProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class BasicOrderShippingAttachmentCommentProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAttachmentComment()
    {
        $messageId = 'oro_dpd.message';

        $translatedMessage = 'File comment';

        $transaction = $this->createMock(DPDTransaction::class);
        $transaction->expects(self::once())
            ->method('getParcelNumbers')
            ->willReturn([1, 4, '5']);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with($messageId, ['%parcelNumbers%' => '1, 4, 5'])
            ->willReturn($translatedMessage);

        $provider = new BasicOrderShippingAttachmentCommentProvider($messageId, $translator);

        self::assertEquals($translatedMessage, $provider->getAttachmentComment($transaction));
    }
}
