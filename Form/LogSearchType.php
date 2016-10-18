<?php

namespace Lexik\Bundle\MonologBrowserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class LogSearchType extends AbstractType {

    const NAME = 'search';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('term', FormTypes\SearchType::class, array(
                'required' => false
            ))
            ->add('level', FormTypes\ChoiceType::class, array(
                'translation_domain' => 'LexikMonologBrowserBundle',
                'choices' => $options['log_levels'],
                'required' => false,
            ))
            ->add('date_from', FormTypes\DateType::class, array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false
            ))
            ->add('time_from', FormTypes\TimeType::class, [
                'widget' => 'single_text',
                'input'  => 'timestamp',
                'required' => false
            ])
            ->add('date_to', FormTypes\DateType::class, array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,
            ))
            ->add('time_to', FormTypes\TimeType::class, [
                'widget' => 'single_text',
                'input'  => 'timestamp',
                'required' => false
            ])
        ;

        $qb = $options['query_builder'];
        $convertDateToDatabaseValue = function(\DateTime $date) use ($qb) {
            return Type::getType('datetime')->convertToDatabaseValue($date, $qb->getConnection()->getDatabasePlatform());
        };

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($qb, $convertDateToDatabaseValue) {
            $data = $event->getData();
            
            if (null !== $data['term']) {
                $qb->andWhere('l.message LIKE :message')
                    ->setParameter('message', '%' . str_replace(' ', '%', $data['term']) . '%')
                    ->orWhere('l.channel LIKE :channel')
                    ->setParameter('channel', $data['term'] . '%');
            }

            if (null !== $data['level']) {
                $qb->andWhere('l.level = :level')
                    ->setParameter('level', $data['level']);
            }
            
            if ($data['date_from'] instanceof \DateTime) {
                if(isset($data['time_from']) && $data['time_from']){
                    $data['date_from']->setTimestamp($data['date_from']->getTimestamp() + $data['time_from']);
                }
                $qb->andWhere('l.datetime >= :date_from')
                    ->setParameter('date_from', $convertDateToDatabaseValue($data['date_from']));
            }

            if ($data['date_to'] instanceof \DateTime) {
                if(isset($data['time_to']) && $data['time_to']){
                    $data['date_to']->setTimestamp($data['date_to']->getTimestamp() + $data['time_to']);
                }
                $qb->andWhere('l.datetime <= :date_to')
                    ->setParameter('date_to', $convertDateToDatabaseValue($data['date_to']));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver
            ->setRequired(array(
                'query_builder',
            ))
            ->setDefaults(array(
                'log_levels' => array(),
                'csrf_protection' => false,
            ))
            ->setAllowedTypes('log_levels', 'array')
            ->setAllowedTypes('query_builder', '\Doctrine\DBAL\Query\QueryBuilder')

        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver
            ->setRequired(array(
                'query_builder',
            ))
            ->setDefaults(array(
                'log_levels' => array(),
                'csrf_protection' => false,
            ))
            ->setAllowedTypes('search', array(
                'log_levels' => 'array',
                'query_builder' => '\Doctrine\DBAL\Query\QueryBuilder',
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return static::NAME;
    }

    public function getBlockPrefix() {
        return static::NAME;
    }

}
