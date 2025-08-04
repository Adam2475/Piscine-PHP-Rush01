<?php
namespace App\Controller;

use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

use App\Form\RegistrationFormType;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $imageFile = $form->get('image')->getData();

            // encodindg the image to base64
            if ($imageFile)
            {
                $imageData = file_get_contents($imageFile->getPathname());
                $base64 = base64_encode($imageData);
                $mime = $imageFile->getMimeType();
                $user->setImage('data:' . $mime . ';base64,' . $base64);
                echo "Image uploaded successfully.";
            }
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Your profile was updated successfully.');
            return $this->redirectToRoute('userpage', ['id' => $user->getId()]);
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
