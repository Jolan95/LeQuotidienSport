<?php // src/EventListener/CustomListener.php

namespace App\EventListener;

use Symfony\Flex\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class CustomListener
{
    public function onKernelRequest(RequestEvent $event)
    {
    }
}