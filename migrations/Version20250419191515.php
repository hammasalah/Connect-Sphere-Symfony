<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250419191515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE applications (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, enevt_id_id INT NOT NULL, job_id_id INT NOT NULL, status VARCHAR(255) NOT NULL, applied_at VARCHAR(255) DEFAULT NULL, rewarded INT DEFAULT NULL, cover_letter VARCHAR(255) NOT NULL, resume_path VARCHAR(255) NOT NULL, cover_rating INT DEFAULT NULL, INDEX IDX_F7C966F09D86650F (user_id_id), INDEX IDX_F7C966F0A578C4F9 (enevt_id_id), INDEX IDX_F7C966F07E182327 (job_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE comments (id INT AUTO_INCREMENT NOT NULL, post_id_id INT NOT NULL, user_id_id INT NOT NULL, content VARCHAR(255) NOT NULL, time_stamp VARCHAR(255) NOT NULL, is_deleted INT DEFAULT NULL, INDEX IDX_5F9E962AE85F12B8 (post_id_id), INDEX IDX_5F9E962A9D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE conversion (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, points_convertis INT NOT NULL, montant NUMERIC(10, 2) NOT NULL, devise VARCHAR(255) NOT NULL, date DATETIME NOT NULL, INDEX IDX_BD9127449D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, organizer_id_id INT NOT NULL, category_id_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, start_time VARCHAR(255) NOT NULL, end_time VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, image LONGTEXT NOT NULL, points INT NOT NULL, INDEX IDX_5387574AE78C696A (organizer_id_id), INDEX IDX_5387574A9777D11E (category_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE feed_posts (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, event_id_id INT NOT NULL, content VARCHAR(255) NOT NULL, time_stamp VARCHAR(255) NOT NULL, is_deleted INT DEFAULT NULL, created_at VARCHAR(255) NOT NULL, updated_at VARCHAR(255) NOT NULL, score_popularite INT DEFAULT NULL, image_path VARCHAR(255) NOT NULL, group_id INT DEFAULT NULL, INDEX IDX_7DD2E9469D86650F (user_id_id), INDEX IDX_7DD2E9463E5F2F7B (event_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE group_feed_posts (id INT AUTO_INCREMENT NOT NULL, group_id_id INT NOT NULL, user_id_id INT NOT NULL, content VARCHAR(255) NOT NULL, timestamp VARCHAR(255) NOT NULL, media_url VARCHAR(255) NOT NULL, is_deleted INT DEFAULT NULL, INDEX IDX_AA1F88F42F68B530 (group_id_id), INDEX IDX_AA1F88F49D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE group_members (id INT AUTO_INCREMENT NOT NULL, group_it_id INT NOT NULL, user_id_id INT NOT NULL, role VARCHAR(255) NOT NULL, INDEX IDX_C3A086F37F71E2AF (group_it_id), INDEX IDX_C3A086F39D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE historique_points (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, type VARCHAR(255) NOT NULL, points INT NOT NULL, raison VARCHAR(255) NOT NULL, date DATETIME NOT NULL, INDEX IDX_EEF82E759D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE jobs (id INT AUTO_INCREMENT NOT NULL, user_id_id INT DEFAULT NULL, event_id_id INT DEFAULT NULL, job_title VARCHAR(255) NOT NULL, event_title VARCHAR(255) DEFAULT NULL, job_location VARCHAR(255) NOT NULL, employment_type VARCHAR(255) NOT NULL, application_dead_line VARCHAR(255) DEFAULT NULL, min_salary INT NOT NULL, max_salary INT NOT NULL, currency VARCHAR(255) NOT NULL, job_descreption VARCHAR(255) NOT NULL, recruiter_name VARCHAR(255) NOT NULL, recruiter_email VARCHAR(255) NOT NULL, created_at VARCHAR(255) DEFAULT NULL, INDEX IDX_A8936DC59D86650F (user_id_id), INDEX IDX_A8936DC53E5F2F7B (event_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE likes (id INT AUTO_INCREMENT NOT NULL, post_id_id INT NOT NULL, user_id_id INT NOT NULL, time_stamp VARCHAR(255) NOT NULL, INDEX IDX_49CA4E7DE85F12B8 (post_id_id), INDEX IDX_49CA4E7D9D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messages (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, recipient_id INT NOT NULL, content VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, type VARCHAR(255) NOT NULL, read_status INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE participation_events (participation_id INT NOT NULL, events_id INT NOT NULL, INDEX IDX_29E2D16F6ACE3B73 (participation_id), INDEX IDX_29E2D16F9D6A1065 (events_id), PRIMARY KEY(participation_id, events_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE participation_users (participation_id INT NOT NULL, users_id INT NOT NULL, INDEX IDX_1B5F28626ACE3B73 (participation_id), INDEX IDX_1B5F286267B3B43D (users_id), PRIMARY KEY(participation_id, users_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reviews (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, event_id_id INT NOT NULL, rating INT NOT NULL, comment VARCHAR(255) NOT NULL, creatid_at VARCHAR(255) DEFAULT NULL, INDEX IDX_6970EB0F9D86650F (user_id_id), INDEX IDX_6970EB0F3E5F2F7B (event_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE rewards (id INT AUTO_INCREMENT NOT NULL, action_type VARCHAR(255) NOT NULL, points INT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE roulette (id INT AUTO_INCREMENT NOT NULL, user_id_id INT DEFAULT NULL, points_gagnes INT NOT NULL, date DATETIME NOT NULL, INDEX IDX_D80F9D2E9D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE shares (id INT AUTO_INCREMENT NOT NULL, post_id_id INT DEFAULT NULL, user_id_id INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_905F717CE85F12B8 (post_id_id), INDEX IDX_905F717C9D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE transaction_argent (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_groups (id INT AUTO_INCREMENT NOT NULL, creator_id_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, profile_picture LONGTEXT NOT NULL, rules VARCHAR(255) NOT NULL, created_at VARCHAR(255) NOT NULL, INDEX IDX_953F224DF05788E9 (creator_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_intrests (id INT AUTO_INCREMENT NOT NULL, user_id_id INT DEFAULT NULL, category_id_id INT DEFAULT NULL, INDEX IDX_5A3306759D86650F (user_id_id), INDEX IDX_5A3306759777D11E (category_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_messages (id INT AUTO_INCREMENT NOT NULL, user_id_id INT DEFAULT NULL, sent_at DATETIME DEFAULT NULL, last_message VARCHAR(255) DEFAULT NULL, username VARCHAR(255) NOT NULL, INDEX IDX_3B8FFA969D86650F (user_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_profile (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, bio VARCHAR(255) NOT NULL, profile_picture VARCHAR(255) NOT NULL, intrests VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_rewards (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, reward_id INT NOT NULL, event_id INT NOT NULL, points_earned INT NOT NULL, erned_at VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_at VARCHAR(255) DEFAULT NULL, updated_at VARCHAR(255) DEFAULT NULL, points INT DEFAULT NULL, age INT NOT NULL, gender VARCHAR(255) NOT NULL, argent NUMERIC(10, 2) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE visite_utilisateur (id INT AUTO_INCREMENT NOT NULL, dernier_visite DATE NOT NULL, serie INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE worker_raitings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, rater_id INT NOT NULL, job_id INT NOT NULL, raiting INT NOT NULL, comment LONGTEXT NOT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applications ADD CONSTRAINT FK_F7C966F09D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applications ADD CONSTRAINT FK_F7C966F0A578C4F9 FOREIGN KEY (enevt_id_id) REFERENCES events (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applications ADD CONSTRAINT FK_F7C966F07E182327 FOREIGN KEY (job_id_id) REFERENCES jobs (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AE85F12B8 FOREIGN KEY (post_id_id) REFERENCES feed_posts (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A9D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion ADD CONSTRAINT FK_BD9127449D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events ADD CONSTRAINT FK_5387574AE78C696A FOREIGN KEY (organizer_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events ADD CONSTRAINT FK_5387574A9777D11E FOREIGN KEY (category_id_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feed_posts ADD CONSTRAINT FK_7DD2E9469D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feed_posts ADD CONSTRAINT FK_7DD2E9463E5F2F7B FOREIGN KEY (event_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE group_feed_posts ADD CONSTRAINT FK_AA1F88F42F68B530 FOREIGN KEY (group_id_id) REFERENCES user_groups (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE group_feed_posts ADD CONSTRAINT FK_AA1F88F49D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE group_members ADD CONSTRAINT FK_C3A086F37F71E2AF FOREIGN KEY (group_it_id) REFERENCES user_groups (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE group_members ADD CONSTRAINT FK_C3A086F39D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE historique_points ADD CONSTRAINT FK_EEF82E759D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE jobs ADD CONSTRAINT FK_A8936DC59D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE jobs ADD CONSTRAINT FK_A8936DC53E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7DE85F12B8 FOREIGN KEY (post_id_id) REFERENCES feed_posts (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7D9D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participation_events ADD CONSTRAINT FK_29E2D16F6ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participation_events ADD CONSTRAINT FK_29E2D16F9D6A1065 FOREIGN KEY (events_id) REFERENCES events (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participation_users ADD CONSTRAINT FK_1B5F28626ACE3B73 FOREIGN KEY (participation_id) REFERENCES participation (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participation_users ADD CONSTRAINT FK_1B5F286267B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F9D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F3E5F2F7B FOREIGN KEY (event_id_id) REFERENCES events (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE roulette ADD CONSTRAINT FK_D80F9D2E9D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shares ADD CONSTRAINT FK_905F717CE85F12B8 FOREIGN KEY (post_id_id) REFERENCES feed_posts (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shares ADD CONSTRAINT FK_905F717C9D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_groups ADD CONSTRAINT FK_953F224DF05788E9 FOREIGN KEY (creator_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_intrests ADD CONSTRAINT FK_5A3306759D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_intrests ADD CONSTRAINT FK_5A3306759777D11E FOREIGN KEY (category_id_id) REFERENCES category (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_messages ADD CONSTRAINT FK_3B8FFA969D86650F FOREIGN KEY (user_id_id) REFERENCES users (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F09D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F0A578C4F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F07E182327
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962AE85F12B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962A9D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE conversion DROP FOREIGN KEY FK_BD9127449D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events DROP FOREIGN KEY FK_5387574AE78C696A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events DROP FOREIGN KEY FK_5387574A9777D11E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feed_posts DROP FOREIGN KEY FK_7DD2E9469D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feed_posts DROP FOREIGN KEY FK_7DD2E9463E5F2F7B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE group_feed_posts DROP FOREIGN KEY FK_AA1F88F42F68B530
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE group_feed_posts DROP FOREIGN KEY FK_AA1F88F49D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE group_members DROP FOREIGN KEY FK_C3A086F37F71E2AF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE group_members DROP FOREIGN KEY FK_C3A086F39D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE historique_points DROP FOREIGN KEY FK_EEF82E759D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE jobs DROP FOREIGN KEY FK_A8936DC59D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE jobs DROP FOREIGN KEY FK_A8936DC53E5F2F7B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE likes DROP FOREIGN KEY FK_49CA4E7DE85F12B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE likes DROP FOREIGN KEY FK_49CA4E7D9D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participation_events DROP FOREIGN KEY FK_29E2D16F6ACE3B73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participation_events DROP FOREIGN KEY FK_29E2D16F9D6A1065
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participation_users DROP FOREIGN KEY FK_1B5F28626ACE3B73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE participation_users DROP FOREIGN KEY FK_1B5F286267B3B43D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F9D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F3E5F2F7B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE roulette DROP FOREIGN KEY FK_D80F9D2E9D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shares DROP FOREIGN KEY FK_905F717CE85F12B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shares DROP FOREIGN KEY FK_905F717C9D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_groups DROP FOREIGN KEY FK_953F224DF05788E9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_intrests DROP FOREIGN KEY FK_5A3306759D86650F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_intrests DROP FOREIGN KEY FK_5A3306759777D11E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_messages DROP FOREIGN KEY FK_3B8FFA969D86650F
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE applications
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE comments
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE conversion
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE events
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE feed_posts
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE group_feed_posts
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE group_members
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE historique_points
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE jobs
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE likes
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messages
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE participation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE participation_events
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE participation_users
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reviews
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rewards
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE roulette
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE shares
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE transaction_argent
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_groups
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_intrests
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_messages
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_profile
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_rewards
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE users
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE visite_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE worker_raitings
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
