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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SecurityController extends AbstractController
{

    /**
    * @Route("signin", name="app_signin")
    */
    
    public function signin(Request $request,MailerInterface $mailer , ManagerRegistry $manager, UserPasswordHasherInterface $passwordHasher){
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
            $email = (new Email())
            ->from('jolanaubry10@gmail.com')
            ->to($user->getEmail())
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Le Quotidien Sport vous Souhaite la bienvenue ! ❤')
            ->text('Sending emails is fun again!')
            ->html("<h1>Bienvenue au Quotidien Sport</h1>
            <p>Bonjour ".$user->getFullname()."</p>
            <p>Vous êtes désormais membre du Quotidien Sport, vous pouvez désormais profiter pleinement de notre site
            internet et ainsi ne louper aucune actualité! </p>
            <p>L'Equipe du Quotidien Sport</p>
            ");
            $mailer->send($email);
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