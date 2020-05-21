<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ZipCodeRulesRequestTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /**
     * @var ZipCodeRulesRequest
     */
    protected $model;

    protected function setUp(): void
    {
        $this->model = new ZipCodeRulesRequest();
    }

    protected function tearDown(): void
    {
        unset($this->model);
    }

    public function testAccessors()
    {
    }
}
