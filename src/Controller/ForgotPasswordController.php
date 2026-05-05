<?php

namespace App\Controller;

use App\PdoApp;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForgotPasswordController extends AbstractController
{
    #[Route('/mdpOublier', name: 'forgot_password')]
    public function index(Request $request, PdoApp $pdo): Response
    {
        $session = $request->getSession();
        $type = $session->get('type'); // INTER ou FAM
        $error = null;

        // Traitement POST
        if ($request->isMethod('POST')) {
            $id = $request->request->get('id');
            $password = $request->request->get('password');
            $password2 = $request->request->get('password2');
            $code = $request->request->get('code');
            $codeHash = $request->request->get('codeAsh');

            if ($password !== $password2) {
                $error = "Les mots de passe saisis ne sont pas identiques.";
            } elseif (!password_verify($code, $codeHash)) {
                $error = "Le code de changement saisi n'est pas bon.";
            } else {
                if ($type === "INTER") {
                    $userId = $id;
                } else { // FAM
                    $userId = $pdo->getNumsFamille($id)['numero_Famille'];
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                if ($pdo->putNewMdp($userId, $hashedPassword)) {
                    return $this->redirectToRoute('app_login');
                }
            }
        }

        // Génération du code temporaire pour envoi mail (GET ou POST)
        if ($type === "INTER") {
            $user = $pdo->getIntervenantNumSS($request->request->get('id'));
            $to = $user['email_Candidats'];
            $name = $user['nom_Candidats'] . ' ' . $user['prenom_Candidats'];
        } else { // FAM
            $idFam = $pdo->getNumsFamille($request->request->get('id'))['numero_Famille'];
            $user = $pdo->getFamilleNumFam($idFam);
            $emails = explode('|', $user['emails_Parents']);
            $to = $emails[0]; // juste un mail pour dev
            $name = $user['nom_Parents'];
        }

        $temporaryCode = rand(100000, 999999);
        $temporaryCodeHash = password_hash($temporaryCode, PASSWORD_DEFAULT);

        // Envoyer le mail (en dev, forcer un mail)
        $toDev = "noreplychaudoudoux@gmail.com";
        $subject = "Changement de mot de passe";
        $message = "Bonjour $name,\r\n\r\n";
        $message .= "Nous avons reçu une demande de changement de votre mot de passe.\r\n";
        $message .= "Code temporaire : $temporaryCode\r\n\r\n";
        $message .= "La Maison des Chaudoudoux";

        $headers = "From: noreplychaudoudoux@gmail.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        mail($toDev, $subject, $message, $headers);

        return $this->render('forgot_password.html.twig', [
            'type' => $type,
            'error' => $error,
            'id' => $request->request->get('id'),
            'temporaryCodeHash' => $temporaryCodeHash
        ]);
    }
}
