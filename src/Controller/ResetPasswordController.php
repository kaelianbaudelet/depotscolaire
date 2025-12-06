<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\InvalidPasswordResetTokenException;
use App\Exception\PasswordReuseException;
use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\Service\PasswordResetManager;
use App\Service\UserPasswordManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Ce contrôleur gère la réinitialisation du mot de passe.
 * On a tous oublié notre mot de passe un jour, c'est ici qu'on les aide à le retrouver !
 */
final class ResetPasswordController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(string:MAILER_FROM_EMAIL)%')] private readonly string $mailerFromEmail,
        #[Autowire('%env(string:MAILER_FROM_NAME)%')] private readonly string $mailerFromName,
    ) {
    }

    /**
     * Affiche le formulaire de demande de réinitialisation.
     * L'utilisateur entre son email, et on lui envoie un lien magique.
     */
    #[Route('/reset-password', name: 'app_reset_password_request')]
    public function request(
        Request $request,
        UserRepository $userRepository,
        PasswordResetManager $passwordResetManager,
        MailerInterface $mailer,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            // On ne révèle pas si l'email existe ou non pour des raisons de sécurité
            if ($user instanceof User) {
                [$resetToken, $plainToken] = $passwordResetManager->createToken($user);

                $resetUrl = $this->generateUrl('app_reset_password_confirm', [
                    'selector' => $resetToken->getSelector(),
                    'token' => $plainToken,
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $emailMessage = (new TemplatedEmail())
                    ->from(new Address($this->mailerFromEmail, $this->mailerFromName))
                    ->to((string) $user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('security/reset_password/email.html.twig')
                    ->context([
                        'resetUrl' => $resetUrl,
                        'expiresAt' => $resetToken->getExpiresAt(),
                        'user' => $user,
                    ]);

                $mailer->send($emailMessage);
            }

            $this->addFlash('success', 'Si un compte correspond à cette adresse, un email de réinitialisation vient d\'être envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Valide la réinitialisation et permet de choisir un nouveau mot de passe.
     * C'est l'étape finale du processus.
     */
    #[Route('/reset-password/{selector}/{token}', name: 'app_reset_password_confirm')]
    public function reset(
        string $selector,
        string $token,
        Request $request,
        PasswordResetManager $passwordResetManager,
        UserPasswordManager $userPasswordManager,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        try {
            $resetToken = $passwordResetManager->validateToken($selector, $token);
        } catch (InvalidPasswordResetTokenException) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré.');

            return $this->redirectToRoute('app_reset_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = (string) $form->get('newPassword')->getData();

            try {
                $userPasswordManager->resetPassword($resetToken->getUser(), $newPassword);
                $passwordResetManager->markTokenUsed($resetToken);
                $this->addFlash('success', 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter à nouveau !');

                return $this->redirectToRoute('app_login');
            } catch (PasswordReuseException) {
                $form->get('newPassword')->addError(new FormError('Vous ne pouvez pas réutiliser un ancien mot de passe.'));
            }
        }

        return $this->render('security/reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}

