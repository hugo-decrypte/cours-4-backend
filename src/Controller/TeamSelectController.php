<?php

namespace App\Controller;

use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeamSelectController extends AbstractController
{
    #[Route('/teams', name: 'app_teams')]
    public function index(TeamRepository $teamRepository): Response
    {
        // Récupérer 2 teams (vous pouvez ajuster la logique selon vos besoins)
        $teams = $teamRepository->findBy([], null, 2);

        return $this->render('index.html.twig', [
            'teams' => $teams,
        ]);
    }
}