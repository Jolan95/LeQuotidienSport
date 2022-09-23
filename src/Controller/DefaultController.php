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
use App\Entity\Rate;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\NewPasswordType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\RateRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints\Date;

class DefaultController extends AbstractController
{



    #[Route('/', name: 'home')]
    public function index(PostRepository $postRepo,Request $request, RateRepository $rateRepo): Response
    {
        return $this->redirectToRoute('app_sport', ["sport" =>"actualité"]);

    }

    #[Route('/journal/{sport}', name: 'app_sport')]
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
        return $this->render('index.html.twig', [
        'priority' => $priority,
        'articles' => $articles,
        'sport' => $sport
        ]);
    }


                
    #[Route('/article/{id}', name: 'app_article')]  
    public function article(int $id, RateRepository $rateRepo,PostRepository $postRepo, Request $request, CommentRepository $commentRepo, CommentType $commentType, ManagerRegistry $manager){
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
            $comment->setRate(0);
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
     
        return $this->render("articles/article.html.twig",
        [
            "article" => $article,
            "comments" => $comments,
            "form" => $form->createView(),
            "stars" => $stars,
            "average" => $average[1]
        ]);
    }
 
    #[Route('/profile', name: 'app_profile')]
    public function profile(PostRepository $repository ): Response
    {
        $post = $repository->findBy(["published" => true, "user" => $this->getUser()]);
        $numberArticles = count($post);
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

    // /**
    // * @Route("admin-utilisateurs/{role}/{page}", name="app_admin", defaults={"page"=0})
    // * requirements={"user" : ["user", "author", "admin"]}
    // */
    // public function admin($page ,UserRepository $userRepo, $role, PostRepository $post): Response
    // {
    //     $roles = strtoupper($role);
    //     $roles= '["ROLE_'.$role.'"]';
    //     $offset = null;
    //     if($page){
    //         $offset = 20 * ($page - 1);
    //     }
    //     $users = $userRepo->findByRoles($roles, $offset);
    //     $route = "admin/".$role.".html.twig";
    //     return $this->render($route, [
    //     'role' => $role,
    //     'users' => $users,
    //     "posts" => $post
    //     ]);
    // }
    
    /**
    * @Route("admin-utilisateurs/{role}/{page}", name="app_admin", defaults={"page"=0})
    * requirements={"user" : ["user", "author", "admin"]}
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
        
    /**
     * @Route("admin/listing/articles/{page}", name="app_admin_articles")
     * @isGranted("ROLE_ADMIN")
     */
    public function adminArticles($page ,PostRepository $postRepo, UserRepository $userRepo, Request $request): Response
    {
        $authors = $userRepo->findByRoles('["ROLE_AUTHOR"]',null);
        $admins = $userRepo->findByRoles('["ROLE_admin"]', null);
        $numberPages = ceil($postRepo->findNumberPage() / 20);
        $offset = ($page - 1) * 20;
        $posts = $postRepo->findByFilters(null, "created_At", "DESC", null, $offset);
        return $this->render("admin/articles.html.twig", [
        'posts' => $posts,
        'authors' => $authors,
        'admins' => $admins,
        'numberPages' => $numberPages,
        "page" => $page
        ]);
    }

    #[Route('author/my-articles', name: 'myarticles')]
    public function myArticles( PostRepository $postRepo){
        $posts = $postRepo->findByUserDesc($this->getUser());
         return $this->render("articles/mesarticles.html.twig", [
            "posts" => $posts
        ]);
    }

    #[Route('author/my-brouillons', name: 'mybrouillons')]
    public function myBrouillons( PostRepository $postRepo){
           
        $posts = $postRepo->findBy(["user" => $this->getUser(), "published" => false]);
        
         return $this->render("articles/mesbrouillons.html.twig", [
            "posts" => $posts
        ]);
    }
        
    /**
     * @isGranted("ROLE_ADMIN")
     */
    #[Route('admin/comments/{id}', name: 'adminComments')]
    public function adminComments($id, PostRepository $postRepo){
           
        $post = $postRepo->findById($id);
         return $this->render("admin/comments.html.twig", [
            "post" => $post[0]
        ]);
    }


    #[Route('author/edit-article/{id}', name: 'edit-article')]
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
