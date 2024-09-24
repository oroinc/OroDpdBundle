<?php

namespace Oro\Bundle\DPDBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    #[\Override]
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $repository = $doctrine->getManager()->getRepository(ShippingService::class);
        /** @var ShippingService $classicDpdShippingService */
        $classicDpdShippingService = $repository->findOneBy(['code' => ShippingService::CLASSIC_SERVICE_SUBSTR]);
        $referenceRepository->set('dpdClassicShippingService', $classicDpdShippingService);
    }
}
