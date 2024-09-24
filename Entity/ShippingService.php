<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DPDBundle\Entity\Repository\ShippingServiceRepository;

/**
* Entity that represents Shipping Service
*
*/
#[ORM\Entity(repositoryClass: ShippingServiceRepository::class)]
#[ORM\Table(name: 'oro_dpd_shipping_service')]
class ShippingService
{
    const CLASSIC_SERVICE_SUBSTR = 'Classic';
    const EXPRESS_SERVICE_SUBSTR = 'Express';

    #[ORM\Id]
    #[ORM\Column(name: 'code', type: Types::STRING, length: 30)]
    protected ?string $code = null;

    #[ORM\Column(name: 'description', type: Types::STRING, length: 255)]
    protected ?string $description = null;

    #[ORM\Column(name: 'is_express', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $expressService = false;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param bool $isExpressService
     *
     * @return $this
     */
    public function setExpressService($isExpressService)
    {
        $this->expressService = $isExpressService;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpressService()
    {
        return $this->expressService;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string) $this->getDescription();
    }
}
