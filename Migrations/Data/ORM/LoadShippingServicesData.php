<?php

namespace Oro\Bundle\DPDBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\ShippingService;

/**
 * Loads shipping services.
 */
class LoadShippingServicesData extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(ShippingService::class);
        foreach ($this->getData() as $data) {
            $service = $repository->find(['code' => $data['code']]);
            if (!$service) {
                $service = new ShippingService();
            }

            $service->setCode($data['code']);
            $service->setDescription($data['description']);
            $service->setExpressService($data['express']);
            $manager->persist($service);
        }
        $manager->flush();
    }

    private function getData(): array
    {
        return [
            [
                'code' => 'Classic',
                'description' => 'DPD Classic',
                'express' => false
            ]
        ];
    }
}
