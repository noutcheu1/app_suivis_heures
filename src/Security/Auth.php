<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\Session\SessionInterface;


class Auth
{
    public function __construct(private SessionInterface $session) {}

    public function check(): bool
    {
        return $this->session->has('username');
    }

    public function is_intervenant() : bool
    {
        return $this->session->has('intervenant_id') && $this->session->has('candidat_id');
    }

    public function is_famille() : bool {
        return $this->session->has('famille_id'); 
    }

    public function user(): ? string
    {
        return $this->session->get('username');
    }

    public function intervenant_id(): ? string {
        return $this->session->get('intervenant_id');
    }

    public function candidat_id(): ? string {
        return $this->session->get('candidat_id');
    }

    public function famille_id(): ? string {
        return $this->session->get('famille_id');
    }

    public function isAdmin(): bool
    {
        return $this->user() === '9.99.99.99.999.999.99';
    }
}
