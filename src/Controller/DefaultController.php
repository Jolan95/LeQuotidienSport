<?php

namespace App\Controller;


use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CallApiService;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\PostType;
use App\Repository\RateRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class DefaultController extends AbstractController
{


    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_sport', ["sport" =>"actualité"]);
    }

    #[Route('/journal/{sport}', name: 'app_sport', requirements: ['sport' => 'actualité|football|basketball|rugby|tennis|cyclisme|autres'])]
    public function articles(string $sport, PostRepository $postRepo, Request $request){
        if($sport == "actualité"){
            $sport = null;
        }
        $priority = $postRepo->findOneByPrimary($sport);
        if($priority != null){
            $priority= $priority[0];
            $articles = $postRepo->findPostsExceptPrimary($priority->getId(), $sport);
        } else {
            $articles = $postRepo->findBySport($sport);
        }
        $ajax = $request->query->get("ajax");
        $search = $request->query->get("search");
        $offset = $request->query->get("offset");
        if($ajax){
            if($ajax == 1 && strlen($search) > 2){
                    $articles = $postRepo->findByFilterandCategory($search, $sport);
                    $priority = null;
            }
            if($ajax == 2){
                $articles = $postRepo->findPostsExceptPrimary($priority->getId(), $sport, $offset);
                $priority = null;
            }
            return new JsonResponse([
                "content" => $this->renderView('content/articles.html.twig', [
                    "articles" => $articles,
                    "priority" => $priority
                ])
            ]);  
        }    
        return $this->render('pages/index.html.twig', [
        'priority' => $priority,
        'articles' => $articles,
        'sport' => $sport
        ]);
    }


                
    #[Route('/article/{id}', name: 'app_article')]  
    public function article(int $id, RateRepository $rateRepo,PostRepository $postRepo, Request $request,  ManagerRegistry $manager){
        $entity = $manager->getManager();
        $article =  $postRepo->findOneBy(["id" => $id]);
        $form = $this->createForm(CommentType::class);
        $form->handleRequest($request);
        $average = $rateRepo->findAverageRating($article);
        if($this->getUser()){
            $rate = $rateRepo->findOneBy(["user" => $this->getUser(), "post" => $article]);
            $rate? $stars = false : $stars= true; 
        } else{
            $stars = false;
        }
        if($form->isSubmitted() && $form->isValid()){
            $comment = new Comment();
            $comment->setContent($form->getData()->getContent());
            $comment->setAuthor($this->getUser());
            $comment->setPost($article);
            $comment->setDate(new \DateTime());
            $entity->persist($comment);
            $entity->flush();
            $this->addFlash('success', "Votre commentaire a bien été publié !");
            //Reset the form
            $comment = new Comment();
            $form = $this->createForm(CommentType::class, $comment);
        }    
        $comments= $article->getComments();
     
        return $this->render("pages/article.html.twig",
        [
            "article" => $article,
            "comments" => $comments,
            "form" => $form->createView(),
            "stars" => $stars,
            "average" => $average[1]
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
        return $this->render('pages/live.html.twig', [
            "environement" => $_ENV["APi_KEY_SPORT"],
            "footFrance" => $matchsFrance,
            "footPL" => $matchsPL
        ]);
    }

    #[Route('/author', name:'app_author')]
    #[isGranted("ROLE_AUTHOR")]
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
            $post->setViews(0);
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
            $post->setPicture($data->getPicture());
            // uncomment if you want to proceed with upload image instead of url images 
            // $file = $post->getPicture();
            // $fileName = md5(uniqid()).'.'.$file->guessExtension();
            // $file->move($this->getParameter('upload_directory'), $fileName);
            // $post->setPicture($fileName);
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
    * @Route("admin-utilisateurs/{role}/{page}", name="app_admin", defaults={"page"=0})
    * requirements={"user" : ["user", "author", "admin"]}
    * @isGranted("ROLE_ADMIN")
    */
    public function admin($page ,UserRepository $userRepo, $role, PostRepository $post): Response
    {
        $roles = strtoupper($role);
        $roles= '["ROLE_'.$role.'"]';
        $offset = null;
        if($page){
            $offset = 10 * ($page - 1);
        }
        $number_page = ceil($userRepo->findNumberPage($roles) / 10);
        $users = $userRepo->findByRoles($roles, $offset);
        return $this->render("admin/utilisateurs.html.twig", [
        'role' => $role,
        'users' => $users,
        "posts" => $post,
        "numberPage" => $number_page,
        "page" => $page
        ]);
    }

     
    #[Route("admin/listing/articles", name : "app_admin_articles")]
    #[isGranted("ROLE_ADMIN")]
    public function adminallArticles(PostRepository $postRepo, UserRepository $userRepo): Response
    {
        $authors = $userRepo->findByRoles('["ROLE_AUTHOR"]',null);
        $admins = $userRepo->findByRoles('["ROLE_admin"]', null);
        $numberPages = ceil($postRepo->findNumberPage() / 20);
        $posts = $postRepo->findByFilters(null, "created_At", "DESC", null, 0);
        return $this->render("admin/all-articles.html.twig", [
        'posts' => $posts,
        'authors' => $authors,
        'admins' => $admins,
        'numberPages' => $numberPages,
        "page" => 1
        ]);
    }

    #[Route('author/my-articles', name: 'app_my_articles')]
    #[isGranted("ROLE_AUTHOR")]
    public function myArticles( PostRepository $postRepo){
        $posts = $postRepo->findByUserDesc($this->getUser());
         return $this->render("admin/mesarticles.html.twig", [
            "posts" => $posts
        ]);
    }

    #[Route('author/my-brouillons', name: 'app_my_brouillons')]
    public function myBrouillons( PostRepository $postRepo){
           
        $posts = $postRepo->findBy(["user" => $this->getUser(), "published" => false]);
        
         return $this->render("admin/mesbrouillons.html.twig", [
            "posts" => $posts
        ]);
    }
        

    #[Route('admin/comments/{id}', name: 'app_admin_comments')]
    #[isGranted("ROLE_ADMIN")]
    public function adminComments($id, PostRepository $postRepo){
           
        $post = $postRepo->findById($id);
         return $this->render("admin/comments.html.twig", [
            "post" => $post[0]
        ]);
    }


    #[Route('author/edit-article/{id}', name: 'app_edit_article')]
    #[isGranted("ROLE_AUTHOR")]
    public function edit_article($id, PostRepository $repo, Request $request,ManagerRegistry $manager){
           
        $post = $repo->findOneBy(["id" => $id]);
        $form = $this->createForm(PostType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // set new date if the user publishes a post drafted
            if( $form->getClickedButton() === $form->get("publier") && $post->getPublished() == false){
                $post->setCreatedAt(new \DateTimeImmutable());
            }
            $data = $form->getData();
            $post->setTitle($data->getTitle());
            $post->setDescription($data->getDescription());
            $post->setContent($data->getContent());
            $post->setCategory($data->getCategory());
            $post->setImportant($data->getImportant());
            // if button "publier" is clicked, the article is published 
            $form->getClickedButton() === $form->get("publier")? $post->setPublished(true) : $post->setPublished(false);
            if($data->getPicture() != null ){
                $post->setPicture($data->getPicture());
                // $file = $data->getPicture();
                // $fileName = md5(uniqid()).'.'.$file->guessExtension();
                // $file->move($this->getParameter('upload_directory'), $fileName);
                // $post->setPicture($fileName);
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
    
    // /**
    //  * @Route("/create-admin", name="app_create_admin")
    //  */
    // public function createadmin( ManagerRegistry $manager, UserPasswordHasherInterface $passwordHasher)
    // {
    //     $user = new User();
    //     $user->setEmail("admin@hotmail.fr");
    //     $user->setFullname("Administrateur");
    //     $hashedPassword = $passwordHasher->hashPassword($user, "Administrateur");
    //     $user->setPassword($hashedPassword);
    //     $user->setRoles(["ROLE_ADMIN"]);

    //     $entity = $manager->getManager();
    //     $entity->persist($user);
    //     $entity->flush();

    //     return new Response("admin créé");
    // }

    


}
