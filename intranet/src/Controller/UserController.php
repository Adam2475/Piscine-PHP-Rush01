<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Enum\UserRole;
use App\Entity\User;
use App\Form\RegistrationFormType;

final class UserController extends AbstractController
{
    #[Route('/userpage', name: 'userpage')]
    public function index(): Response
    {
        // Get the logged-in user (or null if not logged in)
        $user = $this->getUser();

        // var_dump($user->getRole());

        return $this->render('personal/personal.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin', name: 'admin')]
    public function admin(Request $request): Response
    {
        $user = $this->getUser();
        $registrationFormView = null;

        if (!$user || $user->getRole() !== UserRole::ADMIN) {
            $this->addFlash('error', 'Access denied.');
            return $this->redirectToRoute('homepage');
        }

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

        // Render admin page here
        return $this->render('personal/admin.html.twig', [
            'user' => $user,
            'registrationForm' => $registrationFormView,
        ]);
    }
}
