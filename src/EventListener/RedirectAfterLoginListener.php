<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class RedirectAfterLoginListener
{
    private RouterInterface $router;
    private RequestStack $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        // Vérifiez les rôles pour rediriger en fonction
        if ($user->hasRole('ROLE_ADMIN')) {
            $url = $this->router->generate('admin_dashboard'); // Remplacez par votre route d'admin
        } elseif ($user->hasRole('ROLE_BOUTIQUIER')) {
            $url = $this->router->generate('boutiquier_dashboard'); // Route pour boutiquiers
    
        } else {
            $url = $this->router->generate('client_dettes'); // Route pour clients
        }

        // Définissez la redirection
        $this->requestStack->getCurrentRequest()->getSession()->set('_security.main.target_path', $url);
    }
}
