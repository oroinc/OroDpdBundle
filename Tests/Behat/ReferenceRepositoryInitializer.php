<?php

namespace Oro\Bundle\DPDBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\DPDBundle\Entity\ShippingService as DPDShippingService;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroDPDBundle:ShippingService');
        /** @var DPDShippingService $germany */
        $germany = $repository->findOneBy(['code' => DPDShippingService::CLASSIC_SERVICE_SUBSTR]);
        $referenceRepository->set('dpdClassicShippingService', $germany);
    }
}
