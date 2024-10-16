<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;

/**
* Entity that represents Rate
*
*/
#[ORM\Entity(repositoryClass: RateRepository::class)]
#[ORM\Table(name: 'oro_dpd_rate')]
#[ORM\HasLifecycleCallbacks]
class Rate
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DPDTransport::class, inversedBy: 'rates')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', nullable: false)]
    protected ?DPDTransport $transport = null;

    #[ORM\ManyToOne(targetEntity: ShippingService::class)]
    #[ORM\JoinColumn(name: 'shipping_service_id', referencedColumnName: 'code', onDelete: 'CASCADE')]
    protected ?ShippingService $shippingService = null;

    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(name: 'country_code', referencedColumnName: 'iso2_code', nullable: false)]
    protected ?Country $country = null;

    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_code', referencedColumnName: 'combined_code')]
    protected ?Region $region = null;

    #[ORM\Column(name: 'region_text', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $regionText = null;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'weight_value', type: Types::FLOAT, nullable: true)]
    protected $weightValue;

    /**
     * @var string
     */
    #[ORM\Column(name: 'price_value', type: 'money', nullable: false)]
    protected $priceValue;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DPDTransport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param DPDTransport|null $transport
     *
     * @return Rate
     */
    public function setTransport(DPDTransport $transport = null)
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * @return ShippingService
     */
    public function getShippingService()
    {
        return $this->shippingService;
    }

    /**
     * @param ShippingService $shippingService
     *
     * @return Rate
     */
    public function setShippingService(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;

        return $this;
    }

    /**
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param Country $country
     *
     * @return Rate
     */
    public function setCountry(Country $country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param Region|null $region
     *
     * @return Rate
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegionText()
    {
        return $this->regionText;
    }

    /**
     * @param string $regionText
     *
     * @return Rate
     */
    public function setRegionText($regionText)
    {
        $this->regionText = $regionText;

        return $this;
    }

    /**
     * Get name of region.
     *
     * @return string
     */
    public function getRegionName()
    {
        return $this->getRegion() ? $this->getRegion()->getName() : $this->getRegionText();
    }

    /**
     * @return float
     */
    public function getWeightValue()
    {
        return $this->weightValue;
    }

    /**
     * @param float $weightValue
     *
     * @return Rate
     */
    public function setWeightValue($weightValue)
    {
        $this->weightValue = $weightValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getPriceValue()
    {
        return $this->priceValue;
    }

    /**
     * @param string $priceValue
     *
     * @return Rate
     */
    public function setPriceValue($priceValue)
    {
        $this->priceValue = $priceValue;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return sprintf(
            '%s, %s, %s, %s => %s',
            $this->getShippingService()->getCode(),
            $this->getCountry()->getName(),
            $this->getRegionName() ? $this->getRegionName() : '*',
            $this->getWeightValue() ? number_format($this->getWeightValue(), 2) : '*',
            $this->getPriceValue()
        );
    }
}
