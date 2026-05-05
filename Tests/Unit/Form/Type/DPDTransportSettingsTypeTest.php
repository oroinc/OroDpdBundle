<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Form\Type\DPDTransportSettingsType;
use Oro\Bundle\DPDBundle\Validator\Constraints\RatesCsvFileValidator;
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
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UpdateIntegrationValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DPDTransportSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $symmetricCrypter;

    /** @var DPDTransportSettingsType */
    private $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->symmetricCrypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->transport->expects(self::any())
            ->method('getSettingsEntityFQCN')
            ->willReturn(DPDTransport::class);

        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects(self::any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects(self::any())
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
     * The parent implementation resolves the validation.yml path by searching for "Bundle" in the entity file path.
     * Since DPDBundle classes reside in "package/dpd/" (without "Bundle" in the directory structure),
     * the automatic resolution fails. This override provides the correct path explicitly.
     */
    #[\Override]
    protected function getConfigFile(string $class): ?string
    {
        if ($class === DPDTransport::class) {
            return dirname(__DIR__, 4) . '/Resources/config/validation.yml';
        }

        return parent::getConfigFile($class);
    }

    #[\Override]
    protected function getValidators(): array
    {
        return [
            'oro_dpd_remove_used_shipping_service_validator' => new UpdateIntegrationValidator(
                $this->createMock(IntegrationShippingMethodFactoryInterface::class),
                $this->createMock(ShippingMethodValidatorInterface::class),
                'applicableShippingServices'
            ),
            RatesCsvFileValidator::ALIAS => new RatesCsvFileValidator(
                $this->createMock(DoctrineHelper::class)
            ),
        ];
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EntityType::class => new EntityTypeStub([
                        1 => $this->getEntity(ShippingService::class, [
                            'code' => 'Classic',
                            'description' => 'DPD Classic',
                        ]),
                        2 => $this->getEntity(ShippingService::class, [
                            'code' => 'Express_830',
                            'description' => 'DPD Express 8:30',
                        ]),
                    ]),
                    WeightUnitSelectType::class => new EntityTypeStub([
                        'mg' => $this->getEntity(WeightUnit::class, ['code' => 'mg']),
                        'kg' => $this->getEntity(WeightUnit::class, ['code' => 'kg']),
                    ]),
                    new LocalizedPropertyType(),
                    LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    new LocalizedFallbackValueCollectionType($this->createMock(ManagerRegistry::class)),
                    new OroEncodedPlaceholderPasswordType($this->symmetricCrypter),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        DPDTransport $defaultData,
        array $submittedData,
        bool $isValid,
        DPDTransport $expectedData
    ) {
        if (count($submittedData) > 0) {
            $this->symmetricCrypter->expects($this->once())
                ->method('encryptData')
                ->with($submittedData['cloudUserToken'])
                ->willReturn($submittedData['cloudUserToken']);
        }

        $form = $this->factory->create(DPDTransportSettingsType::class, $defaultData, []);

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        self::assertEquals($isValid, $form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        /** @var ShippingService $expectedShippingService */
        $expectedShippingService = $this->getEntity(
            ShippingService::class,
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

    /**
     * @dataProvider submitWithLongValuesProvider
     */
    public function testSubmitWithTooLongValues(array $override): void
    {
        $this->symmetricCrypter->expects(self::any())
            ->method('encryptData')
            ->willReturnArgument(0);

        $submitData = array_replace_recursive([
            'labels' => ['values' => ['default' => 'first label']],
            'dpdTestMode' => true,
            'cloudUserId' => 'user',
            'cloudUserToken' => 'password',
            'unitOfWeight' => 'kg',
            'ratePolicy' => DPDTransport::FLAT_RATE_POLICY,
            'labelSize' => DPDTransport::PDF_A4_LABEL_SIZE,
            'labelStartPosition' => DPDTransport::UPPERLEFT_LABEL_START_POSITION,
            'applicableShippingServices' => [1],
        ], $override);

        $form = $this->factory->create(DPDTransportSettingsType::class, new DPDTransport());
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function submitWithLongValuesProvider(): array
    {
        return [
            'label too long' => [['labels' => ['values' => ['default' => str_repeat('a', 256)]]]],
            'cloudUserId too long' => [['cloudUserId' => str_repeat('a', 256)]],
            'cloudUserToken too long' => [['cloudUserToken' => str_repeat('a', 256)]],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => $this->transport->getSettingsEntityFQCN()]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals(DPDTransportSettingsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }
}
