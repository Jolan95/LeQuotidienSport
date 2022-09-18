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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

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
}
