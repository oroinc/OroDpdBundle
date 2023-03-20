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
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

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
     * {@inheritDoc}
     */
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
            new ValidatorExtension(Validation::createValidator()),
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
