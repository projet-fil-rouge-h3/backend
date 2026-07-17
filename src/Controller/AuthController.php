<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
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

    /**
     * Liste paginée des utilisateurs pour le back-office (ROLE_ADMIN via security.yaml).
     * Le mapping manuel produit le shape AdminUser attendu par le front.
     */
    #[Route('/users', name: 'api_auth_users', methods: ['GET'])]
    public function getUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 0);
        $size = $request->query->getInt('size', 20);

        $data = $userRepository->getPaginatedUsers($page, $size);
        $data['content'] = array_map(fn (User $u) => $this->mapUserForAdmin($u), $data['content']);

        return $this->json($data);
    }

    /**
     * Mise à jour admin d'un utilisateur : activation/désactivation et rôle.
     * Garde-fou : un admin ne peut pas se désactiver ni se rétrograder lui-même.
     */
    #[Route('/users/{id}', name: 'api_auth_users_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateUser(User $targetUser, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();
        $isSelf = $this->getUser() instanceof User && $this->getUser()->getId() === $targetUser->getId();

        if (isset($data['active'])) {
            if ($isSelf && $data['active'] === false) {
                return $this->json(['message' => 'Impossible de désactiver votre propre compte'], 400);
            }
            $targetUser->setIsActive((bool) $data['active']);
        }

        if (isset($data['role'])) {
            if (!in_array($data['role'], ['USER', 'ADMIN'], true)) {
                return $this->json(['message' => 'Rôle invalide (USER ou ADMIN attendu)'], 400);
            }
            if ($isSelf && $data['role'] !== 'ADMIN') {
                return $this->json(['message' => 'Impossible de retirer votre propre rôle admin'], 400);
            }
            $targetUser->setRoles($data['role'] === 'ADMIN' ? ['ROLE_ADMIN'] : ['ROLE_USER']);
        }

        $em->flush();

        return $this->json($this->mapUserForAdmin($targetUser));
    }

    /**
     * Shape AdminUser du front : role singulier, dates ISO, flag active.
     */
    private function mapUserForAdmin(User $user): array
    {
        return [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'role' => in_array('ROLE_ADMIN', $user->getRoles(), true) ? 'ADMIN' : 'USER',
            'emailVerified' => (bool) $user->isVerified(),
            'phone' => $user->getPhone(),
            'createdAt' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'active' => (bool) $user->isActive(),
        ];
    }
}
