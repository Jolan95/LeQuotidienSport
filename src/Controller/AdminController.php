<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/admin')]
class AdminController extends AbstractController
{
    /**
     * @Route("/{role}", name="app_admin")
     * requirements={"user" : ["user", "author", "admin"]}
     */
    public function index(UserRepository $userRepo, $role, PostRepository $post): Response
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
     * @Route("/listing/articles", name="app_adminArticles")
     * @isGranted("ROLE_ADMIN")
     */
    public function adminArticles(PostRepository $post): Response
    {

        $posts = $post->findArticlesByDate();


        return $this->render("admin/articles.html.twig", [
        'posts' => $posts
        ]);
    }

}
