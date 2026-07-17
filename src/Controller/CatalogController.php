<?php

namespace App\Controller;

use App\Repository\CarouselSlideRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductRepository;
use App\Entity\CarouselSlide;
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
    // Liste admin : inclut les catégories désactivées (protégée ROLE_ADMIN dans security.yaml)
    #[Route('/categories/admin', name: 'api_categories_admin', methods: ['GET'])]
    public function getCategoriesAdmin(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findBy([], ['name' => 'ASC']);

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
    // ⚠️ Déclarée AVANT /products/{slug} : sinon "top" serait pris pour un slug
    #[Route('/products/top', name: 'api_products_top', methods: ['GET'])]
    public function getTopProducts(ProductRepository $productRepository): JsonResponse
    {
        // Les produits mis en avant : actifs, triés par priorité d'affichage
        $products = $productRepository->findBy(
            ['isActive' => true],
            ['displayPriority' => 'ASC'],
            4
        );

        return $this->json($products, 200, [], ['groups' => 'product:read']);
    }

    // ⚠️ Déclarée AVANT /products/{slug} + protégée ROLE_ADMIN dans security.yaml
    #[Route('/products/admin', name: 'api_products_admin', methods: ['GET'])]
    public function getProductsAdmin(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 0);
        $size = $request->query->getInt('size', 50);

        // includeInactive: true → le back-office voit aussi les produits désactivés
        $data = $productRepository->getPaginatedProducts($page, $size, null, null, true);

        return $this->json($data, 200, [], ['groups' => 'product:read']);
    }

    #[Route('/products/{slug}', name: 'api_products_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function getProductBySlug(string $slug, ProductRepository $productRepository): JsonResponse
    {
        // Fiche produit publique : uniquement si le produit est actif
        $product = $productRepository->findOneBy(['slug' => $slug, 'isActive' => true]);

        if (!$product) {
            return $this->json(['message' => 'Produit introuvable'], 404);
        }

        return $this->json($product, 200, [], ['groups' => 'product:read']);
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

    #[Route('/carousel', name: 'api_carousel', methods: ['GET'])]
    public function getCarousel(CarouselSlideRepository $carouselRepository): JsonResponse
    {
        // Les slides actifs, dans l'ordre d'affichage défini en back-office
        $slides = $carouselRepository->findBy(['isActive' => true], ['displayOrder' => 'ASC']);

        return $this->json($slides, 200, [], ['groups' => 'carousel:read']);
    }

    // Liste admin : inclut les slides désactivés (protégée ROLE_ADMIN dans security.yaml)
    #[Route('/carousel/admin', name: 'api_carousel_admin', methods: ['GET'])]
    public function getCarouselAdmin(CarouselSlideRepository $carouselRepository): JsonResponse
    {
        $slides = $carouselRepository->findBy([], ['displayOrder' => 'ASC']);

        return $this->json($slides, 200, [], ['groups' => 'carousel:read']);
    }

    #[Route('/carousel', name: 'api_carousel_create', methods: ['POST'])]
    public function createCarouselSlide(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();

        $slide = new CarouselSlide();
        $slide->setTitle($data['title'] ?? '');
        $slide->setSubtitle($data['subtitle'] ?? null);
        $slide->setImageUrl($data['imageUrl'] ?? '');
        $slide->setLinkUrl($data['linkUrl'] ?? null);
        $slide->setDisplayOrder($data['displayOrder'] ?? 1);

        // Par défaut, un nouveau slide est actif
        $slide->setIsActive(true);

        $em->persist($slide);
        $em->flush();

        return $this->json($slide, 201, [], ['groups' => 'carousel:read']);
    }

    #[Route('/carousel/{id}', name: 'api_carousel_update', methods: ['PUT'])]
    public function updateCarouselSlide(CarouselSlide $slide, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = $request->toArray();

        if (isset($data['title'])) $slide->setTitle($data['title']);
        if (isset($data['subtitle'])) $slide->setSubtitle($data['subtitle']);
        if (isset($data['imageUrl'])) $slide->setImageUrl($data['imageUrl']);
        if (isset($data['linkUrl'])) $slide->setLinkUrl($data['linkUrl']);
        if (isset($data['displayOrder'])) $slide->setDisplayOrder($data['displayOrder']);
        if (isset($data['active'])) $slide->setIsActive($data['active']);

        $em->flush();

        return $this->json($slide, 200, [], ['groups' => 'carousel:read']);
    }

    #[Route('/carousel/{id}', name: 'api_carousel_delete', methods: ['DELETE'])]
    public function deleteCarouselSlide(CarouselSlide $slide, EntityManagerInterface $em): JsonResponse
    {
        // Soft delete : on désactive simplement le slide
        $slide->setIsActive(false);
        $em->flush();

        return $this->json(null, 204);
    }
}
