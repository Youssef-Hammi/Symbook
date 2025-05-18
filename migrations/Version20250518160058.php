<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250518160058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT '(DC2Type:json)', password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book ADD category_id INT DEFAULT NULL, ADD title VARCHAR(255) NOT NULL, ADD author VARCHAR(255) NOT NULL, ADD price DOUBLE PRECISION NOT NULL, ADD description VARCHAR(255) NOT NULL, ADD image VARCHAR(255) NOT NULL, ADD stock INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book ADD CONSTRAINT FK_CBE5A33112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CBE5A33112469DE2 ON book (category_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cart_item ADD user_id INT DEFAULT NULL, ADD book_id INT DEFAULT NULL, ADD quantity INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE2527A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE252716A2B381 FOREIGN KEY (book_id) REFERENCES book (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F0FE2527A76ED395 ON cart_item (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F0FE252716A2B381 ON cart_item (book_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category DROP book
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_verification ADD user_id INT DEFAULT NULL, ADD token VARCHAR(255) NOT NULL, ADD created_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_verification ADD CONSTRAINT FK_FE22358A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE22358A76ED395 ON email_verification (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD user_id INT DEFAULT NULL, ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD status VARCHAR(255) NOT NULL, ADD total_price DOUBLE PRECISION NOT NULL, ADD payment_method VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F5299398A76ED395 ON `order` (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_item ADD order_id INT DEFAULT NULL, ADD book_id INT DEFAULT NULL, ADD quantity INT NOT NULL, ADD price DOUBLE PRECISION NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F0916A2B381 FOREIGN KEY (book_id) REFERENCES book (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_52EA1F0916A2B381 ON order_item (book_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE password_reset ADD user_id INT DEFAULT NULL, ADD token VARCHAR(255) NOT NULL, ADD created_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE password_reset ADD CONSTRAINT FK_B1017252A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B1017252A76ED395 ON password_reset (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ADD order_id INT DEFAULT NULL, ADD payment_status VARCHAR(255) NOT NULL, ADD payment_date DATETIME NOT NULL, ADD stripe_payment_id VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ADD CONSTRAINT FK_6D28840D8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_6D28840D8D9F6D38 ON payment (order_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE2527A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_verification DROP FOREIGN KEY FK_FE22358A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE password_reset DROP FOREIGN KEY FK_B1017252A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book DROP FOREIGN KEY FK_CBE5A33112469DE2
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CBE5A33112469DE2 ON book
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book DROP category_id, DROP title, DROP author, DROP price, DROP description, DROP image, DROP stock
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE252716A2B381
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F0FE2527A76ED395 ON cart_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F0FE252716A2B381 ON cart_item
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cart_item DROP user_id, DROP book_id, DROP quantity
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE category ADD book VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FE22358A76ED395 ON email_verification
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE email_verification DROP user_id, DROP token, DROP created_at
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F5299398A76ED395 ON `order`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `order` DROP user_id, DROP created_at, DROP status, DROP total_price, DROP payment_method
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F0916A2B381
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_52EA1F098D9F6D38 ON order_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_52EA1F0916A2B381 ON order_item
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_item DROP order_id, DROP book_id, DROP quantity, DROP price
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B1017252A76ED395 ON password_reset
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE password_reset DROP user_id, DROP token, DROP created_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D8D9F6D38
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_6D28840D8D9F6D38 ON payment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment DROP order_id, DROP payment_status, DROP payment_date, DROP stripe_payment_id
        SQL);
    }
}
