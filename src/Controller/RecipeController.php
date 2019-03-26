<?php

namespace App\Controller;

use Ehann\RediSearch\Index;
use Ehann\RedisRaw\PredisAdapter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RecipeController extends AbstractController
{
    /**
     * @Route("/recipe", name="recipe")
     */
    public function index(Request $request, ParameterBagInterface $params, LoggerInterface $logger): Response
    {
        $result = [];
        if ($request->getMethod() === 'POST') {
            $search = $request->request->get('search');
            if ($search) {
                $redis = (new PredisAdapter())->connect($params->get('redisHost'), $params->get('redisPort'));
                $redis->setLogger($logger);
                $recipeIndex = new Index($redis, 'recipes');
                $result = $recipeIndex
//                    ->numericFilter('prepTimeMin', 25, 30)
                    ->sortBy('title')
                    ->highlight(['title', 'ingredients'])
                    ->search($search);
            }
        }

        return $this->render('recipe/index.html.twig', [
            'result' => $result,
        ]);
    }
}
