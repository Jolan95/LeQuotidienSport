<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Post;
use App\Entity\User;


class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(PostRepository $post ): Response
    {
       $posts = $post->findArticlesByDate();
        ;
        return $this->render('articles/articles.html.twig', [
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


}
