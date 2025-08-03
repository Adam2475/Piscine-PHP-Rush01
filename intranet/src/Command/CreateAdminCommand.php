<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new administrator user',
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
        $this->em = $em;
        $this->hasher = $hasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $firstNameQ = new Question('First name: ');
        $lastNameQ = new Question('Last name: ');
        $emailQ = new Question('Email: ');
        $passwordQ = new Question('Password (will be hashed): ');

        $firstName = $helper->ask($input, $output, $firstNameQ);
        $lastName = $helper->ask($input, $output, $lastNameQ);
        $email = $helper->ask($input, $output, $emailQ);
        $password = $helper->ask($input, $output, $passwordQ);

        // Create user
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setCreated(new \DateTime());
        $user->setRole(UserRole::ADMIN);
        $user->setIsActive(true);

        $hashedPassword = $this->hasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $io->success('Administrator created successfully!');

        return Command::SUCCESS;
    }
}
