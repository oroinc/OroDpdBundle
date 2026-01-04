<?php

namespace Oro\Bundle\DPDBundle\Form\Type;

use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatesCsvType extends AbstractType
{
    public const NAME = 'oro_dpd_rates_csv';

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $transportSettings = $form->getParent()->getData();
        $view->vars['rates_count'] =
            ($transportSettings instanceof DPDTransport) ? count($transportSettings->getRates()) : 0;
        $view->vars['download_csv_label'] = $options['download_csv_label'];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'download_csv_label' => 'oro.dpd.transport.rates_csv.download.label',
                'constraints' => [],
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return FileType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
