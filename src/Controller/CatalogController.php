<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductRepository;
use App\Entity\Product;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
#[Route('/api')]
class CatalogController extends AbstractController
{
    #[Route('/categories', name: 'api_categories', methods: ['GET'])]
    public function getCategories(CategoryRepository $categoryRepository): JsonResponse
    {
        // On récupère uniquement les catégories actives grâce au repository
        $categories = $categoryRepository->findBy(['isActive' => true]);

        // La fonction $this->json() transforme automatiquement les objets en JSON
        // en utilisant uniquement les champs du groupe 'category:read'
        return $this->json($categories, 200, [], ['groups' => 'category:read']);
    }
    #[Route('/categories', name: 'api_categories_create', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();

        $category = new Category();
        $category->setName($data['name'] ?? '');
        $category->setSlug($data['slug'] ?? '');
        $category->setDescription($data['description'] ?? null);
        $category->setImageUrl($data['imageUrl'] ?? null);

        // Par défaut, la catégorie est active
        $category->setIsActive(true);

        $em->persist($category);
        $em->flush();

        return $this->json($category, 201, [], ['groups' => 'category:read']);
    }
    #[Route('/categories/{id}', name: 'api_categories_update', methods: ['PUT'])]
    public function updateCategory(Category $category, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();

        if (isset($data['name'])) $category->setName($data['name']);
        if (isset($data['slug'])) $category->setSlug($data['slug']);
        if (isset($data['description'])) $category->setDescription($data['description']);
        if (isset($data['imageUrl'])) $category->setImageUrl($data['imageUrl']);
        if (isset($data['active'])) $category->setIsActive($data['active']);

        $em->flush();

        return $this->json($category, 200, [], ['groups' => 'category:read']);
    }
    #[Route('/categories/{id}', name: 'api_categories_delete', methods: ['DELETE'])]
    public function deleteCategory(Category $category, EntityManagerInterface $em): JsonResponse
    {
        // Soft delete : on désactive simplement la catégorie
        $category->setIsActive(false);
        $em->flush();

        return $this->json(null, 204);
    }
    #[Route('/products', name: 'api_products', methods: ['GET'])]
    public function getProducts(Request $request, ProductRepository $productRepository): JsonResponse
    {
        // On récupère les paramètres de l'URL (ex: ?page=0&size=24&q=SOC)
        // Les valeurs par défaut correspondent à ton fichier catalog.ts
        $page = $request->query->getInt('page', 0);
        $size = $request->query->getInt('size', 24);
        $categoryId = $request->query->get('categoryId');
        $search = $request->query->get('q');

        $data = $productRepository->getPaginatedProducts($page, $size, $categoryId, $search);

        // On renvoie le JSON en utilisant le groupe de notre entité Product
        return $this->json($data, 200, [], ['groups' => 'product:read']);
    }
    #[Route('/products', name: 'api_products_create', methods: ['POST'])]
    public function createProduct(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepo): JsonResponse
    {
        // On transforme le JSON reçu du Front en tableau PHP
        $data = $request->toArray();

        $product = new Product();
        $product->setName($data['name'] ?? '');
        $product->setSlug($data['slug'] ?? '');
        $product->setShortDescription($data['shortDescription'] ?? null);
        $product->setDescription($data['description'] ?? null);
        $product->setPriceMonthly($data['priceMonthly'] ?? 0);
        $product->setPriceYearly($data['priceYearly'] ?? 0);
        $product->setImageUrl($data['imageUrl'] ?? null);
        $product->setFeatures($data['features'] ?? null);
        $product->setDisplayPriority($data['displayPriority'] ?? 1);

        // Par défaut, un nouveau produit est actif
        $product->setIsActive(true);

        // Gestion de la catégorie
        if (!empty($data['categoryId'])) {
            $category = $categoryRepo->find($data['categoryId']);
            $product->setCategory($category);
        }

        // On sauvegarde en base
        $em->persist($product);
        $em->flush();

        return $this->json($product, 201, [], ['groups' => 'product:read']);
    }
    #[Route('/products/{id}', name: 'api_products_update', methods: ['PUT'])]
    public function updateProduct(Product $product, Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepo): JsonResponse
    {
        $data = $request->toArray();

        // On met à jour les champs si on les reçoit dans le JSON
        if (isset($data['name'])) $product->setName($data['name']);
        if (isset($data['slug'])) $product->setSlug($data['slug']);
        if (isset($data['shortDescription'])) $product->setShortDescription($data['shortDescription']);
        if (isset($data['description'])) $product->setDescription($data['description']);
        if (isset($data['priceMonthly'])) $product->setPriceMonthly($data['priceMonthly']);
        if (isset($data['priceYearly'])) $product->setPriceYearly($data['priceYearly']);
        if (isset($data['imageUrl'])) $product->setImageUrl($data['imageUrl']);
        if (isset($data['features'])) $product->setFeatures($data['features']);
        if (isset($data['displayPriority'])) $product->setDisplayPriority($data['displayPriority']);

        // Réactivation potentielle via PUT (comme demandé dans admin.ts)
        if (isset($data['active'])) $product->setIsActive($data['active']);

        if (!empty($data['categoryId'])) {
            $category = $categoryRepo->find($data['categoryId']);
            $product->setCategory($category);
        }

        // flush() suffit pour une mise à jour (pas besoin de persist)
        $em->flush();

        return $this->json($product, 200, [], ['groups' => 'product:read']);
    }
    #[Route('/products/{id}', name: 'api_products_delete', methods: ['DELETE'])]
    public function deleteProduct(Product $product, EntityManagerInterface $em): JsonResponse
    {
        // On ne fait pas un vrai $em->remove($product) !
        // On passe simplement le produit en inactif (Soft Delete)
        $product->setIsActive(false);
        $em->flush();

        // Le code HTTP 204 signifie "Action réussie, pas de contenu à renvoyer"
        return $this->json(null, 204);
    }
}
