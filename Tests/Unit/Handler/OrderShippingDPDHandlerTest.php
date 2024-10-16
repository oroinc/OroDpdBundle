<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\DPDBundle\Handler\OrderShippingDPDHandler;
use Oro\Bundle\DPDBundle\Method\DPDHandlerInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Transaction\File\Name\Provider\TransactionFileNameProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class OrderShippingDPDHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var DPDShippingMethod|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethod;

    /** @var DPDHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dpdHandler;

    /** @var TransactionFileNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $transactionFileNameProvider;

    /** @var OrderShippingDPDHandler */
    private $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->dpdHandler = $this->createMock(DPDHandlerInterface::class);
        $this->shippingMethod = $this->createMock(DPDShippingMethod::class);
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->transactionFileNameProvider = $this->createMock(TransactionFileNameProviderInterface::class);

        $this->handler = new OrderShippingDPDHandler(
            $this->doctrine,
            $this->fileManager,
            $this->shippingMethodProvider,
            $this->transactionFileNameProvider
        );
    }

    private function getOrder(int $id, string $shippingMethod, string $shippingMethodType): Order
    {
        $order = new Order();
        ReflectionUtil::setId($order, $id);
        $order->setShippingMethod($shippingMethod);
        $order->setShippingMethodType($shippingMethodType);

        return $order;
    }

    private function getDPDTransaction(array $parcelNumbers): DPDTransaction
    {
        $transaction = new DPDTransaction();
        $transaction->setParcelNumbers($parcelNumbers);

        return $transaction;
    }

    public function testShipOrderSuccess()
    {
        $responseData = [
            'Ack' => true,
            'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
            'LabelResponse' => [
                'LabelPDF' => base64_encode('pdf data'),
                'LabelDataList' => [
                    [
                        'YourInternalID' => 'internal id',
                        'ParcelNo' => 'a number',
                    ],
                ],
            ],
        ];

        $response = new SetOrderResponse();
        $response->parse($responseData);

        $this->shippingMethod->expects(self::once())
            ->method('getDPDHandler')
            ->willReturn($this->dpdHandler);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($this->shippingMethod);

        $this->dpdHandler->expects(self::once())
            ->method('shipOrder')
            ->willReturn($response);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(DPDTransaction::class)
            ->willReturn($this->manager);

        $order = $this->getOrder(1, 'flat_rate', 'primary');

        $file = new ComponentFile(__DIR__.'/../Fixtures/attachment/test_label.txt');
        $this->fileManager->expects($this->once())
            ->method('writeToTemporaryFile')
            ->with($response->getLabelPDF())
            ->willReturn($file);

        $shipDateForm = $this->createMock(FormInterface::class);
        $shipDateForm->expects(self::once())
            ->method('getData')
            ->willReturn(new \DateTime());

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('shipDate')
            ->willReturn($shipDateForm);

        $filename = 'Shipping - order.pdf';

        $this->transactionFileNameProvider->expects(self::once())
            ->method('getTransactionFileName')
            ->with($order, $response)
            ->willReturn($filename);

        $result = $this->handler->shipOrder($order, $form);

        $this->assertArrayHasKey('successful', $result);
        $this->assertEquals($response->isSuccessful(), $result['successful']);

        $this->assertArrayHasKey('errors', $result);
        $this->assertEquals($response->getErrorMessagesLong(), $result['errors']);

        $this->assertArrayHasKey('transaction', $result);
        /** @var DPDTransaction $dpdTransaction */
        $dpdTransaction = $result['transaction'];
        $this->assertInstanceOf(DPDTransaction::class, $dpdTransaction);
        $this->assertEquals($file, $dpdTransaction->getLabelFile()->getFile());

        $this->assertEquals($filename, $dpdTransaction->getLabelFile()->getOriginalFilename());
    }

    public function testShipOrderFail()
    {
        $responseData = [
            'Ack' => false,
            'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
            'ErrorDataList' => [
                [
                    'ErrorID' => 1,
                    'ErrorCode' => 'AN_ERROR_CODE',
                    'ErrorMsgShort' => 'A short error msg',
                    'ErrorMsgLong' => 'A long error msg',
                ],
            ],
        ];

        $response = new SetOrderResponse();
        $response->parse($responseData);

        $this->shippingMethod->expects(self::once())
            ->method('getDPDHandler')
            ->willReturn($this->dpdHandler);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($this->shippingMethod);

        $this->dpdHandler->expects(self::once())
            ->method('shipOrder')
            ->willReturn($response);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $order = $this->getOrder(1, 'flat_rate', 'primary');

        $this->fileManager->expects($this->never())
            ->method('writeToTemporaryFile');

        $shipDateForm = $this->createMock(FormInterface::class);
        $shipDateForm->expects(self::once())
            ->method('getData')
            ->willReturn(new \DateTime());

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('get')
            ->with('shipDate')
            ->willReturn($shipDateForm);

        $result = $this->handler->shipOrder($order, $form);

        $this->assertArrayHasKey('successful', $result);
        $this->assertEquals($response->isSuccessful(), $result['successful']);

        $this->assertArrayHasKey('errors', $result);
        $this->assertEquals($response->getErrorMessagesLong(), $result['errors']);

        $this->assertArrayNotHasKey('transaction', $result);
    }

    public function testGetNextPickupDay()
    {
        $order = $this->getOrder(1, 'flat_rate', 'primary');

        $pickupDate = new \DateTime();

        $this->shippingMethod->expects(self::once())
            ->method('getDPDHandler')
            ->willReturn($this->dpdHandler);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->willReturn($this->shippingMethod);

        $this->dpdHandler->expects(self::once())
            ->method('getNextPickupDay')
            ->willReturn($pickupDate);

        $nextPickupDay = $this->handler->getNextPickupDay($order);
        $this->assertEquals($pickupDate, $nextPickupDay);
    }

    public function testAddTrackingNumbersToOrder()
    {
        $order = $this->getOrder(1, 'a shipping method', 'primary');

        $dpdTransaction = $this->getDPDTransaction(['1', '2', '3']);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(OrderShippingTracking::class)
            ->willReturn($this->manager);

        $this->handler->addTrackingNumbersToOrder($order, $dpdTransaction);

        $this->assertCount(count($dpdTransaction->getParcelNumbers()), $order->getShippingTrackings());
    }

    public function testUnlinkExistingLabelFromOrder()
    {
        $order = $this->getOrder(1, 'a shipping method', 'primary');

        $dpdTransaction = $this->getDPDTransaction(['1', '2', '3']);

        $attachment = new Attachment();

        $attachmentRepository = $this->createMock(ObjectRepository::class);
        $attachmentRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($attachment);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Attachment::class)
            ->willReturn($this->manager);
        $this->manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($attachmentRepository);
        $this->manager->expects(self::once())
            ->method('remove')
            ->with($attachment);

        $this->handler->unlinkLabelFromOrder($order, $dpdTransaction);
    }

    public function testUnlinkNotExistingLabelFromOrder()
    {
        $order = $this->getOrder(1, 'a shipping method', 'primary');

        $dpdTransaction = $this->getDPDTransaction(['1', '2', '3']);

        $attachmentRepository = $this->createMock(ObjectRepository::class);
        $attachmentRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Attachment::class)
            ->willReturn($this->manager);
        $this->manager->expects(self::once())
            ->method('getRepository')
            ->willReturn($attachmentRepository);
        $this->manager->expects(self::never())
            ->method('remove');

        $this->handler->unlinkLabelFromOrder($order, $dpdTransaction);
    }

    public function testRemoveTrackingNumbersFromOrder()
    {
        $order = $this->getOrder(1, 'a shipping method', 'primary');

        $dpdTransaction = $this->getDPDTransaction(['1', '2', '3']);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->manager);

        $this->handler->addTrackingNumbersToOrder($order, $dpdTransaction);
        $this->assertCount(count($dpdTransaction->getParcelNumbers()), $order->getShippingTrackings());
        $this->handler->removeTrackingNumbersFromOrder($order, $dpdTransaction);
        $this->assertEmpty($order->getShippingTrackings());
    }
}
