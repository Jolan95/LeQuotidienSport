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
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class BackEndController extends AbstractController
{

    #[Route('admin/delete/{post}', name: 'remove_post')]
    public function postRemove(Post $post, ManagerRegistry $doctrine )
    {
        $entityManager = $doctrine->getManager();
        $entityManager->remove($post);
        $entityManager->flush();
        $this->addFlash("Error", "Article supprimé.");
        
    }
    #[Route('admin/remove/{user}/{role}', name: 'remove_user')]
    public function userRemove(User $user, $role, ManagerRegistry $doctrine){
            
        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash("success", $user->getFullname()." est maintant supprimé"); 
        return $this->redirectToRoute("app_admin", ['role' => $role]);

    }
    #[Route('admin/makeAuthor/{user}', name: 'make_author')]
    public function makeAuthor(User $user, ManagerRegistry $doctrine, Request $request){
            
        $user->setRoles(["ROLE_AUTHOR"]);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
        $this->addFlash("success", $user->getFullname()." est maintenant un auteur");  
        return $this->redirectToRoute("app_admin", ["role" => "user"]);
 

    }
    #[Route('admin/makeUser/{user}', name: 'make_user')]
    public function makeUser(User $user, ManagerRegistry $doctrine){
            
        $user->setRoles(["ROLE_USER"]);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
            return $this->redirectToRoute("app_admin", ["role" => "author"]);

    }
    #[Route('admin/makeAdmin/{user}', name: 'make_admin')]
    public function makeAdmin(User $user, ManagerRegistry $doctrine){
            
        $user->setRoles(["ROLE_ADMIN"]);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
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

    #[Route('request-ajax-list-all-articles', name: 'tri_all_articles')]
    public function triAllArticles(UserRepository $userRepo, PostRepository $postRepo,Request $request){
        $author = $request->query->get("author");
        $order = $request->query->get("order");
        if($order){
            $values = explode(',',$order);
            $value = $values[0];
            $order = $values[1];
        } else {
            $value = null;
        }
        if($value == "rate" ){
            $posts = $postRepo->findByRateAverage( $order, $author);
        }else{
            $posts = $postRepo->findByFilters($author, $value ,$order);
        }
        return new JsonResponse([
            "content" => $this->renderView('content/mesarticles.html.twig', [
            "posts" => $posts,
            ])
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
                "content" => $this->renderView('content/mesarticles.html.twig', [
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

    #[Route('author/delete/{post}', name: 'delete_myarticles')]
    public function deleteMyArticles(Post $post, ManagerRegistry $doctrine){

        if($post->getUser() === $this->getUser()){ 
            $entityManager = $doctrine->getManager();
            $entityManager->remove($post);
            $entityManager->flush();
            return $this->redirectToRoute("myarticles");
            ;
        } else{
            return new Response("<h4>Vous ne pouvez pas supprimer ce poste</h4>");
        }

    }

    #[Route('comment/remove/{id}', name: 'delete_comment')]
    public function deleteComment($id, CommentRepository $commentRepo ,ManagerRegistry $doctrine){
        $comment = $commentRepo->findOneById($id);
        if($this->getUser() == $comment->getAuthor() || $this->isGranted('ROLE_ADMIN')){
            $post_id = $comment->getPost()->getId();
            $entityManager = $doctrine->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash("danger", "Le commentaire à été supprimé définitivement.");
            return $this->redirectToRoute("adminComments", ["id" => $post_id]);
        }else{
            throw new Exception("Vous ne pouvez pas accéder à cette requête.");
        }

    }
}
