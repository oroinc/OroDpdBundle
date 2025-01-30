<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareTrait;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Represents DPD transaction.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_dpd_shipping_transaction')]
class DPDTransaction
{
    use CreatedAtAwareTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'parcel_numbers', type: Types::ARRAY)]
    protected $parcelNumbers = [];

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: File::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'file_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?File $labelFile = null;

    /**
     * DPDTransaction constructor.
     */
    public function __construct()
    {
        $this->parcelNumbers = array();
        $this->setCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $parcelNumber
     *
     * @return DPDTransaction
     */
    public function addParcelNumber($parcelNumber)
    {
        $this->parcelNumbers[] = $parcelNumber;

        return $this;
    }

    /**
     * @param array $parcelNumbers
     *
     * @return DPDTransaction
     */
    public function setParcelNumbers(array $parcelNumbers)
    {
        $this->parcelNumbers = $parcelNumbers;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getParcelNumbers()
    {
        return $this->parcelNumbers;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     *
     * @return DPDTransaction
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return File
     */
    public function getLabelFile()
    {
        return $this->labelFile;
    }

    /**
     * @param File|null $labelFile
     *
     * @return DPDTransaction
     */
    public function setLabelFile(?File $labelFile = null)
    {
        $this->labelFile = $labelFile;

        return $this;
    }
}
