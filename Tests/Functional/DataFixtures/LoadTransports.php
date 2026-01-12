<?php

namespace Oro\Bundle\DPDBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

class LoadTransports extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getTransportsData() as $reference => $data) {
            $entity = new DPDTransport();
            foreach ($data['applicableShippingServices'] as $shipServiceRef) {
                /** @var ShippingService $shipService */
                $shipService = $this->getReference($shipServiceRef);
                $entity->addApplicableShippingService($shipService);
            }
            $this->setEntityPropertyValues($entity, $data, ['reference', 'applicableShippingServices']);
            $manager->persist($entity);
            $this->setReference($reference, $entity);
        }
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadShippingServices::class];
    }

    private function getTransportsData(): array
    {
        return Yaml::parse(file_get_contents(__DIR__ . '/data/transports.yml'));
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
