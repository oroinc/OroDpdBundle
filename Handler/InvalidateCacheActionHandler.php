<?php

namespace Oro\Bundle\DPDBundle\Handler;

use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheActionHandlerInterface;
use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Handler for clearing DPD cache
 */
class InvalidateCacheActionHandler implements InvalidateCacheActionHandlerInterface
{
    private CacheInterface $zipCodeRulesCache;

    public function __construct(CacheInterface $zipCodeRulesCache)
    {
        $this->zipCodeRulesCache = $zipCodeRulesCache;
    }

    public function handle(DataStorageInterface $dataStorage)
    {
        $this->zipCodeRulesCache->clear();
    }
}
