<?php

namespace App\Controller;

use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\NewPasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;



class PasswordController extends AbstractController
{
    /**
     * @Route("/password-reset", name="forgetPassword")
     */

    public function ForgetPassword(){
        
        $form =  $this->createForm(NewPasswordType::class);
        
        return $this->render("password/forget.html.twig", [
            "form" => $form->createView()
        ]);
    }

    
    
}