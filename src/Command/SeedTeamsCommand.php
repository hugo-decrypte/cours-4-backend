<?php

namespace App\Command;

use App\Entity\Team;
use App\Repository\ChampionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-teams',
    description: 'Crée 2 teams Faker et leur assigne des champions existants aléatoires'
)]
class SeedTeamsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ChampionRepository $championRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create('fr_FR');

        $io->title(' Création des teams et assignation des champions ');

        // Récupération des champions existants
        $champions = $this->championRepository->findAll();

        shuffle($champions);

        $championIndex = 0;

        for ($i = 1; $i <= 2; $i++) {
            $team = new Team();
            $team->setName('Team ' . strtoupper($faker->word()));
            $team->setCountry($faker->country());
            $team->setCreatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($team);

            $io->section('🏳️ ' . $team->getName());

            // 5 champions par team
            for ($j = 1; $j <= 5; $j++) {
                $champion = $champions[$championIndex];

                // côté propriétaire
                $champion->addTeam($team);

                $io->text(sprintf(
                    '➕ %s (%s)',
                    $champion->getName(),
                    $champion->getTitle()
                ));

                $championIndex++;
            }
        }

        $io->newLine();
        $this->entityManager->flush();

        $io->success('✅ 2 teams créées et champions assignés aléatoirement');

        return Command::SUCCESS;
    }
}
