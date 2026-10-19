<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Les formulaires de larecreetech.com reçus par l'app (à la place de Formspree) : les candidatures à la cohorte et les contacts du quiz.
 */
final class Version20261019200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Candidatures à la cohorte et contacts du quiz (formulaires de larecreetech.com, sans Formspree).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, prenom VARCHAR(100) NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(40) DEFAULT NULL, situation VARCHAR(40) NOT NULL, lien VARCHAR(500) DEFAULT NULL, niveau_claude VARCHAR(40) NOT NULL, idee VARCHAR(60) NOT NULL, projet LONGTEXT NOT NULL, disponibilite VARCHAR(60) NOT NULL, source VARCHAR(40) NOT NULL, cohorte SMALLINT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_quiz (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, email VARCHAR(180) NOT NULL, profil VARCHAR(40) DEFAULT NULL, UNIQUE INDEX UNIQ_contact_quiz_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE contact_quiz');
        $this->addSql('DROP TABLE candidature');
    }
}
