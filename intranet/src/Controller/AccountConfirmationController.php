<?php
// src/Controller/AccountConfirmationController.php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class AccountConfirmationController extends AbstractController
{
    #[Route('/confirm/{token}', name: 'app_confirm_account')]
    public function confirmAccount(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired confirmation token.');
            return $this->redirectToRoute('homepage');
        }

        // Form per settare la password
        $form = $this->createFormBuilder()
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
                'invalid_message' => 'The password fields must match.',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Password should not be blank']),
                    new Length(['min' => 6, 'minMessage' => 'Password must be at least {{ limit }} characters']),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($hashedPassword);
            $user->setIsActive(true);
            $user->setConfirmationToken(null);

            $em->flush();

            $this->addFlash('success', 'Your account has been activated! You can now log in.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/set_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
