<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class ForgotPasswordController extends AbstractController
{
   #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'Your email address',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your email']),
                    new Email(['message' => 'Invalid email address']),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Generate and store token
                $token = bin2hex(random_bytes(32));
                $user->setConfirmationToken($token);
                $em->flush();

                // Send reset email
                $emailMessage = (new TemplatedEmail())
                    ->from(new Address('no-reply@example.com', 'Intranet Admin'))
                    ->to($user->getEmail())
                    ->subject('Reset your password')
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context([
                        'confirmationUrl' => $this->generateUrl(
                            'app_confirm_account',
                            ['token' => $token],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        'user' => $user,
                    ]);
                $mailer->send($emailMessage);
            }

            // Always show this, even if user wasn't found
            $this->addFlash('success', 'If an account exists for this email, a password reset link has been sent.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
