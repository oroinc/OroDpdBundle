<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Symfony\Component\Yaml\Yaml;

class LoadShippingServices extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $shippingServices = Yaml::parse(file_get_contents(__DIR__.'/data/shipping_services.yml'));
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

            $this->setReference($ref, $entity);
        }
        $manager->flush();
    }
}
