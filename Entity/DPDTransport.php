<?php

namespace Oro\Bundle\DPDBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
* Entity that represents DPD Transport
*
*/
#[ORM\Entity]
class DPDTransport extends Transport
{
    const FLAT_RATE_POLICY = 0;
    const TABLE_RATE_POLICY = 1;

    const PDF_A4_LABEL_SIZE = 'PDF_A4';
    const PDF_A6_LABEL_SIZE = 'PDF_A6';

    const UPPERLEFT_LABEL_START_POSITION = 'UpperLeft';
    const UPPERRIGHT_LABEL_START_POSITION = 'UpperRight';
    const LOWERLEFT_LABEL_START_POSITION = 'LowerLeft';
    const LOWERRIGHT_LABEL_START_POSITION = 'LowerRight';

    #[ORM\Column(name: 'dpd_test_mode', type: Types::BOOLEAN, nullable: false)]
    protected ?bool $dpdTestMode = null;

    #[ORM\Column(name: 'dpd_cloud_user_id', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $cloudUserId = null;

    #[ORM\Column(name: 'dpd_cloud_user_token', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $cloudUserToken = null;

    /**
     * @var Collection<int, ShippingService>
     */
    #[ORM\ManyToMany(targetEntity: ShippingService::class, fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'oro_dpd_transport_ship_service')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'ship_service_id', referencedColumnName: 'code', onDelete: 'CASCADE')]
    protected ?Collection $applicableShippingServices = null;

    #[ORM\ManyToOne(targetEntity: WeightUnit::class)]
    #[ORM\JoinColumn(name: 'dpd_unit_of_weight_code', referencedColumnName: 'code')]
    protected ?WeightUnit $unitOfWeight = null;

