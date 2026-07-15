<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715132130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adress (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, street VARCHAR(255) NOT NULL, street2 VARCHAR(255) DEFAULT NULL, city VARCHAR(100) NOT NULL, postal_code VARCHAR(20) NOT NULL, country VARCHAR(100) NOT NULL, is_default TINYINT NOT NULL, user_id INT NOT NULL, INDEX IDX_5CECC7BEA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, image_url VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, invoice_number VARCHAR(100) NOT NULL, amount_ht NUMERIC(10, 2) NOT NULL, vat_rate NUMERIC(5, 2) NOT NULL, vat_amount NUMERIC(10, 2) NOT NULL, amount_ttc NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, issued_at DATETIME NOT NULL, customer_order_id INT NOT NULL, UNIQUE INDEX UNIQ_90651744A15A2E17 (customer_order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(50) NOT NULL, total_ammount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, product_name VARCHAR(150) NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, billing_period VARCHAR(50) NOT NULL, customer_order_id INT NOT NULL, product_id INT DEFAULT NULL, INDEX IDX_52EA1F09A15A2E17 (customer_order_id), INDEX IDX_52EA1F094584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, short_description VARCHAR(255) DEFAULT NULL, price_monthly NUMERIC(10, 2) NOT NULL, price_yearly NUMERIC(10, 2) NOT NULL, image_url VARCHAR(255) DEFAULT NULL, features JSON DEFAULT NULL, display_priority INT NOT NULL, is_active TINYINT NOT NULL, category_id INT DEFAULT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE adress ADD CONSTRAINT FK_5CECC7BEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744A15A2E17 FOREIGN KEY (customer_order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09A15A2E17 FOREIGN KEY (customer_order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(20) DEFAULT NULL, ADD created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adress DROP FOREIGN KEY FK_5CECC7BEA76ED395');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744A15A2E17');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09A15A2E17');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('DROP TABLE adress');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE product');
        $this->addSql('ALTER TABLE user DROP phone, DROP created_at');
    }
}
