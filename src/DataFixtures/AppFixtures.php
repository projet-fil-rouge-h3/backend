<?php

namespace App\DataFixtures;

use App\Entity\CarouselSlide;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // 1. CRÉATION DES UTILISATEURS
        $admin = new User();
        $admin->setEmail('admin@cyna-it.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $admin->setFirstName('Admin'); 
        $admin->setLastName('System');
        $admin->setIsVerified(true);
        $admin->setIsActive(true);
        $admin->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($admin);

        $client = new User();
        $client->setEmail('client@cyna-it.fr');
        $client->setRoles(['ROLE_USER']);
        $client->setPassword($this->hasher->hashPassword($client, 'client123'));
        $client->setFirstName('Client'); 
        $client->setLastName('Test');
        $client->setIsVerified(true);
        $client->setIsActive(true);
        $client->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($client);

        // 2. CRÉATION DES CATÉGORIES CYBERSÉCURITÉ
        $categoriesData = [
            ['name' => 'Détection des Menaces (SOC)', 'slug' => 'soc'],
            ['name' => 'Protection des Terminaux (EDR)', 'slug' => 'edr'],
            ['name' => 'Protection Étendue (XDR)', 'slug' => 'xdr'],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setSlug($data['slug']);
            $category->setDescription($faker->paragraph());
            $category->setIsActive(true);
            $manager->persist($category);
            $categories[] = $category;
        }

        // 3. CRÉATION DES PRODUITS SAAS
        $productsData = [
            [
                'name' => 'Cyna SOC Pro',
                'slug' => 'cyna-soc-pro',
                'catIndex' => 0,
                'priceM' => '99.90',
                'priceY' => '999.00',
                'features' => ['monitoring' => '24/7', 'sla' => '2h', 'threat_hunting' => true],
                'imageUrl' => '/src/assets/products/soc-premium.svg'
            ],
            [
                'name' => 'Cyna EDR Advanced',
                'slug' => 'cyna-edr-advanced',
                'catIndex' => 1,
                'priceM' => '49.90',
                'priceY' => '499.00',
                'features' => ['endpoints' => 50, 'anti_ransomware' => true, 'ai_detection' => true],
                'imageUrl' => '/src/assets/products/edr-pro.svg'
            ],
            [
                'name' => 'Cyna XDR Ultimate',
                'slug' => 'cyna-xdr-ultimate',
                'catIndex' => 2,
                'priceM' => '149.90',
                'priceY' => '1499.00',
                'features' => ['cloud_integration' => true, 'automated_response' => true],
                'imageUrl' => '/src/assets/products/xdr-ultimate.svg'
            ]
        ];

        $priority = 1;
        foreach ($productsData as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setSlug($data['slug']);
            $product->setCategory($categories[$data['catIndex']]);
            $product->setShortDescription($faker->sentence());
            $product->setDescription($faker->paragraphs(3, true));
            $product->setPriceMonthly($data['priceM']);
            $product->setPriceYearly($data['priceY']);
            $product->setFeatures($data['features']);
            $product->setDisplayPriority($priority++);
            $product->setIsActive(true);
            
            // Ajout de l'image pour le produit
            if (method_exists($product, 'setImageUrl')) {
                $product->setImageUrl($data['imageUrl']);
            }
            
            $manager->persist($product);
        }

        // 4. CRÉATION DES SLIDES DU CAROUSEL (page d'accueil)
        $slidesData = [
            [
                'title' => 'Protégez votre entreprise 24/7',
                'subtitle' => 'SOC managé par nos experts cybersécurité',
                'imageUrl' => '/src/assets/hero.png', 
                'linkUrl' => '/catalog?cat=soc',
            ],
            [
                'title' => 'Vos terminaux sous haute protection',
                'subtitle' => 'EDR nouvelle génération avec détection par IA',
                'imageUrl' => '/src/assets/products/edr-starter.svg',
                'linkUrl' => '/catalog?cat=edr',
            ],
            [
                'title' => 'Une visibilité totale sur vos menaces',
                'subtitle' => 'XDR : corrélation cloud, réseau et endpoints',
                'imageUrl' => '/src/assets/products/xdr-essential.svg',
                'linkUrl' => '/catalog?cat=xdr',
            ],
        ];

        $order = 1;
        foreach ($slidesData as $data) {
            $slide = new CarouselSlide();
            $slide->setTitle($data['title']);
            $slide->setSubtitle($data['subtitle']);
            $slide->setImageUrl($data['imageUrl']);
            $slide->setLinkUrl($data['linkUrl']);
            $slide->setDisplayOrder($order++);
            $slide->setIsActive(true);
            $manager->persist($slide);
        }

        // 5. SAUVEGARDE EN BASE DE DONNÉES
        $manager->flush();
    }
}