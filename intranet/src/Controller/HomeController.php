<?php
namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Enum\UserRole;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        $registrationFormView = null;

        if ($user && $user->getRole() === UserRole::ADMIN) {
            $newUser = new User();
            $form = $this->createForm(RegistrationFormType::class, $newUser);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // Generate confirmation token
                $token = bin2hex(random_bytes(32));
                $newUser->setConfirmationToken($token);

                // Mark as inactive by default
                $newUser->setIsActive(false);

                // Set role from form
                $newUser->setRole($form->get('role')->getData());

                $newUser->setCreated(new \DateTime());

                $em->persist($newUser);
                $em->flush();

                // Send confirmation email
                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@example.com', 'Intranet Admin'))
                    ->to($newUser->getEmail())
                    ->subject('Please confirm your account')
                    ->htmlTemplate('emails/confirmation.html.twig')
                    ->context([
                        'user' => $newUser,
                        'confirmationUrl' => $this->generateUrl(
                            'app_confirm_account',
                            ['token' => $newUser->getConfirmationToken()],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                    ]);

                $mailer->send($email);

                $this->addFlash('success', 'User registered successfully! A confirmation email has been sent.');
                return $this->redirectToRoute('homepage');
            }

            $registrationFormView = $form->createView();
        }

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'registrationForm' => $registrationFormView,
        ]);
    }
}
