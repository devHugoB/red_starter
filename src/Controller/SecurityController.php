<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationType;
use App\Repository\ResetPasswordRepository;
use App\Repository\UserRepository;
use App\Services\EmailService;
use App\Services\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route(path: '/', name: 'auth_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SecurityService $securityService,
        private readonly EmailService $emailService
    ) {}

    #[Route(path: 'inscription', name: 'register')]
    public function Register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $registerForm = $this->createForm(UserRegistrationType::class, $user);
        $registerForm->handleRequest($request);

        if ($registerForm->isSubmitted() && $registerForm->isValid()) {
            $hash = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hash);

            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute("home");
        }

        return $this->render('security/signup.html.twig', [
            'form' => $registerForm->createView()
        ]);
    }

    #[Route(path: 'connexion', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('home');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: 'deconnexion', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: 'demande-reinitialisation-mot-de-passe', name: 'reset-password-request')]
    public function resetPasswordRequest(Request $request, UserRepository $userRepository): Response
    {
        $emailForm = $this->securityService->createResetEmailForm($this->createFormBuilder());
        $emailForm->handleRequest($request);

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $username = $emailForm->get('pseudo')->getData();
            $email = $emailForm->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email, 'username' => $username]);

            if ($user) {
                $token = $this->securityService->createResetPasswordToken($user);
                $this->emailService->sendResetPasswordEmail($email, $username, $token);
            }

            $this->addFlash('success', "Un email de reinitialisation vous a été envoyé");
            $this->redirectToRoute('home');
        }

        return $this->render('security/reset-password-request.html.twig', [
            'form' => $emailForm->createView()
        ]);
    }

    /**
     * Récupère le token donné et le lie à un token existant dans la base de données
     * Si le token n'existe pas ou s'il a expiré, renvoie l'utilisateur vers la page de connexion
     * Si le token est correcte, créer un formulaire demandant un nouveau mot de passe
     * Si le nouveau mot de passe est valide l'enregistre dans la base de données, supprime le token et renvoie vers la page de connexion
     *
     * @param string $token
     * @param UserPasswordHasherInterface $passwordHasher
     * @param Request $request
     * @param ResetPasswordRepository $resetPasswordRepository
     * @return RedirectResponse|Response
     * @throws TransportExceptionInterface
     */
    #[Route(path: 'reinitialisation-mot-de-passe/{token}', name: 'reset-password')]
    public function resetPassword(string $token, UserPasswordHasherInterface $passwordHasher, Request $request, ResetPasswordRepository $resetPasswordRepository): RedirectResponse|Response
    {
        $resetPassword = $resetPasswordRepository->findOneBy(['token' => sha1($token)]);

        if (!$resetPassword || $resetPassword->getExpiredAt() < new \DateTime('now')) {
            if ($resetPassword) {
                $this->em->remove($resetPassword);
                $this->em->flush();
            }

            return $this->redirectToRoute('auth_login');
        }

        $passwordForm = $this->securityService->createResetPasswordForm($this->createFormBuilder());
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $newPassword = $passwordForm->get('password')->getData();
            $user = $resetPassword->getUser();
            $hash = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hash);

            $this->em->remove($resetPassword);
            $this->em->flush();

            $this->addFlash('success', "Votre mot de passe a bien été reinitialisé");
            $this->emailService->sendConfirmUpdatePasswordEmail($user->getEmail(), $user->getUsername());

            return $this->redirectToRoute('auth_login');
        }

        return $this->render('security/reset-password.html.twig', [
            'form' => $passwordForm->createView()
        ]);
    }
}
