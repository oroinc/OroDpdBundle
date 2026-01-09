<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DPDBundle\Condition\ShippedWithDPD;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

class ShippedWithDPDTest extends \PHPUnit\Framework\TestCase
{
    private ShippedWithDPD $condition;

    protected function setUp(): void
    {
        $dpdShippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $dpdShippingMethodProvider->expects($this->any())
            ->method('hasShippingMethod')
            ->willReturnMap([
                ['dpd', true],
                ['no_dpd', false],
            ]);

        $this->condition = new ShippedWithDPD($dpdShippingMethodProvider);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals('shipped_with_dpd', $this->condition->getName());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, array $context, bool $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function evaluateDataProvider(): array
    {
        return [
            'dpd_shipping_method' => [
                'options' => [new PropertyPath('foo')],
                'context' => ['foo' => 'dpd'],
                'expectedResult' => true,
            ],
            'no_dpd_shipping_method' => [
                'options' => [new PropertyPath('foo')],
                'context' => ['foo' => 'no_dpd'],
                'expectedResult' => false,
            ],
        ];
    }

    public function testAddError()
    {
        $context = ['foo' => 'no_dpd'];
        $options = [new PropertyPath('foo')];

        $this->condition->initialize($options);
        $message = 'Error message.';
        $this->condition->setMessage($message);

        $errors = new ArrayCollection();

        $this->assertFalse($this->condition->evaluate($context, $errors));

        $this->assertCount(1, $errors);
        $this->assertEquals(
            ['message' => $message, 'parameters' => ['{{ value }}' => 'no_dpd']],
            $errors->get(0)
        );
    }

    public function testInitializeFailsWhenEmptyOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 element, but 0 given.');

        $this->condition->initialize([]);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, ?string $message, array $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'options' => ['value'],
                'message' => null,
                'expected' => [
                    '@shipped_with_dpd' => [
                        'parameters' => [
                            'value',
                        ],
                    ],
                ],
            ],
            [
                'options' => ['value'],
                'message' => 'Test',
                'expected' => [
                    '@shipped_with_dpd' => [
                        'message' => 'Test',
                        'parameters' => [
                            'value',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(array $options, ?string $message, string $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'options' => ['value'],
                'message' => null,
                'expected' => '$factory->create(\'shipped_with_dpd\', [\'value\'])',
            ],
            [
                'options' => ['value'],
                'message' => 'Test',
                'expected' => '$factory->create(\'shipped_with_dpd\', [\'value\'])->setMessage(\'Test\')',
            ],
            [
                'options' => [new PropertyPath('foo[bar].baz')],
                'message' => null,
                'expected' => '$factory->create(\'shipped_with_dpd\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath('
                    . '\'foo[bar].baz\', [\'foo\', \'bar\', \'baz\'], [false, true, false], [false, false, false])'
                    . '])',
            ],
        ];
    }
}
