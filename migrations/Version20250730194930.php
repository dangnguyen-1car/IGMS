<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create garage management tables for GarageManagementBundle
 */
final class Version20250730194930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create garage management tables for workflow, cost items, cost allocations, and project COGS';
    }

    public function up(Schema $schema): void
    {
        // Create garage_project_workflow table
        $this->addSql('CREATE TABLE garage_project_workflow (
            id INT AUTO_INCREMENT NOT NULL,  
            project_id INT NOT NULL,
            responsible_user_id INT DEFAULT NULL,
            stage_key VARCHAR(50) NOT NULL,
            stage_name VARCHAR(100) NOT NULL,
            status VARCHAR(50) DEFAULT \'Chưa bắt đầu\' NOT NULL,
            start_time DATETIME DEFAULT NULL,
            end_time DATETIME DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            INDEX IDX_WORKFLOW_PROJECT (project_id),
            INDEX IDX_WORKFLOW_USER (responsible_user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create garage_cost_item table
        $this->addSql('CREATE TABLE garage_cost_item (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            amount NUMERIC(15, 2) NOT NULL,
            category VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT \'forecast\' NOT NULL,
            entry_date DATE NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create garage_cost_allocation table
        $this->addSql('CREATE TABLE garage_cost_allocation (
            id INT AUTO_INCREMENT NOT NULL,
            cost_item_id INT NOT NULL,
            team_id INT NOT NULL,
            percentage NUMERIC(5, 2) NOT NULL,
            INDEX IDX_ALLOCATION_COST_ITEM (cost_item_id),
            INDEX IDX_ALLOCATION_TEAM (team_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create garage_project_cogs table
        $this->addSql('CREATE TABLE garage_project_cogs (
            id INT AUTO_INCREMENT NOT NULL,
            project_id INT NOT NULL,
            cogs_type VARCHAR(50) NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount NUMERIC(15, 2) NOT NULL,
            INDEX IDX_COGS_PROJECT (project_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE garage_project_workflow ADD CONSTRAINT FK_WORKFLOW_PROJECT FOREIGN KEY (project_id) REFERENCES kimai2_projects (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE garage_project_workflow ADD CONSTRAINT FK_WORKFLOW_USER FOREIGN KEY (responsible_user_id) REFERENCES kimai2_users (id) ON DELETE SET NULL');
        
        $this->addSql('ALTER TABLE garage_cost_allocation ADD CONSTRAINT FK_ALLOCATION_COST_ITEM FOREIGN KEY (cost_item_id) REFERENCES garage_cost_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE garage_cost_allocation ADD CONSTRAINT FK_ALLOCATION_TEAM FOREIGN KEY (team_id) REFERENCES kimai2_teams (id) ON DELETE CASCADE');
        
        $this->addSql('ALTER TABLE garage_project_cogs ADD CONSTRAINT FK_COGS_PROJECT FOREIGN KEY (project_id) REFERENCES kimai2_projects (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraints first
        $this->addSql('ALTER TABLE garage_project_workflow DROP FOREIGN KEY FK_WORKFLOW_PROJECT');
        $this->addSql('ALTER TABLE garage_project_workflow DROP FOREIGN KEY FK_WORKFLOW_USER');
        $this->addSql('ALTER TABLE garage_cost_allocation DROP FOREIGN KEY FK_ALLOCATION_COST_ITEM');
        $this->addSql('ALTER TABLE garage_cost_allocation DROP FOREIGN KEY FK_ALLOCATION_TEAM');
        $this->addSql('ALTER TABLE garage_project_cogs DROP FOREIGN KEY FK_COGS_PROJECT');

        // Drop tables
        $this->addSql('DROP TABLE garage_project_workflow');
        $this->addSql('DROP TABLE garage_cost_allocation');
        $this->addSql('DROP TABLE garage_cost_item');
        $this->addSql('DROP TABLE garage_project_cogs');
    }
}
