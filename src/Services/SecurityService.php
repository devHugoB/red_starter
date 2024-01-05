<?php

namespace App\Services;

use App\Entity\ResetPassword;
use App\Entity\User;
use App\Repository\ResetPasswordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class SecurityService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ResetPasswordRepository $resetPasswordRepository
    ) {}

    /**
     * Créer un formulaire demandant le pseudonyme et l'email
     *
     * @param FormBuilderInterface $emailForm
     * @return FormInterface
     */
    public function createResetEmailForm(FormBuilderInterface $emailForm): FormInterface
    {
        return  $emailForm
            ->add('pseudo', EmailType::class, [
                'constraints' => new NotBlank(["message" => "Veuillez renseigner un pseudonyme"])
            ])
            ->add('email', EmailType::class, [
                'constraints' => new NotBlank(["message" => "Veuillez renseigner une adresse email"])
            ])
            ->getForm()
        ;
    }

    /**
     * Créer un nouveau token de reinitialisation pour l'utilisateur donné
     *
     * @param User $user
     * @return string
     */
    public function createResetPasswordToken(User $user): string
    {
        $oldResetPassword = $this->resetPasswordRepository->findOneBy(['user' => $user]);

        if ($oldResetPassword) {
            $this->em->remove($oldResetPassword);
            $this->em->flush();
        }

        $token = str_replace(['+', '/', '=', '?', '&'], '-', base64_encode(random_bytes(20)));

        $resetPassword = new ResetPassword();
        $resetPassword
            ->setUser($user)
            ->setExpiredAt(new \DateTimeImmutable('+10 minutes'))
            ->setToken(sha1($token))
        ;

        $this->em->persist($resetPassword);
        $this->em->flush();

        return $token;
    }

    /**
     * Créer un formulaire demandant un nouveau mot de passe
     *
     * @param FormBuilderInterface $passwordFrom
     * @return FormInterface
     */
    public function createResetPasswordForm(FormBuilderInterface $passwordFrom): FormInterface
    {
        return $passwordFrom
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options' => ['label' => "Mot de passe"],
                'second_options' => ['label' => "Confirmation du mot de passe"],
                'constraints' => new Regex('/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/', "Votre mot de passe ne respecte pas les conditions minimales requises")
            ])
            ->getForm()
        ;
    }
}