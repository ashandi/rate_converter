<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230708122125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE currency (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_6956883F5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rate (id INT AUTO_INCREMENT NOT NULL, currency_from_id INT NOT NULL, currency_to_id INT NOT NULL, datetime DATETIME NOT NULL, rate DOUBLE PRECISION NOT NULL, INDEX IDX_DFEC3F39A56723E4 (currency_from_id), INDEX IDX_DFEC3F3967D74803 (currency_to_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rate ADD CONSTRAINT FK_DFEC3F39A56723E4 FOREIGN KEY (currency_from_id) REFERENCES currency (id)');
        $this->addSql('ALTER TABLE rate ADD CONSTRAINT FK_DFEC3F3967D74803 FOREIGN KEY (currency_to_id) REFERENCES currency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rate DROP FOREIGN KEY FK_DFEC3F39A56723E4');
        $this->addSql('ALTER TABLE rate DROP FOREIGN KEY FK_DFEC3F3967D74803');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE rate');
    }
}
