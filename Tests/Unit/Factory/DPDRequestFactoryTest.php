<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Factory;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Factory\DPDRequestFactory;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;

class DPDRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createSetOrderRequestDataProvider
     */
    public function testCreateSetOrderRequest(
        string $requestAction,
        \DateTime $shipDate,
        string $orderId,
        OrderAddress $orderAddress,
        string $orderEmail,
        array $packages
    ) {
        $transport = new DPDTransport();
        $transport->setLabelSize(DPDTransport::PDF_A4_LABEL_SIZE);
        $transport->setLabelStartPosition(DPDTransport::UPPERLEFT_LABEL_START_POSITION);

        $shippingService = new ShippingService();
        $shippingService->setCode('Classic');
        $shippingService->setDescription('DPD Classic');

        $dpdRequestFactory = new DPDRequestFactory();
        $request = $dpdRequestFactory->createSetOrderRequest(
            $transport,
            $shippingService,
            $requestAction,
            $shipDate,
            $orderId,
            $orderAddress,
            $orderEmail,
            $packages
        );
        self::assertInstanceOf(SetOrderRequest::class, $request);
        self::assertSameSize($packages, $request->getOrderDataList());
        self::assertEquals($requestAction, $request->getOrderAction());
        self::assertEquals($shipDate, $request->getShipDate());
        if (count($request->getOrderDataList()) > 0) {
            foreach ($request->getOrderDataList() as $idx => $orderData) {
                self::assertEquals($packages[$idx]->getContents(), $orderData->getReference1());
                self::assertEquals($orderId, $orderData->getReference2());
            }
        }
    }

    public function createSetOrderRequestDataProvider(): array
    {
        return [
            'no_packages' => [
                'requestAction' => SetOrderRequest::START_ORDER_ACTION,
                'shipDate' => new \DateTime(),
                'orderId' => '1',
                'orderAddress' => new OrderAddress(),
                'orderEmail' => 'an@email.com',
                'packages' => [],
            ],
            'one_packages' => [
                'requestAction' => SetOrderRequest::START_ORDER_ACTION,
                'shipDate' => new \DateTime(),
                'orderId' => '1',
                'orderAddress' => new OrderAddress(),
                'orderEmail' => 'an@email.com',
                'packages' => [(new Package())->setContents('contents')],
            ],
            'two_packages' => [
                'requestAction' => SetOrderRequest::START_ORDER_ACTION,
                'shipDate' => new \DateTime(),
                'orderId' => '1',
                'orderAddress' => new OrderAddress(),
                'orderEmail' => 'an@email.com',
                'packages' => [(new Package())->setContents('contents'),
                    (new Package())->setContents('other contents'), ],
            ],
        ];
    }
}
