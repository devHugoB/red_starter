<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reaction', name: 'reaction_')]
class ReactionController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('reaction/index.html.twig', [
            'controller_name' => 'ReactionController',
        ]);
    }

    #[Route('/save', name: 'save')]
    public function Save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $time = (float)$request->get("time");

        if ($user->getBestScore() == null || $time < $user->getBestScore()) {
            $user->setBestScore($time);
            $em->flush();
        } else {
            $time = $user->getBestScore();
        }

        return $this->json(["time" => $time]);
    }
}
