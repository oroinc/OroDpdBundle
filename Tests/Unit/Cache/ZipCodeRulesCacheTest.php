<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;

class ZipCodeRulesCacheTest extends \PHPUnit\Framework\TestCase
{
    private const PROCESSING_TIME_ERROR_VALUE = 3;

    /** @var ZipCodeRulesCache */
    private $cache;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->cache = new ZipCodeRulesCache($this->cacheProvider);
    }

    public function testContainsZipCodeRules()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $dpdSettings = $this->getDPDSettings($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(self::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(true);

        self::assertTrue($this->cache->containsZipCodeRules($key));
    }

    public function testContainsZipCodeRulesFalse()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $dpdSettings = $this->getDPDSettings($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(self::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(false);

        self::assertFalse($this->cache->containsZipCodeRules($key));
    }

    public function testFetchPrice()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $dpdSettings = $this->getDPDSettings($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(self::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(true);

        $zipCodeRulesResponse = $this->createMock(ZipCodeRulesResponse::class);

        $this->cacheProvider->expects(self::once())
            ->method('fetch')
            ->with($stringKey)
            ->willReturn($zipCodeRulesResponse);

        self::assertSame($zipCodeRulesResponse, $this->cache->fetchZipCodeRules($key));
    }

    public function testFetchPriceFalse()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $dpdSettings = $this->getDPDSettings($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(self::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(false);

        $this->cacheProvider->expects(self::never())
            ->method('fetch');

        self::assertFalse($this->cache->fetchZipCodeRules($key));
    }

    /**
     * @dataProvider saveZipCodeRulesDataProvider
     */
    public function testSaveZipCodeRules(string $invalidateCacheAtString, int $expectedLifetime)
    {
        $invalidateCacheAt = new \DateTime($invalidateCacheAtString);

        $dpdSettings = $this->getDPDSettings($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $zipCodeRulesResponse = $this->createMock(ZipCodeRulesResponse::class);

        $this->cacheProvider->expects(self::once())
            ->method('save')
            ->with($stringKey, $zipCodeRulesResponse)
            ->willReturnCallback(function ($actualKey, $price, $actualLifetime) use ($expectedLifetime) {
                self::assertLessThan(self::PROCESSING_TIME_ERROR_VALUE, abs($expectedLifetime - $actualLifetime));
            });

        self::assertEquals($this->cache, $this->cache->saveZipCodeRules($key, $zipCodeRulesResponse));
    }

    public function saveZipCodeRulesDataProvider(): array
    {
        return [
            'earlier than lifetime' => [
                'invalidateCacheAt' => '+3second',
                'expectedLifetime' => 3,
            ],
            'in past' => [
                'invalidateCacheAt' => '-1second',
                'expectedLifetime' => ZipCodeRulesCache::LIFETIME,
            ],
            'later than lifetime' => [
                'invalidateCacheAt' => '+24hour+10second',
                'expectedLifetime' => ZipCodeRulesCache::LIFETIME + 10,
            ],
        ];
    }

    private function getDPDSettings(\DateTime $invalidateCacheAt): DPDTransport
    {
        $transport = new DPDTransport();
        $transport->setInvalidateCacheAt($invalidateCacheAt);

        return $transport;
    }
}
