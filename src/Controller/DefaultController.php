<?php

namespace App\Controller;


use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Annotations\Annotation;
use App\Service\CallApiService;
use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use App\Form\NewPasswordType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; 

use App\Form\AccountCreationType;
use App\Form\PostType;
use App\Form\UserType;


class DefaultController extends AbstractController
{



    #[Route('/', name: 'home')]
    public function index(PostRepository $post ): Response
    {
       $posts = $post->findArticlesByDate();
        
        return $this->render('index.html.twig', [
            'articles' => $posts,
        ]);

    }


    #[Route('/profile', name: 'app_profile')]
    public function profile(PostRepository $repository ): Response
    {
        $user = $this->getUser();
        $user = $user->getId();

        $post = $repository->findAll();
        $postOfUser = array_filter(
            $post,
            fn ($post) => $post->getAuthor()->getId() === $user
        );
        $numberArticles = count($postOfUser);

        return $this->render('profile.html.twig', [
        "numberArticles" => $numberArticles
        ]);
    }

    #[Route('/live', name: 'app_live')]
    public function live(CallApiService $api): Response
    {       
        // $leagues = $api->getRanking($id);
        // $leagues = json_decode($leagues, true);
        return $this->render('lves/foot.html.twig', [
            "environement" => $_ENV["APi_KEY_SPORT"],
        ]);

    }

    #[Route('/ranking/{id}', name: 'app_rank')]
    public function rank(CallApiService $api,int $id): Response
    {       
        $leagues = $api->getRanking($id);
        $leagues = json_decode($leagues, true);
        return $this->render('ranking.html.twig', 
        [
            "league" => $leagues['response'][0]["league"],
        ]);
    }

    /**
    * @Route("/category/{sport}", name="app_sport")
    */
    public function articles(string $sport, PostRepository $post){
        $articles =  $post->findBySport($sport);
     return $this->render('index.html.twig', [
         'articles' => $articles,
         'sport' => $sport
     ]);
     }
 
     /**
      * @Route("/article/{id}", name="app_article")
      */
     public function article(int $id, PostRepository $post){
        $article =  $post->findOneBy([
            "id" => $id
        ]);
 
        $date = $article->getCreatedAt();
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($date->getTimestamp());
        $dateTime = $dateTime->format('d/m/Y H:i');
       
     
        return $this->render("articles/article.html.twig",
        [
            "article" => $article,
            "date" => $dateTime
        ]);
     }


    #[Route('/author', name:'author')]
    public function post(Request $request, ManagerRegistry $manager){     
        $post = new Post();
        $user = $this->getUser();
        
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setAuthor($user);
            $file = $post->getPicture();
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                $file->move($this->getParameter('upload_directory'), $fileName);
                $post->setPicture($fileName);
            $entity = $manager->getManager();
            $entity->persist($post);
            $entity->flush();
        
        }
        return $this->render("forms/creation.html.twig", [
            'form' => $form->createView()
        ]);
    }

   /**
     * @Route("admin/{role}", name="app_admin")
     * requirements={"user" : ["user", "author", "admin"]}
     */
    public function admin(UserRepository $userRepo, $role, PostRepository $post): Response
    {


         $roles = strtoupper($role);
         $roles= '["ROLE_'.$role.'"]';
         $users = $userRepo->findByRoles($roles);

         $route = "forms/admin/".$role.".html.twig";

        return $this->render($route, [
        'role' => $role,
        'users' => $users,
        "posts" => $post

        ]);
    }
        
    /**
     * @Route("admin/listing/articles", name="app_adminArticles")
     * @isGranted("ROLE_ADMIN")
     */
    public function adminArticles(PostRepository $post): Response
    {

        $posts = $post->findArticlesByDate();


        return $this->render("admin/articles.html.twig", [
        'posts' => $posts
        ]);
    }
    #[Route('author/my-articles', name: 'myarticles')]
    public function myArticles( PostRepository $repo){
           
        $posts = $repo->findBy([
            "author" => $this->getUser()
        ]);
         return $this->render("articles/mesarticles.html.twig", [
            "posts" => $posts
        ]);

    }
        /**
     * @Route("/password-reset", name="forgetPassword")
     */

    public function ForgetPassword(){
        
        $form =  $this->createForm(NewPasswordType::class);
        
        return $this->render("forms/forget.html.twig", [
            "form" => $form->createView()
        ]);
    }
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
