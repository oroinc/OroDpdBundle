<?php

namespace Oro\Bundle\DPDBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DPDBundle\Entity\ShippingService as DPDShippingService;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
    {
        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroDPDBundle:ShippingService');
        /** @var DPDShippingService $classicDpdShippingService */
        $classicDpdShippingService = $repository->findOneBy(['code' => DPDShippingService::CLASSIC_SERVICE_SUBSTR]);
        $referenceRepository->set('dpdClassicShippingService', $classicDpdShippingService);
    }
}
