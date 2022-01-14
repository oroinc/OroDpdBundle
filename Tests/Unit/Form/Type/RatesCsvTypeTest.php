<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DPDBundle\Form\Type\RatesCsvType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RatesCsvTypeTest extends FormIntegrationTestCase
{
    /** @var RatesCsvType */
    protected $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new RatesCsvType();
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(RatesCsvType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $defaultData = null)
    {
        if ($submittedData === 'file') {
            $submittedData = $this->createUploadedFileMock('filename', 'original_filename', true);
        }
        if ($expectedData === 'file') {
            $expectedData = $this->createUploadedFileMock('filename', 'original_filename', true);
        }

        $form = $this->factory->createBuilder(RatesCsvType::class, $defaultData)
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm();

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        static::assertTrue($form->isValid());
        static::assertTrue($form->isSynchronized());
        static::assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty default data' => [
                'submittedData' => null,
                'expectedData' => null,
            ],
            'full data' => [
                'submittedData' => 'file',
                'expectedData' => 'file',
                'defaultData' => null,
            ],
        ];
    }

    /**
     * @param $name
     * @param $originalName
     * @param $valid
     * @return \PHPUnit\Framework\MockObject\MockObject|UploadedFile
     */
    private function createUploadedFileMock($name, $originalName, $valid)
    {
        $file = $this
            ->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $file
            ->method('getBasename')
            ->willReturn($name);

        $file
            ->method('getClientOriginalName')
            ->willReturn($originalName);

        $file
            ->method('isValid')
            ->willReturn($valid);

        return $file;
    }
}
