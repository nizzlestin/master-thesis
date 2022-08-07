<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220725191807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE repo ADD cloned TINYINT(1) NOT NULL, ADD calculated_golang_metrics TINYINT(1) NOT NULL, ADD calculated_rust_metrics TINYINT(1) NOT NULL, ADD calculated_custom_metrics TINYINT(1) NOT NULL, DROP is_cloned, DROP has_calculated_golang_metrics, DROP has_calculated_rust_metrics, DROP has_calculated_custom_metrics');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE repo ADD is_cloned TINYINT(1) NOT NULL, ADD has_calculated_golang_metrics TINYINT(1) NOT NULL, ADD has_calculated_rust_metrics TINYINT(1) NOT NULL, ADD has_calculated_custom_metrics TINYINT(1) NOT NULL, DROP cloned, DROP calculated_golang_metrics, DROP calculated_rust_metrics, DROP calculated_custom_metrics');
    }
}
