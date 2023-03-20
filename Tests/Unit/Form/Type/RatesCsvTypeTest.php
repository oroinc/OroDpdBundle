<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DPDBundle\Form\Type\RatesCsvType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class RatesCsvTypeTest extends FormIntegrationTestCase
{
    private RatesCsvType $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new RatesCsvType();
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals(RatesCsvType::NAME, $this->formType->getBlockPrefix());
    }

    public function testSubmitForNullData()
    {
        $form = $this->factory->createBuilder(RatesCsvType::class)
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm();

        self::assertNull($form->getData());

        $form->submit(null);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertNull($form->getData());
    }

    public function testSubmit()
    {
        $submittedData = $this->getUploadedFile('filename', 'original_filename', true);
        $expectedData = $this->getUploadedFile('filename', 'original_filename', true);

        $form = $this->factory->createBuilder(RatesCsvType::class)
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm();

        self::assertNull($form->getData());

        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    private function getUploadedFile(string $name, string $originalName, bool $valid): UploadedFile
    {
        $file = $this->createMock(UploadedFile::class);
        $file->expects(self::any())
            ->method('getBasename')
            ->willReturn($name);
        $file->expects(self::any())
            ->method('getClientOriginalName')
            ->willReturn($originalName);
        $file->expects(self::any())
            ->method('isValid')
            ->willReturn($valid);

        return $file;
    }
}
