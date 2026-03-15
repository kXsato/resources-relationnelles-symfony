<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260315154843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quiz_question (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(500) NOT NULL, proposition_a VARCHAR(255) NOT NULL, proposition_b VARCHAR(255) NOT NULL, proposition_c VARCHAR(255) NOT NULL, correct_answer INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_6033B00B81C06096 (activity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE quiz_question ADD CONSTRAINT FK_6033B00B81C06096 FOREIGN KEY (activity_id) REFERENCES activity (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE quiz_question DROP FOREIGN KEY FK_6033B00B81C06096');
        $this->addSql('DROP TABLE quiz_question');
    }
}
