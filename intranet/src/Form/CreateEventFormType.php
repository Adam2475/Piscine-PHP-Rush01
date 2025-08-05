<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class CreateEventFormType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('name', TextType::class)
			->add('title', TextType::class)
			->add('maxParticipants', IntegerType::class, [
				'attr' => [
					'min' => 1,
				],
			])
			->add('description', TextareaType::class)
			->add('title', TextType::class)
    		->add('date', DateType::class, [
    	    'widget' => 'single_text',
    	    'html5' => true,
    	    'constraints' => [
    	        new GreaterThanOrEqual([
    	            'value' => (new \DateTime('tomorrow'))->setTime(0, 0),
    	            'message' => 'The event date must be at least tomorrow.',
    	        ])
    	    ],
    	    'attr' => [
    	        'min' => (new \DateTime('tomorrow'))->format('Y-m-d'),
    	    ],
    		])
			->add('startTime', TimeType::class, [
				'widget' => 'single_text',
			])
			->add('endTime', TimeType::class, [
				'widget' => 'single_text',
			])
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Event::class,
		]);
	}
}

?>