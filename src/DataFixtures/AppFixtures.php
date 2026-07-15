<?php

namespace App\DataFixtures;

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
        $admin = new User();$admin->setEmail('admin@cyna-it.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));$admin->setFirstName('Merlin');
        $admin->setLastName('Admin');$admin->setIsVerified(true);
        $admin->setIsActive(true);$admin->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($admin);

        $client = new User();$client->setEmail('client@cyna-it.fr');
        $client->setRoles(['ROLE_USER']);
        $client->setPassword($this->hasher->hashPassword($client, 'client123'));$client->setFirstName('Hugo');
        $client->setLastName('Client');$client->setIsVerified(true);
        $client->setIsActive(true);$client->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($client);

        // 2. CRÉATION DES CATÉGORIES CYBERSÉCURITÉ
        $categoriesData = [
            ['name' => 'Détection des Menaces (SOC)', 'slug' => 'soc'],
            ['name' => 'Protection des Terminaux (EDR)', 'slug' => 'edr'],
            ['name' => 'Protection Étendue (XDR)', 'slug' => 'xdr'],
        ];

        $categories = [];
        foreach ($categoriesData as$data) {
            $category = new Category();$category->setName($data['name']);$category->setSlug($data['slug']);$category->setDescription($faker->paragraph());$category->setIsActive(true);
            $manager->persist($category);
            $categories[] =$category;
        }

        // 3. CRÉATION DES PRODUITS SAAS
        $productsData = [
            [
                'name' => 'Cyna SOC Pro',
                'slug' => 'cyna-soc-pro',
                'catIndex' => 0,
                'priceM' => '99.90',
                'priceY' => '999.00',
                'features' => ['monitoring' => '24/7', 'sla' => '2h', 'threat_hunting' => true]
            ],
            [
                'name' => 'Cyna EDR Advanced',
                'slug' => 'cyna-edr-advanced',
                'catIndex' => 1,
                'priceM' => '49.90',
                'priceY' => '499.00',
                'features' => ['endpoints' => 50, 'anti_ransomware' => true, 'ai_detection' => true]
            ],
            [
                'name' => 'Cyna XDR Ultimate',
                'slug' => 'cyna-xdr-ultimate',
                'catIndex' => 2,
                'priceM' => '149.90',
                'priceY' => '1499.00',
                'features' => ['cloud_integration' => true, 'automated_response' => true]
            ]
        ];

        $priority = 1;
        foreach ($productsData as $data) {$product = new Product();
            $product->setName($data['name']);
            $product->setSlug($data['slug']);
            $product->setCategory($categories[$data['catIndex']]);
            $product->setShortDescription($faker->sentence());
            $product->setDescription($faker->paragraphs(3, true));
            $product->setPriceMonthly($data['priceM']);
            $product->setPriceYearly($data['priceY']);
            $product->setFeatures($data['features']);
            $product->setDisplayPriority($priority++);$product->setIsActive(true);
            $manager->persist($product);
        }

        // 4. SAUVEGARDE EN BASE DE DONNÉES
        $manager->flush();
    }
}
