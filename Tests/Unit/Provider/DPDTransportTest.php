<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDTransportEntity;
use Oro\Bundle\DPDBundle\Form\Type\DPDTransportSettingsType;
use Oro\Bundle\DPDBundle\Model\SetOrderRequest;
use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Model\ZipCodeRulesResponse;
use Oro\Bundle\DPDBundle\Provider\DPDTransport;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;

class DPDTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var RestClientFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $clientFactory;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $symmetricCrypter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var DPDTransport */
    private $transport;

    #[\Override]
    protected function setUp(): void
    {
        $this->client = $this->createMock(RestClientInterface::class);
        $this->clientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->symmetricCrypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->transport = new DPDTransport($this->logger, $this->symmetricCrypter);
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testGetLabel()
    {
        self::assertEquals('oro.dpd.transport.label', $this->transport->getLabel());
    }

    public function testGetSettingsFormType()
    {
        self::assertEquals(DPDTransportSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testGetSettingsEntityFQCN()
    {
        self::assertEquals(DPDTransportEntity::class, $this->transport->getSettingsEntityFQCN());
    }

    public function testGetSetOrderResponse()
    {
        $setOrderRequest = $this->createMock(SetOrderRequest::class);

        $integration = new Channel();
        $transportEntity = new DPDTransportEntity();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(self::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $restResponse = $this->createMock(RestResponseInterface::class);

        $json = '{
                   "Version": 100,
                   "Ack": true,
                   "Language": "en_EN",
                   "TimeStamp": "2017-01-06T14:22:32.6175888+01:00",
                   "LabelResponse": {
                      "LabelPDF": "base 64 encoded pdf",
                      "LabelDataList": {
                         "LabelData": {
                            "YourInternalID": "an ID",
                            "ParcelNo": "parcel number"
                         }
                      }
                   }
                }';
        $jsonArr = json_decode($json, true);

        $restResponse->expects(self::once())
            ->method('json')
            ->willReturn($jsonArr);

        $this->client->expects(self::once())
            ->method('post')
            ->willReturn($restResponse);

        $setOrderResponse = $this->transport->getSetOrderResponse($setOrderRequest, $transportEntity);
        self::assertInstanceOf(SetOrderResponse::class, $setOrderResponse);
    }

    public function testGetSetOrderResponseRestException()
    {
        $setOrderRequest = $this->createMock(SetOrderRequest::class);

        $integration = new Channel();
        $transportEntity = new DPDTransportEntity();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(self::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $this->client->expects(self::once())
            ->method('post')
            ->willThrowException(new RestException('404'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                sprintf(
                    'setOrder REST request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    '404'
                )
            );
        $this->transport->getSetOrderResponse($setOrderRequest, $transportEntity);
    }

    public function testGetZipCodeRulesResponse()
    {
        $integration = new Channel();
        $transportEntity = new DPDTransportEntity();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(self::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $restResponse = $this->createMock(RestResponseInterface::class);

        $json = '{
                   "Version": 100,
                   "Ack": true,
                   "Language": "en_EN",
                   "TimeStamp": "2017-01-06T14:22:32.6175888+01:00",
                   "ZipCodeRules": {
                      "Country": "a country",
                      "ZipCode": "zip code",
                      "NoPickupDays": "01.01.2017,18.04.2017,25.12.2017",
                      "ExpressCutOff": "12:00",
                      "ClassicCutOff": "08:00",
                      "PickupDepot": "0197",
                      "State": "a state"
                   }
                }';
        $jsonArr = json_decode($json, true);

        $restResponse->expects(self::once())
            ->method('json')
            ->willReturn($jsonArr);

        $this->client->expects(self::once())
            ->method('get')
            ->willReturn($restResponse);

        $zipCodeRulesResponse = $this->transport->getZipCodeRulesResponse($transportEntity);
        self::assertInstanceOf(ZipCodeRulesResponse::class, $zipCodeRulesResponse);
    }

    public function testGetZipCodeRulesResponseRestException()
    {
        $integration = new Channel();
        $transportEntity = new DPDTransportEntity();
        $integration->setTransport($transportEntity);

        $this->clientFactory->expects(self::once())
            ->method('createRestClient')
            ->willReturn($this->client);

        $this->client->expects(self::once())
            ->method('get')
            ->willThrowException(new RestException('404'));

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                sprintf(
                    'zipCodeRules REST request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    '404'
                )
            );
        $this->transport->getZipCodeRulesResponse($transportEntity);
    }
}
