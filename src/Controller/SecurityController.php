<?php

namespace App\Controller;


use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Annotations\Annotation;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use App\Form\NewPasswordType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; 
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\AccountCreationType;
use App\Form\PostType;
use App\Form\UserType;


class SecurityController extends AbstractController
{

    /**
    * @Route("signin", name="app_signin")
    */
    
    public function signin(Request $request, ManagerRegistry $manager, UserPasswordHasherInterface $passwordHasher){
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->getData()->getPassword();
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setRoles(["ROLE_USER"]);
            $entityManager = $manager->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Votre compte à bien été enregistré.');
        }
        return $this->render('forms/signin.html.twig', [
            "form" => $form->createView()
        ]);
    }

    /**
    * @Route("/login", name="app_login")
    */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
    * @Route("/logout", name="app_logout")
    */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}    