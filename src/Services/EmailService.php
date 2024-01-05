<?php

namespace App\Services;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {}

    /**
     * Envoie un email avec un lien de reinitialisation de mot de passe
     *
     * @param string $recipient
     * @param string $username
     * @param string $token
     * @return void
     * @throws TransportExceptionInterface
     */
    public function sendResetPasswordEmail(string $recipient, string $username, string $token): void
    {
        $this->sendEmail(
            $recipient,
            'Reinitialisation de votre mot de passe',
            'email/reset-password.html.twig',
            [
                'username', $username,
                'token' => $token
            ]
        );
    }

    /**
     * Envoie un email confirmant la modification du mot de passe
     *
     * @param string $recipient
     * @return void
     * @throws TransportExceptionInterface
     */
    public function sendConfirmUpdatePasswordEmail(string $recipient, string $username): void
    {
        $this->sendEmail(
            $recipient,
            "Mot de passe modifié",
            'email/confirm-update-password.html.twig',
            [
                'username' => $username
            ]
        );
    }

    /**
     * Récupère le destinataire, le sujet, le template avec ses arguments si existant et le chemin d'une pièce jointe s'il existe
     * Envoie un email au destinataire avec toutes ces informations
     *
     * @param string $recipient
     * @param string $subject
     * @param string $template
     * @param array $context
     * @param string $attachment
     * @return void
     * @throws TransportExceptionInterface
     */
    private function sendEmail(string $recipient, string $subject, string $template, array $context = [], string $attachment = ""): void
    {
        $email = new TemplatedEmail();
        $email
            ->to($recipient)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context)
        ;

        if ($attachment) $email->attachFromPath($attachment, contentType: "application/pdf");

        $this->mailer->send($email);
    }
}