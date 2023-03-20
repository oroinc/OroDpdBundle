<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\DPDBundle\Handler\InvalidateCacheActionHandler;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class InvalidateCacheActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $upsPriceCache;

    /** @var InvalidateCacheActionHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->upsPriceCache = $this->createMock(AbstractAdapter::class);

        $this->handler = new InvalidateCacheActionHandler($this->upsPriceCache);
    }

    public function testHandle()
    {
        $dataStorage = new InvalidateCacheDataStorage([]);

        $this->upsPriceCache->expects(self::once())
            ->method('clear');

        $this->handler->handle($dataStorage);
    }
}
