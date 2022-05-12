<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class BackEndController extends AbstractController
{

    #[Route('admin/delete/{post}', name: 'remove_post')]
    public function postRemove(Post $post, ManagerRegistry $doctrine ): Response
    {
        $entityManager = $doctrine->getManager();
        $entityManager->remove($post);
        $entityManager->flush();

            return $this->redirectToRoute('app_adminArticles');

        
    }
    #[Route('admin/remove/{user}/{role}', name: 'remove_user')]
    public function userRemove(User $user, $role, ManagerRegistry $doctrine){
            
        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
        $entityManager->flush();
            return $this->redirectToRoute("app_admin", ['role' => $role]);

    }
    #[Route('admin/makeAuthor/{user}', name: 'make_author')]
    public function makeAuthor(User $user, ManagerRegistry $doctrine){
            
        $user->setRoles(["ROLE_AUTHOR"]);
        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->flush();
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

    #[Route('author/delete/{post}', name: 'delete_myarticles')]
    public function deleteMyArticles(Post $post, ManagerRegistry $doctrine){

        if($post->getAuthor() === $this->getUser()){

            
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
