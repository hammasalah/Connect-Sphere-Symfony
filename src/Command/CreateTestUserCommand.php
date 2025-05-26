<?php

namespace App\Command;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTestUserCommand extends Command
{
    protected static $defaultName = 'app:create-test-user';
    
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new Users();
        $user->setUsername('testuser');
        $user->setPassword(password_hash('testpassword', PASSWORD_BCRYPT));
        $user->setEmail('test@example.com');
        $user->setCreatedAt(date('Y-m-d H:i:s'));
        $user->setAge(25);
        $user->setGender('male');
        $user->setPoints(0);
        $user->setArgent(100.00);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Test user created with ID: '.$user->getId());
        
        return Command::SUCCESS;
    }
}