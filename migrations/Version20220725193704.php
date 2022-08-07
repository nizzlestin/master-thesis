<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220725193704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE repo ADD golang_metrics_calculated TINYINT(1) NOT NULL, ADD rust_metrics_calculated TINYINT(1) NOT NULL, ADD custom_metrics_calculated TINYINT(1) NOT NULL, DROP calculated_golang_metrics, DROP calculated_rust_metrics, DROP calculated_custom_metrics');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE repo ADD calculated_golang_metrics TINYINT(1) NOT NULL, ADD calculated_rust_metrics TINYINT(1) NOT NULL, ADD calculated_custom_metrics TINYINT(1) NOT NULL, DROP golang_metrics_calculated, DROP rust_metrics_calculated, DROP custom_metrics_calculated');
    }
}
