<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220725191055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE repo ADD is_cloned TINYINT(1) NOT NULL, ADD has_calculated_golang_metrics TINYINT(1) NOT NULL, ADD has_calculated_rust_metrics TINYINT(1) NOT NULL, ADD has_calculated_custom_metrics TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE repo DROP is_cloned, DROP has_calculated_golang_metrics, DROP has_calculated_rust_metrics, DROP has_calculated_custom_metrics');
    }
}
