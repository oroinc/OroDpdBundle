<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\DPDBundle\Cache\ZipCodeRulesCache;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesRequest;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Component\Testing\Unit\EntityTrait;

class ZipCodeRulesCacheTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @internal
     */
    const PROCESSING_TIME_ERROR_VALUE = 3;

    /**
     * @var ZipCodeRulesCache
     */
    protected $cache;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cacheProvider;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->getMockBuilder(CacheProvider::class)
            ->setMethods(['fetch', 'contains', 'save', 'deleteAll'])->getMockForAbstractClass();

        $this->cache = new ZipCodeRulesCache($this->cacheProvider);
    }

    public function testContainsZipCodeRules()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $dpdSettings = $this->getDPDSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(true);

        static::assertTrue($this->cache->containsZipCodeRules($key));
    }

    public function testContainsZipCodeRulesFalse()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $dpdSettings = $this->getDPDSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(false);

        static::assertFalse($this->cache->containsZipCodeRules($key));
    }

    public function testFetchPrice()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $dpdSettings = $this->getDPDSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(true);

        $zipCodeRulesResponse = $this->getZipCodeRulesResponseMock();

        $this->cacheProvider->expects(static::once())
            ->method('fetch')
            ->with($stringKey)
            ->willReturn($zipCodeRulesResponse);

        static::assertSame($zipCodeRulesResponse, $this->cache->fetchZipCodeRules($key));
    }

    public function testFetchPriceFalse()
    {
        $invalidateCacheAt = new \DateTime('+30 minutes');

        $dpdSettings = $this->getDPDSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with($stringKey)
            ->willReturn(false);

        $this->cacheProvider->expects(static::never())
            ->method('fetch');

        static::assertFalse($this->cache->fetchZipCodeRules($key));
    }

    /**
     * @dataProvider saveZipCodeRulesDataProvider
     *
     * @param string $invalidateCacheAtString
     * @param string $expectedLifetime
     */
    public function testSaveZipCodeRules($invalidateCacheAtString, $expectedLifetime)
    {
        $invalidateCacheAt = new \DateTime($invalidateCacheAtString);

        $dpdSettings = $this->getDPDSettingsMock($invalidateCacheAt);

        $key = $this->cache->createKey($dpdSettings, new ZipCodeRulesRequest());

        $stringKey = $key->generateKey().'_'.$invalidateCacheAt->getTimestamp();

        $zipCodeRulesResponse = $this->getZipCodeRulesResponseMock();

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with($stringKey, $zipCodeRulesResponse)
            ->will($this->returnCallback(function ($actualKey, $price, $actualLifetime) use ($expectedLifetime) {
                static::assertLessThan(self::PROCESSING_TIME_ERROR_VALUE, abs($expectedLifetime - $actualLifetime));
            }));

        static::assertEquals($this->cache, $this->cache->saveZipCodeRules($key, $zipCodeRulesResponse));
    }

    /**
     * @return array
     */
    public function saveZipCodeRulesDataProvider()
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

    /**
     * @param \DateTime $invalidateCacheAt
     *
     * @return DPDTransport|object
     */
    private function getDPDSettingsMock(\DateTime $invalidateCacheAt): DPDTransport
    {
        return $this->getEntity(DPDTransport::class, [
            'invalidateCacheAt' => $invalidateCacheAt,
        ]);
    }

    /**
     * @return ZipCodeRulesResponse|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getZipCodeRulesResponseMock()
    {
        return $this->createMock(ZipCodeRulesResponse::class);
    }
}
