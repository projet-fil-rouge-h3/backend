<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Invoice;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
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
        // 1. Vérifier que l'utilisateur est bien connecté via son Token JWT
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Vous devez être connecté pour commander.'], 401);
        }

        $data = $request->toArray();
        $itemsData = $data['items'] ?? [];

        if (empty($itemsData)) {
            return $this->json(['message' => 'Le panier est vide.'], 400);
        }

        // 2. Initialiser la Commande
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('VALIDATED'); // Ou 'PENDING' si tu as un module de paiement type Stripe plus tard
        $order->setCurrency('EUR');
        $order->setCreatedAt(new \DateTimeImmutable());
        $totalAmount = 0.0;

        // 3. Traiter chaque ligne du panier
        foreach ($itemsData as $itemData) {
            $product = $productRepo->find($itemData['productId']);

            if (!$product || !$product->isActive()) {
                continue; // On ignore les produits qui n'existent pas ou sont désactivés
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setProductName($product->getName());
            $orderItem->setQuantity($itemData['quantity'] ?? 1);
            $orderItem->setBillingPeriod($itemData['billingPeriod'] ?? 'monthly');

            // On récupère le VRAI prix en base selon la période choisie
            $unitPrice = ($orderItem->getBillingPeriod() === 'yearly')
                ? $product->getPriceYearly()
                : $product->getPriceMonthly();

            $orderItem->setUnitPrice($unitPrice);
            $orderItem->setCustomerOrder($order);
            $order->addOrderItem($orderItem);

            $em->persist($orderItem);

            // On ajoute au total de la commande
            $totalAmount += ($unitPrice * $orderItem->getQuantity());
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

        // Calcul de la TVA (On part sur une base de 20% pour du logiciel)
        $vatRate = 20.0;
        $amountHt = $totalAmount / (1 + ($vatRate / 100));
        $vatAmount = $totalAmount - $amountHt;

        $invoice->setAmountHt(round($amountHt, 2));
        $invoice->setVatRate($vatRate);
        $invoice->setVatAmount(round($vatAmount, 2));
        $invoice->setAmountTtc(round($totalAmount, 2));
        $order->setInvoice($invoice);

        $em->persist($invoice);

        // 5. Sauvegarder le tout en base de données de manière transactionnelle
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

        // 3. On renvoie le tout avec le même groupe de lecture
        return $this->json($orders, 200, [], ['groups' => 'order:read']);
    }
}
