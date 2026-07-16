<?php

namespace App\Controller;

use App\Entity\Address;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/addresses')]
class AddressController extends AbstractController
{
    #[Route('', name: 'api_addresses_list', methods: ['GET'])]
    public function list(AddressRepository $addressRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $addresses = $addressRepo->findBy(['user' => $user]);

        return $this->json($addresses, 200, [], ['groups' => 'address:read']);
    }

    #[Route('', name: 'api_addresses_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $data = $request->toArray();

        $address = new Address();
        $address->setLabel($data['label'] ?? 'Mon adresse');
        $address->setStreet($data['street'] ?? '');
        $address->setStreet2($data['street2'] ?? null); // Prise en compte du street2
        $address->setPostalCode($data['postalCode'] ?? '');
        $address->setCity($data['city'] ?? '');
        $address->setCountry($data['country'] ?? 'France');
        $address->setIsDefault($data['isDefault'] ?? false);

        $address->setUser($user);

        $em->persist($address);
        $em->flush();

        return $this->json($address, 201, [], ['groups' => 'address:read']);
    }

    #[Route('/{id}', name: 'api_addresses_update', methods: ['PUT'])]
    public function update(Address $address, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if ($address->getUser() !== $this->getUser()) {
            return $this->json(['message' => 'Accès interdit'], 403);
        }

        $data = $request->toArray();

        if (isset($data['label'])) $address->setLabel($data['label']);
        if (isset($data['street'])) $address->setStreet($data['street']);
        if (isset($data['street2'])) $address->setStreet2($data['street2']);
        if (isset($data['postalCode'])) $address->setPostalCode($data['postalCode']);
        if (isset($data['city'])) $address->setCity($data['city']);
        if (isset($data['country'])) $address->setCountry($data['country']);
        if (isset($data['isDefault'])) $address->setIsDefault($data['isDefault']);

        $em->flush();

        return $this->json($address, 200, [], ['groups' => 'address:read']);
    }

    #[Route('/{id}', name: 'api_addresses_delete', methods: ['DELETE'])]
    public function delete(Address $address, EntityManagerInterface $em): JsonResponse
    {
        if ($address->getUser() !== $this->getUser()) {
            return $this->json(['message' => 'Accès interdit'], 403);
        }

        $em->remove($address);
        $em->flush();

        return $this->json(null, 204);
    }
}
