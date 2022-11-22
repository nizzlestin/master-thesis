<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221118084139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', total_commits INT DEFAULT NULL, evaluated_commits INT DEFAULT NULL, cloned_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', latest_known_commit VARCHAR(255) DEFAULT NULL, cloned TINYINT(1) NOT NULL, golang_metrics_calculated TINYINT(1) NOT NULL, rust_metrics_calculated TINYINT(1) NOT NULL, custom_metrics_calculated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_2FB3D0EEF47645AE (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE statistic (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, commit VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, file_basename VARCHAR(255) NOT NULL, file VARCHAR(255) NOT NULL, byte INT DEFAULT NULL, blank INT DEFAULT NULL, comment INT DEFAULT NULL, code INT DEFAULT NULL, complexity INT DEFAULT NULL, commit_date DATETIME DEFAULT NULL, INDEX commit_idx (commit), INDEX file_idx (file), INDEX file_base_idx (file_basename), INDEX project_idx (project_id), INDEX date_idx (commit_date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE statistic_file (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, filename VARCHAR(255) NOT NULL, function_name VARCHAR(255) NOT NULL, INDEX IDX_8BE47249166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT FK_649B469C166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE statistic_file ADD CONSTRAINT FK_8BE47249166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE statistic DROP FOREIGN KEY FK_649B469C166D1F9C');
        $this->addSql('ALTER TABLE statistic_file DROP FOREIGN KEY FK_8BE47249166D1F9C');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE statistic');
        $this->addSql('DROP TABLE statistic_file');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
