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
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\PostType;

class DefaultController extends AbstractController
{



    #[Route('/', name: 'home')]
    public function index(PostRepository $postRepo,Request $request): Response
    {
        $priority = $postRepo->findOneByPrimary();
        if($priority != null){
            $priority= $priority[0];
            $articles = $postRepo->findPostsExceptPrimary($priority->getId());
        } else{
            $articles = $postRepo->findAll();
        }
        $ajax = $request->query->get("ajax");
        if($ajax){
            $search = $request->query->get("search");
            $articles = $postRepo->findByFilter($search);
            if($search != null){
                $priority = null;
            }
            return new JsonResponse([
                "content" => $this->renderView('content/articles.html.twig', [
                    "articles" => $articles,
                    "priority" => $priority
                ])
            ]);  
        }    
 
        return $this->render('index.html.twig', [
            'articles' => $articles,
            'priority' => $priority
        ]);
    }

    #[Route('/category/{sport}', name: 'app_sport')]
    public function articles(string $sport, PostRepository $postRepo){
        $priority = $postRepo->findOneByPrimary($sport);
        if($priority != null){
            $priority= $priority[0];
            $articles = $postRepo->findPostsExceptPrimary($priority->getId(), $sport);
        } else {
            $articles = $postRepo->findBySport($sport);
        }
        return $this->render('index.html.twig', [
        'priority' => $priority,
        'articles' => $articles,
        'sport' => $sport
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
            fn ($post) => $post->getUser()->getId() === $user
        );
        $numberArticles = count($postOfUser);

        return $this->render('profile.html.twig', [
        "numberArticles" => $numberArticles
        ]);
    }

    #[Route('/live', name: 'app_live')]
    public function live(CallApiService $api): Response
    {      
        $matchsPL =  $api->getLiveFoot(3161);
        $matchsFrance =  $api->getLiveFoot(3188);
        $matchsPL = json_decode(json_encode($matchsPL), true);
        $matchsFrance = json_decode(json_encode($matchsFrance), true);
        $matchsFrance["name"] = "Ligue 1";
        $matchsPL["name"] = "Premier League";
        return $this->render('live.html.twig', [
            "environement" => $_ENV["APi_KEY_SPORT"],
            "footFrance" => $matchsFrance,
            "footPL" => $matchsPL
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

      
    #[Route('/article/{id}', name: 'app_article')]  
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
            $data = $form->getData();
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setTitle($data->getTitle());
            $post->setDescription($data->getDescription());
            $post->setContent($data->getContent());
            $post->setUser($user);
            $post->setImportant($data->getImportant());
            // if button "publier" is clicked, the article is published and handle the messag to display
            if($form->getClickedButton() === $form->get("publier")){
                $post->setPublished(true);
                $message = 'Votre article a été publié avec succès !';
            } else{
                $post->setPublished(false);
                $message = 'Votre article a été ajouté à vos brouillons avec succès !';
            }
            $file = $post->getPicture();
            $fileName = md5(uniqid()).'.'.$file->guessExtension();
            $file->move($this->getParameter('upload_directory'), $fileName);
            $post->setPicture($fileName);
            $entity = $manager->getManager();
            $entity->persist($post);
            $entity->flush();
            $this->addFlash('success', $message);

            //reset the form
            $post = new Post();
            $form = $this->createForm(PostType::class, $post);
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
        $route = "admin/".$role.".html.twig";
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
           
        $posts = $repo->findBy(["user" => $this->getUser(), "published" => true]);

         return $this->render("articles/mesarticles.html.twig", [
            "posts" => $posts
        ]);
    }

    #[Route('author/my-brouillons', name: 'mybrouillons')]
    public function myBrouillons( PostRepository $repo){
           
        $posts = $repo->findBy(["user" => $this->getUser(), "published" => false]);

         return $this->render("articles/mesbrouillons.html.twig", [
            "posts" => $posts
        ]);
    }

    #[Route('author/edit-article/{id}', name: 'edit-article')]
    public function edit_article($id, PostRepository $repo, Request $request,ManagerRegistry $manager){
           
        $post = $repo->findOneBy(["id" => $id]);
        $form = $this->createForm(PostType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();
            $post->setTitle($data->getTitle());
            $post->setDescription($data->getDescription());
            $post->setContent($data->getContent());
            $post->setCategory($data->getCategory());
            $post->setImportant($data->getImportant());
            // if button "publier" is clicked, the article is published 
            $form->getClickedButton() === $form->get("publier")? $post->setPublished(true) : $post->setPublished(false);
            if($data->getPicture() != null ){
                $file = $data->getPicture();
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                $file->move($this->getParameter('upload_directory'), $fileName);
                $post->setPicture($fileName);
            }
            $entity = $manager->getManager();
            $entity->persist($post);
            $entity->flush();
            $this->addflash("success", "Votre article a bien été modifié !");
        }    
         return $this->render("forms/edit-article.html.twig", [
            "post" => $post,
            'form' => $form->createView()
        ]);
    }

    #[Route('/password-reset', name: 'forgetPassword')]
    public function ForgetPassword(){
        
        $form =  $this->createForm(NewPasswordType::class);
        
        return $this->render("forms/forget.html.twig", [
            "form" => $form->createView()
        ]);
    }


    // /**
    //  * @Route("/create-admin", name="app_create_admin")
    //  */
    // public function createadmin( ManagerRegistry $manager, UserPasswordHasherInterface $passwordHasher)
    // {
    //     $user = new User();
    //     $user->setEmail("jolan.aubry@hotmail.fr");
    //     $user->setFullname("Jolan Aubry");
    //     $hashedPassword = $passwordHasher->hashPassword($user, "Admin");
    //     $user->setPassword($hashedPassword);
    //     $user->setRoles(["ROLE_ADMIN"]);

    //     $entity = $manager->getManager();
    //     $entity->persist($user);
    //     $entity->flush();

    //     return new Response("admin créé");
    // }

    


}
