<?php
namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\UserRole;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'First name should not be blank']),
                    new Assert\Length(['max' => 50]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Last name should not be blank']),
                    new Assert\Length(['max' => 60]),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email should not be blank']),
                    new Assert\Email(['message' => 'Please enter a valid email address']),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'User' => UserRole::USER->value,
                    'Admin' => UserRole::ADMIN->value,
                ],
                'expanded' => false,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'label' => 'Role',
            ])
            ->add('image', FileType::class, [
                'label' => 'Profile Image (JPG or PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPG or PNG image',
                    ])
                ],
            ]);

        // Add transformer to convert between string and UserRole enum
        $builder->get('role')
            ->addModelTransformer(new CallbackTransformer(
                // Model (UserRole) to view (string)
                fn(?UserRole $role) => $role ? $role->value : null,
                // View (string) to model (UserRole)
                fn(?string $roleString) => $roleString ? UserRole::from($roleString) : null
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
