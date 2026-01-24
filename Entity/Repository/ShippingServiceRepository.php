<?php

namespace Oro\Bundle\DPDBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository for DPD shipping services.
 */
class ShippingServiceRepository extends EntityRepository
{
    /**
     * @return string[]
     */
    public function getAllShippingServiceCodes()
    {
        $qb = $this->createQueryBuilder('shippingService')
            ->select('shippingService.code');
        return array_column($qb->getQuery()->getResult(), 'code');
    }
}
