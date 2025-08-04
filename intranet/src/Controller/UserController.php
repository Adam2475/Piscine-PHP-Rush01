<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Enum\UserRole;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\CreateEventFormType;
use App\Entity\Event;
use App\Service\SearchBarService;
use DateTime;
use App\Service\EvalSlotService;

final class UserController extends AbstractController
{
    #[Route('/api/search/users', name: 'api_search_users', methods: ['GET'])]
    public function searchUsersApi(Request $request, SearchBarService $searchBarService): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (empty($query)) {
            return $this->json([]);
        }

        $results = $searchBarService->searchUsers($query);

        $data = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
            ];
        }, $results);

        return $this->json($data);
    }

    #[Route('/userpage/{id}', name: 'userpage')]
    public function index(Request $request, 
		SearchBarService $searchBarService, 
		int $id, 
		EntityManagerInterface $em,
		EvalSlotService $evalSlotService): Response
    {
        // returning original user to keep a reference to return to
        $user = $this->getUser();
        // calling the searchBar service
        $search = $request->query->get('search');
        $searchResults = $searchBarService->searchUsers($search);
        // get the searched user by ID
        $searchedUser = $em->getRepository(User::class)->find($id);

		if (!$searchedUser) {
			throw $this->createNotFoundException('User not found.');
		}

		$events = $em->getRepository(Event::class)->findAll();
		foreach ($events as $event){
			$date = new DateTime('now');
			if ($date == $event->getDate()) {
				$em->remove($event);
			}
		}

		// getting all the open slots (excluding user)
		$openSlots = $evalSlotService->getOpenSlots($user);

		return $this->render('personal/personal.html.twig', [
			'user' => $searchedUser,
			'originalUser' => $user, // Pass the original user
			'id' => $searchedUser->getId(),
			'searchResults' => $searchResults,
			'events' => $events,
			'slots' => $openSlots,
		]);
	}

	#[Route('/admin', name: 'admin')]
	public function admin(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
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
				// Handle image upload
				$imageFile = $form->get('image')->getData();
				// Mark as inactive by default
				$newUser->setIsActive(false);

				// Encoding the image to base64
				if ($imageFile)
				{
					$imageData = file_get_contents($imageFile->getPathname());
					$base64 = base64_encode($imageData);
					$mime = $imageFile->getMimeType();
					$newUser->setImage('data:' . $mime . ';base64,' . $base64);
					echo "Image uploaded successfully.";
				}
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
				return $this->redirectToRoute('admin');
			}

			$registrationFormView = $form->createView();
		}

		$events = $em->getRepository(Event::class)->findAll();
		foreach ($events as $event){
			$date = new DateTime('now');
			if ($date == $event->getDate()) {
				$em->remove($event);
			}
		}
		// // Render admin page here
		return $this->render('personal/admin.html.twig', [
			'user' => $user,
			'registrationForm' => $registrationFormView,
			'events' => $events,
		]);
	}
}
