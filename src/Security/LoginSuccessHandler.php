<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $targetPath = $this->getTargetPath($request->getSession(), 'main');
        if ($targetPath !== null) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->resolveDefaultTargetUrl($token));
    }

    private function resolveDefaultTargetUrl(TokenInterface $token): string
    {
        $roles = $token->getRoleNames();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return $this->urlGenerator->generate('app_admin_dashboard');
        }

        if (in_array('ROLE_MANAGER', $roles, true)) {
            return $this->urlGenerator->generate('app_manager_dashboard');
        }

        if (in_array('ROLE_GUARD', $roles, true)) {
            return $this->urlGenerator->generate('app_guard_dashboard');
        }

        return $this->urlGenerator->generate('app_home');
    }
}
