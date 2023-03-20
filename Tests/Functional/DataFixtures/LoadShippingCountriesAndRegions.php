<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Symfony\Component\Yaml\Yaml;

class LoadShippingCountriesAndRegions extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getShippingCountriesData() as $reference => $data) {
            $entity = new Country($data['iso2']);
            $entity
                ->setIso3Code($data['iso3'])
                ->setName($reference);

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        foreach ($this->getShippingRegionsData() as $reference => $data) {
            $entity = new Region($data['combinedCode']);
            $entity
                ->setCountry($this->getReference($data['countryRef']))
                ->setCode($data['code'])
                ->setName($reference);

            $manager->persist($entity);

            $this->setReference($reference, $entity);
        }

        $manager->flush();
    }

    private function getShippingCountriesData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_countries.yml'));
    }

    private function getShippingRegionsData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/shipping_regions.yml'));
    }
}
