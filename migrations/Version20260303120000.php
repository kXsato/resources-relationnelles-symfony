<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260303120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add author relation between Resource and User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource ADD author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BC91F416F675F31B ON resource (author_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416F675F31B');
        $this->addSql('DROP INDEX IDX_BC91F416F675F31B ON resource');
        $this->addSql('ALTER TABLE resource DROP author_id');
    }
}
