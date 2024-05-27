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
    /**
     * Used in conditions of operation "oro_dpd_integration_invalidate_cache"
     */
    public const PARAM_TRANSPORT_ID = 'transportId';

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
