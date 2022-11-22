<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221119192932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD status VARCHAR(255) DEFAULT NULL, DROP cloned, DROP golang_metrics_calculated, DROP rust_metrics_calculated, DROP custom_metrics_calculated');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project ADD cloned TINYINT(1) NOT NULL, ADD golang_metrics_calculated TINYINT(1) NOT NULL, ADD rust_metrics_calculated TINYINT(1) NOT NULL, ADD custom_metrics_calculated TINYINT(1) NOT NULL, DROP status');
    }
}
