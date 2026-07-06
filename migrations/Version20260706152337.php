<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260706152337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial PAS domain schema';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(40) NOT NULL, label VARCHAR(120) NOT NULL, scheduled_at DATETIME NOT NULL, location VARCHAR(120) DEFAULT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_AC74095AB03A8386 (created_by_id), INDEX idx_activity_type (type), INDEX idx_activity_scheduled_at (scheduled_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE activity_participation (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) NOT NULL, checked_at DATETIME DEFAULT NULL, inmate_id INT NOT NULL, activity_id INT NOT NULL, checked_by_id INT DEFAULT NULL, INDEX IDX_AA872B8B63CB3D1E (inmate_id), INDEX IDX_AA872B8B81C06096 (activity_id), INDEX IDX_AA872B8B2199DB86 (checked_by_id), INDEX idx_activity_participation_status (status), UNIQUE INDEX uniq_activity_inmate_participation (activity_id, inmate_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE assignment (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME DEFAULT NULL, reason LONGTEXT DEFAULT NULL, inmate_id INT NOT NULL, cell_id INT NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_30C544BA63CB3D1E (inmate_id), INDEX IDX_30C544BACB39D93A (cell_id), INDEX IDX_30C544BAB03A8386 (created_by_id), INDEX idx_assignment_end_at (end_at), INDEX idx_assignment_start_at (start_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(80) NOT NULL, entity_class VARCHAR(180) NOT NULL, entity_id INT DEFAULT NULL, created_at DATETIME NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, details JSON NOT NULL, actor_id INT DEFAULT NULL, INDEX IDX_F6E1C0F510DAF24A (actor_id), INDEX idx_audit_log_action (action), INDEX idx_audit_log_created_at (created_at), INDEX idx_audit_log_entity (entity_class, entity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE building (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, code VARCHAR(30) NOT NULL, address VARCHAR(255) DEFAULT NULL, active TINYINT NOT NULL, UNIQUE INDEX uniq_building_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cell (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(30) NOT NULL, capacity INT NOT NULL, status VARCHAR(30) NOT NULL, wing_id INT NOT NULL, INDEX IDX_CB8787E282AB75FD (wing_id), INDEX idx_cell_status (status), UNIQUE INDEX uniq_cell_number_wing (number, wing_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE incident (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(160) NOT NULL, description LONGTEXT NOT NULL, severity VARCHAR(30) NOT NULL, occurred_at DATETIME NOT NULL, status VARCHAR(30) NOT NULL, cell_id INT DEFAULT NULL, reported_by_id INT DEFAULT NULL, INDEX IDX_3D03A11ACB39D93A (cell_id), INDEX IDX_3D03A11A71CE806 (reported_by_id), INDEX idx_incident_severity (severity), INDEX idx_incident_occurred_at (occurred_at), INDEX idx_incident_status (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE incident_inmate (incident_id INT NOT NULL, inmate_id INT NOT NULL, INDEX IDX_E3D52D1559E53FB9 (incident_id), INDEX IDX_E3D52D1563CB3D1E (inmate_id), PRIMARY KEY (incident_id, inmate_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inmate (id INT AUTO_INCREMENT NOT NULL, uid VARCHAR(50) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, birth_date DATE NOT NULL, status VARCHAR(30) NOT NULL, security_level VARCHAR(30) NOT NULL, arrival_date DATE NOT NULL, release_date DATE DEFAULT NULL, INDEX idx_inmate_status (status), INDEX idx_inmate_security_level (security_level), UNIQUE INDEX uniq_inmate_uid (uid), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(180) NOT NULL, channel VARCHAR(30) NOT NULL, status VARCHAR(30) NOT NULL, sent_at DATETIME DEFAULT NULL, recipient_id INT NOT NULL, INDEX IDX_BF5476CAE92F8F78 (recipient_id), INDEX idx_notification_status (status), INDEX idx_notification_sent_at (sent_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transfer (id INT AUTO_INCREMENT NOT NULL, external_destination VARCHAR(255) DEFAULT NULL, type VARCHAR(30) NOT NULL, reason LONGTEXT NOT NULL, scheduled_at DATETIME NOT NULL, inmate_id INT NOT NULL, from_cell_id INT DEFAULT NULL, to_cell_id INT DEFAULT NULL, validated_by_id INT DEFAULT NULL, INDEX IDX_4034A3C063CB3D1E (inmate_id), INDEX IDX_4034A3C04D673A95 (from_cell_id), INDEX IDX_4034A3C045A1E4CF (to_cell_id), INDEX IDX_4034A3C0C69DE5E5 (validated_by_id), INDEX idx_transfer_scheduled_at (scheduled_at), INDEX idx_transfer_type (type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, profile_type VARCHAR(20) NOT NULL, service VARCHAR(100) DEFAULT NULL, super_admin TINYINT DEFAULT NULL, managed_building_id INT DEFAULT NULL, badge_number VARCHAR(50) DEFAULT NULL, assigned_zone_id INT DEFAULT NULL, INDEX IDX_8D93D6495C971FA5 (managed_building_id), UNIQUE INDEX UNIQ_8D93D649A42CBC11 (badge_number), INDEX IDX_8D93D64995B48724 (assigned_zone_id), UNIQUE INDEX uniq_user_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE wing (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, floor INT NOT NULL, building_id INT NOT NULL, INDEX IDX_B80205444D2A7E12 (building_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE activity_participation ADD CONSTRAINT FK_AA872B8B63CB3D1E FOREIGN KEY (inmate_id) REFERENCES inmate (id)');
        $this->addSql('ALTER TABLE activity_participation ADD CONSTRAINT FK_AA872B8B81C06096 FOREIGN KEY (activity_id) REFERENCES activity (id)');
        $this->addSql('ALTER TABLE activity_participation ADD CONSTRAINT FK_AA872B8B2199DB86 FOREIGN KEY (checked_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BA63CB3D1E FOREIGN KEY (inmate_id) REFERENCES inmate (id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BACB39D93A FOREIGN KEY (cell_id) REFERENCES cell (id)');
        $this->addSql('ALTER TABLE assignment ADD CONSTRAINT FK_30C544BAB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F510DAF24A FOREIGN KEY (actor_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE cell ADD CONSTRAINT FK_CB8787E282AB75FD FOREIGN KEY (wing_id) REFERENCES wing (id)');
        $this->addSql('ALTER TABLE incident ADD CONSTRAINT FK_3D03A11ACB39D93A FOREIGN KEY (cell_id) REFERENCES cell (id)');
        $this->addSql('ALTER TABLE incident ADD CONSTRAINT FK_3D03A11A71CE806 FOREIGN KEY (reported_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE incident_inmate ADD CONSTRAINT FK_E3D52D1559E53FB9 FOREIGN KEY (incident_id) REFERENCES incident (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE incident_inmate ADD CONSTRAINT FK_E3D52D1563CB3D1E FOREIGN KEY (inmate_id) REFERENCES inmate (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transfer ADD CONSTRAINT FK_4034A3C063CB3D1E FOREIGN KEY (inmate_id) REFERENCES inmate (id)');
        $this->addSql('ALTER TABLE transfer ADD CONSTRAINT FK_4034A3C04D673A95 FOREIGN KEY (from_cell_id) REFERENCES cell (id)');
        $this->addSql('ALTER TABLE transfer ADD CONSTRAINT FK_4034A3C045A1E4CF FOREIGN KEY (to_cell_id) REFERENCES cell (id)');
        $this->addSql('ALTER TABLE transfer ADD CONSTRAINT FK_4034A3C0C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6495C971FA5 FOREIGN KEY (managed_building_id) REFERENCES building (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64995B48724 FOREIGN KEY (assigned_zone_id) REFERENCES wing (id)');
        $this->addSql('ALTER TABLE wing ADD CONSTRAINT FK_B80205444D2A7E12 FOREIGN KEY (building_id) REFERENCES building (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AB03A8386');
        $this->addSql('ALTER TABLE activity_participation DROP FOREIGN KEY FK_AA872B8B63CB3D1E');
        $this->addSql('ALTER TABLE activity_participation DROP FOREIGN KEY FK_AA872B8B81C06096');
        $this->addSql('ALTER TABLE activity_participation DROP FOREIGN KEY FK_AA872B8B2199DB86');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BA63CB3D1E');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BACB39D93A');
        $this->addSql('ALTER TABLE assignment DROP FOREIGN KEY FK_30C544BAB03A8386');
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F510DAF24A');
        $this->addSql('ALTER TABLE cell DROP FOREIGN KEY FK_CB8787E282AB75FD');
        $this->addSql('ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11ACB39D93A');
        $this->addSql('ALTER TABLE incident DROP FOREIGN KEY FK_3D03A11A71CE806');
        $this->addSql('ALTER TABLE incident_inmate DROP FOREIGN KEY FK_E3D52D1559E53FB9');
        $this->addSql('ALTER TABLE incident_inmate DROP FOREIGN KEY FK_E3D52D1563CB3D1E');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE transfer DROP FOREIGN KEY FK_4034A3C063CB3D1E');
        $this->addSql('ALTER TABLE transfer DROP FOREIGN KEY FK_4034A3C04D673A95');
        $this->addSql('ALTER TABLE transfer DROP FOREIGN KEY FK_4034A3C045A1E4CF');
        $this->addSql('ALTER TABLE transfer DROP FOREIGN KEY FK_4034A3C0C69DE5E5');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6495C971FA5');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64995B48724');
        $this->addSql('ALTER TABLE wing DROP FOREIGN KEY FK_B80205444D2A7E12');
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE activity_participation');
        $this->addSql('DROP TABLE assignment');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE building');
        $this->addSql('DROP TABLE cell');
        $this->addSql('DROP TABLE incident');
        $this->addSql('DROP TABLE incident_inmate');
        $this->addSql('DROP TABLE inmate');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE transfer');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE wing');
    }
}
