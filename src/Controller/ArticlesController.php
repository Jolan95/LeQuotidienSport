<?php

namespace App\Controller;

use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PostRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use App\Entity\User;

class ArticlesController extends AbstractController
{
    /**
     * @Route("/category/{sport}", name="app_sport")
     */

    public function articles(string $sport, PostRepository $post){
       $articles =  $post->findBySport($sport);
    return $this->render('articles/articles.html.twig', [
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
      

    
    }  


