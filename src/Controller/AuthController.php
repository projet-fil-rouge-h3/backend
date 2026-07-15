<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    /**
     * Route permettant au Front-end de récupérer les infos de l'utilisateur connecté
     */
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        // Symfony récupère automatiquement l'utilisateur grâce au Token JWT !
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        return $this->json($user, 200, [], ['groups' => 'user:read']);
    }

    /**
     * Route permettant l'inscription d'un nouvel utilisateur
     */
    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $data = $request->toArray();

        // On vérifie qu'on a bien les infos minimales
        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(['message' => 'Email et mot de passe requis'], 400);
        }

        // On crée l'utilisateur
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setRoles(['ROLE_USER']); // Rôle client par défaut
        $user->setIsVerified(false);
        $user->setIsActive(true);
        $user->setCreatedAt(new \DateTimeImmutable());

        // On hache le mot de passe de façon sécurisée
        $hashedPassword = $hasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Sauvegarde en base
        $em->persist($user);
        $em->flush();

        return $this->json($user, 201, [], ['groups' => 'user:read']);
    }
}