    #[ORM\Column(name: 'dpd_rate_policy', type: Types::SMALLINT, nullable: false)]
    protected ?int $ratePolicy = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'dpd_flat_rate_price_value', type: 'money', nullable: false)]
    protected $flatRatePriceValue;

    /**
     * @var Collection<int, Rate>
     */
    #[ORM\OneToMany(
        mappedBy: 'transport',
        targetEntity: Rate::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected ?Collection $rates = null;

    /**
     * @var File
     */
    protected $ratesCsv;

    #[ORM\Column(name: 'dpd_label_size', type: Types::STRING, length: 10, nullable: false)]
    protected ?string $labelSize = null;

    #[ORM\Column(name: 'dpd_label_start_position', type: Types::STRING, length: 20, nullable: false)]
    protected ?string $labelStartPosition = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_dpd_transport_label')]
    #[ORM\JoinColumn(name: 'transport_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    protected ?Collection $labels = null;

    #[ORM\Column(name: 'dpd_invalidate_cache_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $invalidateCacheAt = null;

    /**
     * @var ParameterBag
     */
    protected $settings;

    public function __construct()
    {
        $this->applicableShippingServices = new ArrayCollection();
        $this->rates = new ArrayCollection();
        $this->labels = new ArrayCollection();
    }

    /**
     * @return bool
     */
    public function getDPDTestMode()
    {
        return $this->dpdTestMode;
    }

    /**
     * @param bool $testMode
     *
     * @return DPDTransport
     */
    public function setDPDTestMode($testMode)
    {
        $this->dpdTestMode = $testMode;

        return $this;
    }

    /**
     * @return string
     */
    public function getCloudUserId()
    {
        return $this->cloudUserId;
    }

    /**
     * @param string $cloudUserId
     *
     * @return DPDTransport
     */
    public function setCloudUserId($cloudUserId)
    {
        $this->cloudUserId = $cloudUserId;

        return $this;
    }

    /**
     * @return string
     */
    public function getCloudUserToken()
    {
        return $this->cloudUserToken;
    }

    /**
     * @param string $cloudUserToken
     *
     * @return DPDTransport
     */
    public function setCloudUserToken($cloudUserToken)
    {
        $this->cloudUserToken = $cloudUserToken;

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function addLabel(LocalizedFallbackValue $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function removeLabel(LocalizedFallbackValue $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return Collection|ShippingService[]
     */
    public function getApplicableShippingServices()
    {
        return $this->applicableShippingServices;
    }

    /**
     * @param string $code
     *
     * @return ShippingService|null
     */
    public function getApplicableShippingService($code)
    {
        $result = null;

        foreach ($this->applicableShippingServices as $service) {
            if ($service->getCode() === $code) {
                $result = $service;
                break;
            }
        }

        return $result;
    }

    /**
     * @param ShippingService $service
     *
     * @return $this
     */
    public function addApplicableShippingService(ShippingService $service)
    {
        if (!$this->applicableShippingServices->contains($service)) {
            $this->applicableShippingServices->add($service);
        }

        return $this;
    }

    /**
     * @param ShippingService $service
     *
     * @return $this
     */
    public function removeApplicableShippingService(ShippingService $service)
    {
        if ($this->applicableShippingServices->contains($service)) {
            $this->applicableShippingServices->removeElement($service);
        }

        return $this;
    }

    /**
     * @return WeightUnit
     */
    public function getUnitOfWeight()
    {
        return $this->unitOfWeight;
    }

    /**
     * @param WeightUnit $unitOfWeight
     *
     * @return DPDTransport
     */
    public function setUnitOfWeight(WeightUnit $unitOfWeight)
    {
        $this->unitOfWeight = $unitOfWeight;

        return $this;
    }

    /**
     * @return int
     */
    public function getRatePolicy()
    {
        return $this->ratePolicy;
    }

    /**
     * @param int $ratePolicy
     *
     * @return DPDTransport
     */
    public function setRatePolicy($ratePolicy)
    {
        $this->ratePolicy = $ratePolicy;

        return $this;
    }

    /**
     * @return string
     */
    public function getFlatRatePriceValue()
    {
        return $this->flatRatePriceValue;
    }

    /**
     * @param string $flatRatePriceValue
     *
     * @return DPDTransport
     */
    public function setFlatRatePriceValue($flatRatePriceValue)
    {
        $this->flatRatePriceValue = $flatRatePriceValue;

        return $this;
    }

    /**
     * @return Collection|Rate[]
     */
    public function getRates()
    {
        return $this->rates;
    }

    /**
     * @param Rate $rate
     *
     * @return DPDTransport
     */
    public function addRate(Rate $rate)
    {
        if (!$this->rates->contains($rate)) {
            $this->rates->add($rate);
            $rate->setTransport($this);
        }

        return $this;
    }

    /**
     * @return DPDTransport
     */
    public function removeAllRates()
    {
        foreach ($this->rates as $rate) {
            $this->removeRate($rate);
        }

        return $this;
    }

    /**
     * @param Rate $rate
     *
     * @return DPDTransport
     */
    public function removeRate(Rate $rate)
    {
        if ($this->rates->contains($rate)) {
            $this->rates->removeElement($rate);
            $rate->setTransport(null);
        }

        return $this;
    }

    /**
     * @return File
     */
    public function getRatesCsv()
    {
        return $this->ratesCsv;
    }

    /**
     * @param File $ratesCsv
     *
     * @return DPDTransport
     */
    public function setRatesCsv(File $ratesCsv)
    {
        $this->ratesCsv = $ratesCsv;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelSize()
    {
        return $this->labelSize;
    }

    /**
     * @param string $labelSize
     *
     * @return DPDTransport
     */
    public function setLabelSize($labelSize)
    {
        $this->labelSize = $labelSize;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelStartPosition()
    {
        return $this->labelStartPosition;
    }

    /**
     * @param string $labelStartPosition
     *
     * @return DPDTransport
     */
    public function setLabelStartPosition($labelStartPosition)
    {
        $this->labelStartPosition = $labelStartPosition;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'test_mode' => $this->getDPDTestMode(),
                    'cloud_user_id' => $this->getCloudUserId(),
                    'cloud_user_token' => $this->getCloudUserToken(),
                    'invalidate_cache_at' => $this->getInvalidateCacheAt(),
                    'applicable_shipping_services' => $this->getApplicableShippingServices()->toArray(),
                    'unit_of_weight' => $this->getUnitOfWeight(),
                    'rate_policy' => $this->getRatePolicy(),
                    'flat_rate_price_value' => $this->getFlatRatePriceValue(),
                    'rates' => $this->getRates()->toArray(),
                    'label_size' => $this->getLabelSize(),
                    'label_start_position' => $this->getLabelStartPosition(),
                    'labels' => $this->getLabels()->toArray(),
                ]
            );
        }

        return $this->settings;
    }

    /**
     * Set invalidateCacheAt.
     *
     * @param \DateTime|null $invalidateCacheAt
     *
     * @return $this
     */
    public function setInvalidateCacheAt(\DateTime $invalidateCacheAt = null)
    {
        $this->invalidateCacheAt = $invalidateCacheAt;

        return $this;
    }

    /**
     * Get invalidateCacheAt.
     *
     * @return \DateTime
     */
    public function getInvalidateCacheAt()
    {
        return $this->invalidateCacheAt;
    }
}
