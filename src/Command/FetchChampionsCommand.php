<?php

namespace App\Command;

use App\Entity\Champion;
use App\Repository\ChampionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:fetch-champions',
    description: 'Récupère les champions depuis l\'API Riot Games et les enregistre en base de données',
)]
class FetchChampionsCommand extends Command
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private ChampionRepository $championRepository;

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        ChampionRepository $championRepository
    ) {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->championRepository = $championRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->title('Récupération des champions depuis l\'API Riot Games');

            $response = $this->httpClient->request(
                'GET',
                'https://ddragon.leagueoflegends.com/cdn/16.2.1/data/fr_FR/champion.json'
            );

            $data = $response->toArray();
            $championsData = $data['data'] ?? [];

            $io->text(sprintf(' %d champions trouvés', count($championsData)));
            $io->newLine();

            // Barre de progression
            $io->progressStart(count($championsData));

            $countNew = 0;
            $countUpdated = 0;

            foreach ($championsData as $key => $championData) {
                // Chercher si le champion existe déjà par son nom
                $champion = $this->championRepository->findOneBy(['name' => $championData['name']]);

                $isNew = false;
                if (!$champion) {
                    $champion = new Champion();
                    $isNew = true;
                    $countNew++;
                } else {
                    $countUpdated++;
                }

                // Remplir les données du champion
                $champion->setName($championData['name']);
                $champion->setTitle($championData['title']);
                $champion->setBlurb($championData['blurb'] ?? null);

                // Persister l'entité
                $this->entityManager->persist($champion);

                $io->progressAdvance();
            }

            // Sauvegarder en base de données
            $io->newLine(2);
            $io->text('Enregistrement en base de données...');
            $this->entityManager->flush();

            $io->progressFinish();

            // Résumé
            $io->newLine();
            $io->success(['Import terminé avec succès !']);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Erreur lors de la récupération des champions',
                'Message : ' . $e->getMessage()
            ]);

            return Command::FAILURE;
        }
    }
}