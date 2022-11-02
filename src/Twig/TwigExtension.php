<?php

namespace App\Twig;

use App\Repository\PostRepository ;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;



class PostsByUserExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
           
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nbPost', [$this, 'PostbyUser']),
            new TwigFunction('convertDate', [$this, 'convertDate']),

        ];
    }

    public function PostbyUser($value, PostRepository $post)
    {
        $post = $post->findAll();
        $postOfUser = array_filter(
            $post,
            fn ($post) => $post->getUser()->getId() === $value
        );
        $numberArticles = count($postOfUser);
        return $numberArticles;
    }
    public function ConvertDate($date)
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($date->getTimestamp());
        $dateTime = $dateTime->format('H:i d/m/Y ');
        return $dateTime;
    }
}
