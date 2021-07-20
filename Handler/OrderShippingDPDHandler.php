<?php

namespace Oro\Bundle\DPDBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Transaction\File\Name\Provider\TransactionFileNameProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Symfony\Component\Form\FormInterface;

class OrderShippingDPDHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var FileManager
     */
    protected $fileManager;

    /**
     * @var DPDShippingMethodProvider
     */
    protected $shippingMethodProvider;

    /**
     * @var TransactionFileNameProviderInterface
     */
    protected $transactionFileNameProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        FileManager $fileManager,
        DPDShippingMethodProvider $shippingMethodProvider,
        TransactionFileNameProviderInterface $transactionFileNameProvider
    ) {
        $this->doctrine = $doctrine;
        $this->fileManager = $fileManager;
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->transactionFileNameProvider = $transactionFileNameProvider;
    }

    /**
     * @param Order         $order
     * @param FormInterface $form
     *
     * @return array
     */
    public function shipOrder(Order $order, FormInterface $form)
    {
        $shipDate = $form->get('shipDate')->getData();
        if (!$shipDate) {
            return null;
        }

        $shippingMethod = $this->shippingMethodProvider->getShippingMethod($order->getShippingMethod());
        if (!$shippingMethod || !($shippingMethod instanceof DPDShippingMethod)) {
            return null;
        }

        $dpdHandler = $shippingMethod->getDPDHandler($order->getShippingMethodType());
        if (!$dpdHandler) {
            return null;
        }

        $response = $dpdHandler->shipOrder($order, $shipDate);

        $result = [
            'successful' => false,
            'errors' => [],
        ];

        if (!$response) {
            return $result;
        }

        $result['successful'] = $response->isSuccessful();
        $result['errors'] = $response->getErrorMessagesLong();

        if ($response->isSuccessful()) {
            $labelFile = $this->createLabelFile($order, $response);

            $dpdTransaction = (new DPDTransaction())
                ->setOrder($order)
                ->setLabelFile($labelFile)
                ->setParcelNumbers($response->getParcelNumbers());

            $em = $this->doctrine->getManagerForClass(DPDTransaction::class);
            $em->persist($dpdTransaction);
            $em->flush();

            $result['transaction'] = $dpdTransaction;
        }

        return $result;
    }

    /**
     * @param Order            $order
     * @param SetOrderResponse $response
     *
     * @return File
     */
    private function createLabelFile(Order $order, SetOrderResponse $response)
    {
        $labelFileName = $this->transactionFileNameProvider->getTransactionFileName($order, $response);

        $tmpFile = $this->fileManager->writeToTemporaryFile($response->getLabelPDF());
        $labelFile = new File();
        $labelFile->setFile($tmpFile);
        $labelFile->setOriginalFilename($labelFileName);

        return $labelFile;
    }

    /**
     * @param Order $order
     *
     * @return \DateTime
     */
    public function getNextPickupDay(Order $order)
    {
        $shippingMethod = $this->shippingMethodProvider->getShippingMethod($order->getShippingMethod());
        if (!$shippingMethod || !($shippingMethod instanceof DPDShippingMethod)) {
            return null;
        }

        $dpdHandler = $shippingMethod->getDPDHandler($order->getShippingMethodType());
        if (!$dpdHandler) {
            return null;
        }

        return $dpdHandler->getNextPickupDay(new \DateTime('now'));
    }

    public function addTrackingNumbersToOrder(Order $order, DPDTransaction $dpdTransaction)
    {
        $em = $this->doctrine->getManagerForClass(OrderShippingTracking::class);
        foreach ($dpdTransaction->getParcelNumbers() as $parcelNumber) {
            $shippingTracking = new OrderShippingTracking();
            $shippingTracking->setMethod($order->getShippingMethod());
            $shippingTracking->setNumber($parcelNumber);
            $order->addShippingTracking($shippingTracking);
            $em->persist($shippingTracking);
        }
        $em->flush();
    }

    public function unlinkLabelFromOrder(Order $order, DPDTransaction $dpdTransaction)
    {
        $em = $this->doctrine->getManagerForClass(Attachment::class);
        $attachmentRepository = $em->getRepository(Attachment::class);
        $attachment = $attachmentRepository->findOneBy(['file' => $dpdTransaction->getLabelFile()]);
        if ($attachment) {
            $em->remove($attachment);
            $em->flush();
        }
    }

    public function removeTrackingNumbersFromOrder(Order $order, DPDTransaction $dpdTransaction)
    {
        $shippingTrackings = $order->getShippingTrackings();
        $trackingNumbersToRemove = $dpdTransaction->getParcelNumbers();
        foreach ($shippingTrackings as $shippingTracking) {
            if (in_array($shippingTracking->getNumber(), $trackingNumbersToRemove)) {
                $order->removeShippingTracking($shippingTracking);
            }
        }

        $em = $this->doctrine->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();
    }
}
