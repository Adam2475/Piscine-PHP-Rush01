<?php
namespace App\DataFixtures;

use App\Entity\Project;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProjectFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $project = new Project();
        $project->setName('Transcendence');
        $project->setDescription('Build a multiplayer pong game');
        $project->setXp(1000);
        $project->setEstimatedTimeInHours(60);
        $manager->persist($project);

        $project2 = new Project();
        $project2->setName('Intranet');
        $project2->setDescription('Internal user and event management');
        $project2->setXp(500);
        $project2->setEstimatedTimeInHours(30);
        $manager->persist($project2);

        $project3 = new Project();
        $project3->setName('Minishell');
        $project3->setDescription('Recreate a simple shell.');
        $project3->setXp(750);
        $project3->setEstimatedTimeInHours(100);
        $manager->persist($project3);

        $project4 = new Project();
        $project4->setName('Webserv');
        $project4->setDescription('Create an HTTP server');
        $project4->setXp(900);
        $project4->setEstimatedTimeInHours(40);
        $manager->persist($project4);

        $project5 = new Project();
        $project5->setName('Cub3d');
        $project5->setDescription('Make a simple 3D game');
        $project5->setXp(600);
        $project5->setEstimatedTimeInHours(50);
        $manager->persist($project5);

        $manager->flush();
    }
}
