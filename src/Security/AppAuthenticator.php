<?php
namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\SecurityRequestAttributes;


class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private RouterInterface $router,
        // Symfony injecte automatiquement le logger via l'autowiring
        private LoggerInterface $logger,
        private \App\Repository\IntervenantRepository $intervenantRepository,
        private \App\Service\AuditLogger $audit,
    ) {}

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('id', '');
        $type     = $request->request->get('type');
        $ip       = $request->getClientIp();

        // Intervenant : il saisit son TÉLÉPHONE (RGPD : ni numSS ni téléphone en bd horaire).
        // On le VÉRIFIE sur chaudoudou et on en déduit le numSalarie = identifiant du compte.
        // Si aucun (ou plusieurs) intervenant ne correspond, on laisse l'identifiant tel quel
        // → le chargement échouera proprement (mauvais identifiants).
        if ($type === 'INTER') {
            $username = $this->intervenantRepository->findByTelephoneNormalise($username)?->getNumSalarie()
                ?? $username;
        }

        // Log de la tentative : on ne logue JAMAIS le mot de passe
        $this->logger->info('Tentative de connexion', [
            'username' => $username,
            'type'     => $type,
            'ip'       => $ip,
        ]);

        $request->getSession()->set(
            \Symfony\Component\Security\Http\SecurityRequestAttributes::LAST_USERNAME,
            $username
        );
        $request->getSession()->set('type', $type);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                // Active le cookie « se souvenir de moi » (cf. remember_me dans security.yaml,
                // always_remember_me: true → session longue, l'intervenant ne se reconnecte presque jamais).
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        $roles    = $token->getRoleNames();
        $username = $token->getUserIdentifier();

        $this->logger->info('Connexion réussie', [
            'username' => $username,
            'roles'    => $roles,
            'ip'       => $request->getClientIp(),
            'firewall' => $firewallName,
        ]);

        // AUDIT : connexion réussie (l'identifiant stocké = numSalarie/code, non sensible).
        $this->audit->log('login_success', [
            'actor'      => $username,
            'actor_role' => implode(',', $roles),
        ]);

        if (in_array('ROLE_ADMIN', $roles)) {
            $this->logger->debug('Redirection vers le tableau de bord admin', ['username' => $username]);
            return new RedirectResponse($this->router->generate('admin_dashboard_mvc'));
        }
        // Reprise de la page demandée avant le login (ex. scan QR → /declarer/...).
        // Mécanisme TargetPath standard : vaut pour n'importe quelle page protégée.
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        if ($targetPath) {
            $this->removeTargetPath($request->getSession(), $firewallName);
            $this->logger->debug('Reprise de la page demandée après login', ['target' => $targetPath]);
            return new RedirectResponse($targetPath);
        }

        if (in_array('ROLE_INTERVENANT', $roles)) {
            $this->logger->debug('Redirection vers le tableau de bord intervenant', ['username' => $username]);
            return new RedirectResponse(
                $this->router->generate('intervenant_panel_mvc', [
                    'id' => $token->getUser()->getId() // ou méthode équivalente
                ])
            );
        }
        if (in_array('ROLE_FAMILLE', $roles)) {
            $this->logger->debug('Redirection vers le tableau de bord famille', ['username' => $username]);
            return new RedirectResponse($this->router->generate('famille_panel_mvc'));
        }

        // Aucun rôle connu : situation anormale, on logue en warning
        $this->logger->warning('Connexion réussie mais aucun rôle reconnu retour login', [
            'username' => $username,
            'roles'    => $roles,
        ]);

        return new RedirectResponse($this->router->generate('app_login'));
    }

    
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): Response {
        $type = $request->request->get('type');

        $this->logger->warning('Échec de connexion', [
            'username' => $request->request->get('id', '(inconnu)'),
            'type'     => $type,
            'ip'       => $request->getClientIp(),
            'raison'   => $exception->getMessageKey(),
        ]);

        // AUDIT : échec de connexion. L'identifiant tenté peut être un téléphone (sensible)
        // → on ne stocke que son HASH, jamais en clair.
        $this->audit->log('login_failed', [
            'actor'  => \App\Service\AuditLogger::hashId($request->request->get('id')),
            'type'   => $type,
            'reason' => $exception->getMessageKey(),
        ]);

        // ✅ On stocke l'erreur en session comme Symfony le fait normalement
        if ($request->hasSession()) {
            $request->getSession()->set(
                SecurityRequestAttributes::AUTHENTICATION_ERROR,
                $exception
            );
        }

        // ✅ On redirige vers l'URL de login avec le paramètre type
        return new RedirectResponse(
            $this->router->generate('app_login', ['type' => $type])
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }
}