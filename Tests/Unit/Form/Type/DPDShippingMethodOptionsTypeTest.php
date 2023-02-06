<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class DPDShippingMethodOptionsTypeTest extends FormIntegrationTestCase
{
    private DPDShippingMethodOptionsType $formType;

    protected function setUp(): void
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects(self::any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects(self::any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new DPDShippingMethodOptionsType($roundingService);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals(DPDShippingMethodOptionsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, array $expectedData, ?array $defaultData = null)
    {
        $form = $this->factory->create(DPDShippingMethodOptionsType::class, $defaultData);

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        return [
            'empty default data' => [
                'submittedData' => [
                    'handling_fee' => 10,
                ],
                'expectedData' => [
                    'handling_fee' => 10,
                ],
            ],
            'full data' => [
                'submittedData' => [
                    'handling_fee' => 10,
                ],
                'expectedData' => [
                    'handling_fee' => 10,
                ],
                'defaultData' => [
                    'handling_fee' => 12,
                ],
            ],
        ];
    }
}
