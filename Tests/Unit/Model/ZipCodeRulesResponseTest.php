<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use InvalidArgumentException;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;

class ZipCodeRulesResponseTest extends \PHPUnit\Framework\TestCase
{
    /** @var ZipCodeRulesResponse */
    protected $model;

    protected function setUp(): void
    {
        $values = [
            'Ack' => true,
            'TimeStamp' => '2017-02-06T17:35:54.978392+01:00',
            'ZipCodeRules' => [
                'Country' => 'DE',
                'ZipCode' => '97318',
                'NoPickupDays' => '01.01.2017,01.02.2017,01.03.2017',
                'ExpressCutOff' => '12:00',
                'ClassicCutOff' => '11:00',
                'PickupDepot' => '1256',
                'State' => 'BY',
            ],
        ];
        $this->model = new ZipCodeRulesResponse();
        $this->model->parse($values);
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    public function testIsNoPickUpDay()
    {
        self::assertTrue($this->model->isNoPickupDay(new \DateTime('01.01.2017')));
        self::assertFalse($this->model->isNoPickupDay(new \DateTime('02.01.2017')));
    }

    public function testGetNoPickUpDays()
    {
        self::assertEquals(
            [
                new \DateTime('01.01.2017'), new \DateTime('01.02.2017'), new \DateTime('01.03.2017'),
            ],
            $this->model->getNoPickupDays()
        );
    }

    /**
     * @dataProvider evaluateThrowingDataProvider
     */
    public function testEvaluateThrowing(array $values)
    {
        $this->expectException(InvalidArgumentException::class);
        $response = new ZipCodeRulesResponse($values);
        $this->model->parse($values);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function evaluateThrowingDataProvider()
    {
        return [
            'no_zip_code_rules_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-01-06',
                ],
            ],
            'no_country_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-01-06',
                    'ZipCodeRules' => [
                        'ZipCode' => '97318',
                        'NoPickupDays' => '01.01.2017,01.02.2017,01.03.2017',
                        'ExpressCutOff' => '12:00',
                        'ClassicCutOff' => '11:00',
                        'PickupDepot' => '1256',
                        'State' => 'BY',
                    ],
                ],
            ],
            'no_zipcode_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-01-06',
                    'ZipCodeRules' => [
                        'Country' => 'DE',
                        'NoPickupDays' => '01.01.2017,01.02.2017,01.03.2017',
                        'ExpressCutOff' => '12:00',
                        'ClassicCutOff' => '11:00',
                        'PickupDepot' => '1256',
                        'State' => 'BY',
                    ],
                ],
            ],
            'no_no_pickup_days_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-01-06',
                    'ZipCodeRules' => [
                        'Country' => 'DE',
                        'ZipCode' => '97318',
                        'ExpressCutOff' => '12:00',
                        'ClassicCutOff' => '11:00',
                        'PickupDepot' => '1256',
                        'State' => 'BY',
                    ],
                ],
            ],
            'no_express_cutoff_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-01-06',
                    'ZipCodeRules' => [
                        'Country' => 'DE',
                        'ZipCode' => '97318',
                        'NoPickupDays' => '01.01.2017,01.02.2017,01.03.2017',
                        'ClassicCutOff' => '11:00',
                        'PickupDepot' => '1256',
                        'State' => 'BY',
                    ],
                ],
            ],
            'no_classic_cutoff_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-01-06',
                    'ZipCodeRules' => [
                        'Country' => 'DE',
                        'ZipCode' => '97318',
                        'NoPickupDays' => '01.01.2017,01.02.2017,01.03.2017',
                        'ExpressCutOff' => '12:00',
                        'PickupDepot' => '1256',
                        'State' => 'BY',
                    ],
                ],
            ],
            'no_pickup_depot_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-01-06',
                    'ZipCodeRules' => [
                        'Country' => 'DE',
                        'ZipCode' => '97318',
                        'NoPickupDays' => '01.01.2017,01.02.2017,01.03.2017',
                        'ExpressCutOff' => '12:00',
                        'ClassicCutOff' => '11:00',
                        'State' => 'BY',
                    ],
                ],
            ],
            'no_state_response' => [
                'values' => [
                    'Ack' => true,
                    'TimeStamp' => '2017-01-06',
                    'ZipCodeRules' => [
                        'Country' => 'DE',
                        'ZipCode' => '97318',
                        'NoPickupDays' => '01.01.2017,01.02.2017,01.03.2017',
                        'ExpressCutOff' => '12:00',
                        'ClassicCutOff' => '11:00',
                        'PickupDepot' => '1256',
                    ],
                ],
            ],
        ];
    }
}
