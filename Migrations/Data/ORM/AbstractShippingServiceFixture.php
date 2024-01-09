<?php

namespace Oro\Bundle\DPDBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\ShippingService;

/**
 * Abstract class for shipping services fixtures.
 */
abstract class AbstractShippingServiceFixture extends AbstractFixture
{
    protected function addUpdateShippingServices(
        ObjectManager $manager,
        array $shippingServices,
        $setReferences = false
    ) {
        $repository = $manager->getRepository(ShippingService::class);
        foreach ($shippingServices as $ref => $shippingService) {
            $entity = $repository->find(['code' => $shippingService['code']]);
            if (!$entity) {
                $entity = new ShippingService();
            }

            $entity->setCode($shippingService['code']);
            $entity->setDescription($shippingService['description']);
            $entity->setExpressService((bool) $shippingService['express']);
            $manager->persist($entity);

            if ($setReferences) {
                $this->setReference($ref, $entity);
            }
        }
    }
}
