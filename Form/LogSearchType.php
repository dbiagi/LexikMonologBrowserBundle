<?php

namespace Lexik\Bundle\MonologBrowserBundle\Form;

use Lexik\Bundle\MonologBrowserBundle\Model\LogRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class LogSearchType extends AbstractType {

    const NAME = 'search';

    /** @var LogRepository */
    private $logRepository;

    function __construct(LogRepository $logRepository) {
        $this->logRepository = $logRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('term', FormTypes\SearchType::class, [
                'required' => false,
            ])
            ->add('level', FormTypes\ChoiceType::class, [
                'translation_domain' => 'LexikMonologBrowserBundle',
                'choices'            => $this->logRepository->getLogsLevel(),
                'required'           => false,
                'placeholder'        => 'log.search.level',
            ])
            ->add('date_from', FormTypes\DateType::class, [
                'widget'   => 'single_text',
                'format'   => 'dd/MM/yyyy',
                'html5'    => false,
                'required' => false,
            ])
            ->add('time_from', FormTypes\TimeType::class, [
                'widget'   => 'single_text',
                'input'    => 'timestamp',
                'required' => false,
            ])
            ->add('date_to', FormTypes\DateType::class, [
                'widget'   => 'single_text',
                'format'   => 'dd/MM/yyyy',
                'html5'    => false,
                'required' => false,
            ])
            ->add('time_to', FormTypes\TimeType::class, [
                'widget'   => 'single_text',
                'input'    => 'timestamp',
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'csrf_protection' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return static::NAME;
    }

}
