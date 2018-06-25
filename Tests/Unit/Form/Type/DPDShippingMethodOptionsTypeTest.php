<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class DPDShippingMethodOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var DPDShippingMethodOptionsType */
    protected $formType;

    protected function setUp()
    {
        /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject $roundingService */
        $roundingService = $this->getMockForAbstractClass(RoundingServiceInterface::class);
        $roundingService->expects(static::any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects(static::any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new DPDShippingMethodOptionsType($roundingService);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    DPDShippingMethodOptionsType::class => $this->formType
                ],
                []
            ),
        ];
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(DPDShippingMethodOptionsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create(DPDShippingMethodOptionsType::class, $defaultData);

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        static::assertTrue($form->isValid());
        static::assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
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
