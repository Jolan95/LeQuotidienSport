<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Repository\PostRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class AuthorController extends AbstractController
{
    #[Route('/author', name:'author')]
    public function post(Request $request, ManagerRegistry $manager){     
        $post = new Post();
        $user = $this->getUser();
        
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setAuthor($user);
            $file = $post->getPicture();
                $fileName = md5(uniqid()).'.'.$file->guessExtension();
                $file->move($this->getParameter('upload_directory'), $fileName);
                $post->setPicture($fileName);
            $entity = $manager->getManager();
            $entity->persist($post);
            $entity->flush();
        
        }
    return $this->render("articles/creation.html.twig", [
        'form' => $form->createView()
    ]);
    }

}
