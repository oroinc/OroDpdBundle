<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Migrations\Data\ORM\AbstractShippingServiceFixture;
use Symfony\Component\Yaml\Yaml;

class LoadShippingServices extends AbstractShippingServiceFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->addUpdateShippingServices($manager, $this->getShippingServicesData(), true);
        $manager->flush();
    }

    private function getShippingServicesData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_services.yml'));
    }
}
