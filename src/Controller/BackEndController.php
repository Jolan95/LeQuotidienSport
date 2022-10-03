<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use App\Repository\RateRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use App\Entity\Rate;
use App\Service\File;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Service\Fetch;

class BackEndController extends AbstractController
{

    #[Route('admin/remove/{user}/{role}', name: 'remove_user')]
    #[isGranted('ROLE_ADMIN')]
    public function userRemove(User $user, $role, ManagerRegistry $doctrine, Request $request){
            
        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash("success", $user->getFullname()." est maintant supprimé"); 
        $route = $request->headers->get('referer');
        return $this->redirect($route);
    }


    #[Route('admin/makeAuthor/{user}', name: 'make_author')]
    #[isGranted('ROLE_ADMIN')]
    public function makeAuthor(User $user, ManagerRegistry $doctrine, MailerInterface $mailer){
            
        $user->setRoles(["ROLE_AUTHOR"]);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $email = (new TemplatedEmail())
        ->from("lequotidiensport@hotmail.com")
        ->to($user->getEmail())
        ->subject('Changement de vos accès')
        ->htmlTemplate('mails/change-role.html.twig')
        ->context([
            'role' => "Auteur",
            "user" => $user->getFullname(),
        ]);
        $mailer->send($email);
        $this->addFlash("success", $user->getFullname()." est maintenant un auteur");  
        return $this->redirectToRoute("app_admin", ["role" => "user"]);
 

    }
    #[Route('admin/makeUser/{user}', name: 'make_user')]
    #[isGranted('ROLE_ADMIN')]
    public function makeUser(User $user, ManagerRegistry $doctrine, MailerInterface $mailer){
            
        $user->setRoles(["ROLE_USER"]);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $email = (new TemplatedEmail())
        ->from("lequotidiensport@hotmail.com")
        ->to($user->getEmail())
        ->subject('Changement de vos accès')
        ->htmlTemplate('mails/change-role.html.twig')
        ->context([
            'role' => "Utilisateur",
            "user" => $user->getFullname(),
        ]);
        $mailer->send($email);
            return $this->redirectToRoute("app_admin", ["role" => "author"]);

    }
    #[Route('admin/makeAdmin/{user}', name: 'make_admin')]
    #[isGranted('ROLE_ADMIN')]
    public function makeAdmin(User $user, ManagerRegistry $doctrine, MailerInterface $mailer){
            
        $user->setRoles(["ROLE_ADMIN"]);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $email = (new TemplatedEmail())
        ->from("lequotidiensport@hotmail.com")
        ->to($user->getEmail())
        ->subject('Changement de vos accès')
        ->htmlTemplate('mails/change-role.html.twig')
        ->context([
            'role' => "Administrateur",
            "user" => $user->getFullname(),
        ]);

        $mailer->send($email);
            
        return $this->redirectToRoute("app_admin", ["role" => "author"]);
    }


    #[Route('rating-article', name: 'rating-article')]
    public function RateArticle( ManagerRegistry $doctrine, Request $request, UserRepository $userRepo, PostRepository $postRepo, RateRepository $rateRepo){
        $value= $request->query->get("rate");
        $post= $postRepo->findOneById($request->query->get("post"));
        $rate = $rateRepo->findOneBy(["user" => $this->getUser(), "post" => $post]);
        if($rate){
            $rate->setValue(floatval($value));
            $entityManager = $doctrine->getManager();
            $entityManager->persist($rate);
            $entityManager->flush();
            return new Response("Update");
        }
        $rate = new Rate();
        $rate->setUser($this->getUser());
        $rate->setPost($post);
        $rate->setValue(floatval($value));
        $entityManager = $doctrine->getManager();
        $entityManager->persist($rate);
        $entityManager->flush();
        return new Response("Ajout");
    }

    #[Route('request-ajax-listing-all-articles', name: 'tri_every_articles')]
    #[isGranted('ROLE_ADMIN')]
    public function triEveryArticles(UserRepository $userRepo, PostRepository $postRepo,Request $request){
        $author = $request->query->get("author");
        $order = $request->query->get("order");
        $search = $request->query->get("search");
        $page = $request->query->get("page");
        if($page == null){
            $page = 1;
        }
        $authors = $userRepo->findByRoles('["ROLE_AUTHOR"]',null);
        $admins = $userRepo->findByRoles('["ROLE_admin"]', null);
        $values = explode(',',$order);
        $value = $values[0];
        $order = $values[1];
        if($author != "" || $search != ""){
            $page = 1;
        }
        $offset = ($page - 1) * 20;
        if($value == "rate" ){
            $posts = $postRepo->findByRateAverage( $order, $author, $search, $offset);
            $numberPages = $postRepo->findNumberPagesRateAverage( $order, $author, $search);
            $numberPages = ceil($numberPages[0][1]/20);
        }else{
            $posts = $postRepo->findByFilters($author, $value ,$order, $search, $offset);
            $numberPages = $postRepo->findNumberPagesByFilters($author, $value ,$order, $search);
            $numberPages = ceil($numberPages[0][1]/20);

        }
        return new JsonResponse([
            "content" => [$this->renderView('content/all-articles.html.twig', [
            "posts" => $posts,
            "numberPages" => $numberPages,
            "page" => $page,
            "admins" => $admins,
            "authors" =>$authors
            ]), "page" => $numberPages]
        ]);  
    }

    #[Route('request-ajax-order-my-article', name: 'order-article')]
    public function orderArticle(PostRepository $postRepo,Request $request, RateRepository $rateRepo)
    {
        $data = $request->query->get("tri");
        $values = explode(',',$data);
        $value = $values[0];
        $order = $values[1];
        if($value == "rate" ){
            $posts = $postRepo->findByRateAverage( $order, $this->getUser());
        } else{
            $posts = $postRepo->findArticlesByOrder($value, $this->getUser(), $order);
        }
            return new JsonResponse([
                "content" => $this->renderView('content/mes-articles.html.twig', [
                "posts" => $posts
                ])
            ]);  
        }    

    #[Route('article/add-view', name: 'add_view_article')]
    public function AddView(PostRepository $postRepo, ManagerRegistry $doctrine, Request $request){
        $article_id = $request->query->get("article");
        $article = $postRepo->findOneById($article_id);
        $article->setViews($article->getViews() + 1);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($article);
        $entityManager->flush();  
        return new Response("ok");         
    }

    #[Route('author/delete/{post}', name: 'delete_my_articles')]
    #[isGranted('ROLE_AUTHOR')]
    public function deleteMyArticles(Post $post, ManagerRegistry $doctrine, Request $request){

        if($post->getUser() === $this->getUser() || $this->isGranted('ROLE_ADMIN')){ 
            $entityManager = $doctrine->getManager();
            $entityManager->remove($post);
            $entityManager->flush();            
            $route = $request->headers->get('referer');
            return $this->redirect($route);
        } else{
            return new Response("<h4>Vous ne pouvez pas supprimer ce poste</h4>", 403);
        }
    }


    #[Route('comment/remove/{id}', name: 'delete_comment')]
    public function deleteComment($id, CommentRepository $commentRepo ,ManagerRegistry $doctrine, Request $request){
        $comment = $commentRepo->findOneById($id);
        if($this->getUser() == $comment->getAuthor() || $this->isGranted('ROLE_ADMIN')){
            $post_id = $comment->getPost()->getId();
            $entityManager = $doctrine->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash("danger", "Le commentaire à été supprimé définitivement.");
            $route = $request->headers->get('referer');
            return $this->redirect($route);
        }else{
            throw new Exception("Vous ne pouvez pas accéder à cette requête.", 403);
        }

    }
}
