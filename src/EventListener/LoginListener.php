<?php
namespace App\EventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Logs;

class LoginListener
{
    public function __construct(private EntityManagerInterface $em) {}

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        $request = $event->getRequest(); // <- ici on récupère la Request
        $user = $event->getUser();

        $history = new Logs();
        $history->setUser($user)
                ->setLoginAt(new \DateTime())
                ->setIp($request->getClientIp())              // IP
                ->setUserAgent($request->headers->get('User-Agent')); // User-Agent

        $this->em->persist($history);
        $this->em->flush();
    }
}