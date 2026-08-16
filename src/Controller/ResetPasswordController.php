<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire('%env(FRONTEND_URL)%')] string $frontendUrl
    ): JsonResponse {
        $data = $request->toArray();
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['message' => 'Email manquant'], 400);
        }

        $user = $userRepo->findOneBy(['email' => $email]);

        if ($user) {
            $resetToken = bin2hex(random_bytes(32));
            $user->setResetToken($resetToken);
            $user->setResetTokenExpiresAt((new \DateTimeImmutable())->modify('+1 hour'));

            $em->flush();

            $resetUrl = $frontendUrl . '/reset-password?token=' . $resetToken;

            $emailMessage = (new TemplatedEmail())
                ->from('noreply@cyna-it.fr')
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->html(
                    '<p>Cliquez sur ce lien pour réinitialiser votre mot de passe : '
                    . '<a href="' . $resetUrl . '">Réinitialiser</a></p>'
                    . '<p>Ce lien expire dans 1 heure.</p>'
                );

            $mailer->send($emailMessage);

            // Uniquement pour le dev : le lien part dans le log serveur, jamais dans la réponse HTTP
            if ($this->getParameter('kernel.environment') === 'dev') {
                $logger->info('Reset password link (DEV only)', [
                    'url' => $resetUrl,
                ]);
            }
        }

        return $this->json([
            'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
        ], 200);
    }

    #[Route('/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = $request->toArray();
        $token = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$token || !$newPassword) {
            return $this->json(['message' => 'Token ou mot de passe manquant'], 400);
        }

        $user = $userRepo->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['message' => 'Token invalide ou expiré'], 400);
        }

        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $em->flush();

        return $this->json(['message' => 'Mot de passe modifié avec succès'], 200);
    }
}