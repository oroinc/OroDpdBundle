<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

class LoadRates extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getRatesData() as $reference => $data) {
            $entity = new Rate();

            $transport = $this->getReference($data['transport']);
            $entity->setTransport($transport);

            $shipService = $this->getReference($data['shippingService']);
            $entity->setShippingService($shipService);

            $country = $this->getReference($data['country']);
            $entity->setCountry($country);

            if (array_key_exists('region', $data)) {
                $region = $this->getReference($data['region']);
                $entity->setRegion($region);
            }

            $this->setEntityPropertyValues($entity, $data, ['transport', 'shippingService', 'country', 'region']);
            $manager->persist($entity);
            $this->setReference($reference, $entity);
        }
        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadTransports::class,
            LoadShippingCountriesAndRegions::class
        ];
    }

    private function getRatesData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_rates.yml'));
    }

    private function setEntityPropertyValues(object $entity, array $data, array $excludeProperties = []): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $property => $value) {
            if (\in_array($property, $excludeProperties, true)) {
                continue;
            }
            $propertyAccessor->setValue($entity, $property, $value);
        }
    }
}
