<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Invoice;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    public function createOrder(Request $request, EntityManagerInterface $em, ProductRepository $productRepo): JsonResponse
    {
        // Vérifier que l'utilisateur est bien connecté via son Token JWT
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Vous devez être connecté pour commander.'], 401);
        }

        $data = $request->toArray();
        $itemsData = $data['items'] ?? [];

        if (empty($itemsData)) {
            return $this->json(['message' => 'Le panier est vide.'], 400);
        }

        $order = new Order();
        $order->setUser($user);
        $order->setStatus('VALIDATED'); // Ou 'PENDING' si tu as un module de paiement type Stripe plus tard
        $order->setCurrency('EUR');
        $order->setCreatedAt(new \DateTimeImmutable());
        $totalAmount = 0.0;

        foreach ($itemsData as $itemData) {
            $product = $productRepo->find($itemData['productId']);

            if (!$product || !$product->isActive()) {
                continue; // On ignore les produits qui n'existent pas ou sont désactivés
            }

            // Normalisation : le front envoie MONTHLY / YEARLY / ONE_TIME (majuscules)
            $billingPeriod = strtoupper($itemData['billingPeriod'] ?? 'MONTHLY');

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setProductName($product->getName());
            $orderItem->setQuantity($itemData['quantity'] ?? 1);
            $orderItem->setBillingPeriod($billingPeriod);

            // YEARLY → prix annuel ; MONTHLY et ONE_TIME → prix mensuel
            $unitPrice = ($billingPeriod === 'YEARLY')
                ? $product->getPriceYearly()
                : $product->getPriceMonthly();

            $orderItem->setUnitPrice($unitPrice);
            $orderItem->setCustomerOrder($order);
            $order->addOrderItem($orderItem);

            $em->persist($orderItem);

            $totalAmount += ($unitPrice * $orderItem->getQuantity());
        }

        // Tous les produits du panier étaient inexistants ou désactivés
        if ($order->getOrderItems()->isEmpty()) {
            return $this->json(['message' => 'Aucun produit valide dans le panier.'], 400);
        }

        $order->setTotalAmount($totalAmount);
        $em->persist($order);

        // 4. Générer la Facture automatiquement
        $invoice = new Invoice();
        // Exemple de numéro généré : FAC-20260716-6789 (Basé sur la date et un nombre aléatoire pour l'instant)
        //TODO faire un numéro de facture unique
        $invoice->setInvoiceNumber('FAC-' . date('Ymd') . '-' . rand(1000, 9999));
        $invoice->setCurrency('EUR');
        $invoice->setIssuedAt(new \DateTimeImmutable());
        $invoice->setCustomerOrder($order);

        // Convention projet : les prix catalogue sont HT, la TVA (20 %) s'ajoute sur la facture
        $vatRate = 20.0;
        $amountHt = $totalAmount;
        $vatAmount = $amountHt * ($vatRate / 100);

        $invoice->setAmountHt(round($amountHt, 2));
        $invoice->setVatRate($vatRate);
        $invoice->setVatAmount(round($vatAmount, 2));
        $invoice->setAmountTtc(round($amountHt + $vatAmount, 2));
        $order->setInvoice($invoice);

        $em->persist($invoice);

        $em->flush();

        return $this->json($order, 201, [], ['groups' => 'order:read']);
    }

    #[Route('', name: 'api_orders_list', methods: ['GET'])]
    public function getUserOrders(OrderRepository $orderRepository): JsonResponse
    {
        // 1. On récupère l'utilisateur connecté via son token
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        // 2. On demande au Repository de chercher uniquement SES commandes
        // On les trie par date décroissante (DESC) pour avoir la plus récente en haut
        $orders = $orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->json($orders, 200, [], ['groups' => 'order:read']);
    }

    /**
     * Toutes les commandes, paginées, pour le back-office (ROLE_ADMIN via security.yaml).
     */
    #[Route('/all', name: 'api_orders_all', methods: ['GET'])]
    public function getAllOrders(Request $request, OrderRepository $orderRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 0);
        $size = $request->query->getInt('size', 20);

        $data = $orderRepository->getPaginatedOrders($page, $size);

        return $this->json($data, 200, [], ['groups' => 'order:read']);
    }

    /**
     * Statistiques du tableau de bord admin.
     * Pas d'entité Subscription côté Symfony : on expose le nombre de produits actifs.
     */
    #[Route('/stats', name: 'api_orders_stats', methods: ['GET'])]
    public function getStats(
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        ProductRepository $productRepository,
    ): JsonResponse {
        return $this->json([
            'totalOrders' => $orderRepository->count([]),
            'totalRevenueHt' => round($orderRepository->getTotalRevenueHt(), 2),
            'totalUsers' => $userRepository->count([]),
            'activeProducts' => $productRepository->count(['isActive' => true]),
        ]);
    }

    /**
     * Changement de statut d'une commande par un admin.
     */
    #[Route('/{id}/status', name: 'api_orders_update_status', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function updateOrderStatus(Order $order, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $allowedStatuses = ['PENDING', 'VALIDATED', 'PROCESSING', 'COMPLETED', 'CANCELLED', 'REFUNDED'];
        $status = $request->toArray()['status'] ?? null;

        if (!in_array($status, $allowedStatuses, true)) {
            return $this->json([
                'message' => 'Statut invalide. Attendu : ' . implode(', ', $allowedStatuses),
            ], 400);
        }

        $order->setStatus($status);
        $em->flush();

        return $this->json($order, 200, [], ['groups' => 'order:read']);
    }
}
