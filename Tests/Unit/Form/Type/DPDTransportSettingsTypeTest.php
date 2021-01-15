<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Form\Type\DPDTransportSettingsType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Form\Type\WeightUnitSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class DPDTransportSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    const DATA_CLASS = 'Oro\Bundle\DPDBundle\Entity\DPDTransport';

    /**
     * @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $transport;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /**
     * @var DPDTransportSettingsType
     */
    protected $formType;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $symmetricCrypter;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->transport->expects(static::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(static::DATA_CLASS);

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->symmetricCrypter = $this
            ->getMockBuilder(SymmetricCrypterInterface::class)
            ->getMock();

        /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject $roundingService */
        $roundingService = $this->getMockForAbstractClass(RoundingServiceInterface::class);
        $roundingService->expects(static::any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects(static::any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new DPDTransportSettingsType(
            $this->transport,
            $this->doctrineHelper,
            $roundingService
        );

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityTypeStub(
            [
                1 => $this->getEntity(
                    'Oro\Bundle\DPDBundle\Entity\ShippingService',
                    [
                        'code' => 'Classic',
                        'description' => 'DPD Classic',
                    ]
                ),
                2 => $this->getEntity(
                    'Oro\Bundle\DPDBundle\Entity\ShippingService',
                    [
                        'code' => 'Express_830',
                        'description' => 'DPD Express 8:30',
                    ]
                ),
            ],
            'entity'
        );

        $unitOfWeightEntity = new EntityTypeStub(
            [
                'mg' => $this->getEntity(
                    'Oro\Bundle\ShippingBundle\Entity\WeightUnit',
                    ['code' => 'mg']
                ),
                'kg' => $this->getEntity(
                    'Oro\Bundle\ShippingBundle\Entity\WeightUnit',
                    ['code' => 'kg']
                ),
            ],
            WeightUnitSelectType::NAME
        );
        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->getMockBuilder('Doctrine\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $localizedFallbackValue = new LocalizedFallbackValueCollectionType($registry);

        return [
            new PreloadedExtension(
                [
                    DPDTransportSettingsType::class => $this->formType,
                    EntityType::class => $entityType,
                    WeightUnitSelectType::class => $unitOfWeightEntity,
                    LocalizedPropertyType::class => new LocalizedPropertyType(),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    LocalizedFallbackValueCollectionType::class => $localizedFallbackValue,
                    OroEncodedPlaceholderPasswordType::class
                        => new OroEncodedPlaceholderPasswordType($this->symmetricCrypter),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @param DPDTransport       $defaultData
     * @param array|DPDTransport $submittedData
     * @param bool               $isValid
     * @param DPDTransport       $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        DPDTransport $defaultData,
        array $submittedData,
        $isValid,
        DPDTransport $expectedData
    ) {
        if (count($submittedData) > 0) {
            $this->symmetricCrypter
                ->expects($this->once())
                ->method('encryptData')
                ->with($submittedData['cloudUserToken'])
                ->willReturn($submittedData['cloudUserToken']);
        }

        $form = $this->factory->create(DPDTransportSettingsType::class, $defaultData, []);

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        static::assertEquals($isValid, $form->isValid());
        static::assertTrue($form->isSynchronized());
        static::assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        /** @var ShippingService $expectedShippingService */
        $expectedShippingService = $this->getEntity(
            'Oro\Bundle\DPDBundle\Entity\ShippingService',
            [
                'code' => 'Classic',
                'description' => 'DPD Classic',
            ]
        );

        return [
            'service without value' => [
                'defaultData' => new DPDTransport(),
                'submittedData' => [],
                'isValid' => false,
                'expectedData' => (new DPDTransport())
                    ->addLabel(new LocalizedFallbackValue()),
            ],
            'service with value' => [
                'defaultData' => new DPDTransport(),
                'submittedData' => [
                    'labels' => [
                        'values' => ['default' => 'first label'],
                    ],
                    'dpdTestMode' => true,
                    'cloudUserId' => 'user',
                    'cloudUserToken' => 'password',
                    'unitOfWeight' => 'kg',
                    'ratePolicy' => DPDTransport::FLAT_RATE_POLICY,
                    'flatRatePriceValue' => null,
                    'ratesCsv' => null,
                    'labelSize' => DPDTransport::PDF_A4_LABEL_SIZE,
                    'labelStartPosition' => DPDTransport::UPPERLEFT_LABEL_START_POSITION,
                    'applicableShippingServices' => [1],
                ],
                'isValid' => true,
                'expectedData' => (new DPDTransport())
                    ->setDPDTestMode(true)
                    ->setCloudUserId('user')
                    ->setCloudUserToken('password')
                    ->setUnitOfWeight((new WeightUnit())->setCode('kg'))
                    ->setRatePolicy(DPDTransport::FLAT_RATE_POLICY)
                    ->setLabelSize(DPDTransport::PDF_A4_LABEL_SIZE)
                    ->setLabelStartPosition(DPDTransport::UPPERLEFT_LABEL_START_POSITION)
                    ->addApplicableShippingService($expectedShippingService)
                    ->addLabel((new LocalizedFallbackValue())->setString('first label')),
            ],
        ];
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with([
                'data_class' => $this->transport->getSettingsEntityFQCN(),
            ]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(DPDTransportSettingsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }
}
