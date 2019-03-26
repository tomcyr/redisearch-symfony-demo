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

class BikeController extends AbstractController
{
    /**
     * @Route("/bike", name="bike")
     */
    public function index(Request $request, ParameterBagInterface $params, LoggerInterface $logger): Response
    {
        $result = [];
        if ($request->getMethod() === 'POST') {
            $search = $request->request->get('search');
            $redis = (new PredisAdapter())->connect($params->get('redisHost'), $params->get('redisPort'));
            $redis->setLogger($logger);
            $bikesIndex = new Index($redis, 'bikes');
            $result = $bikesIndex
                ->limit(0, 100)
                ->geoFilter('place', 20.9698153, 52.225168, 1000, 'm')
                ->search($search);
        }

        dump($result);

        return $this->render('bike/index.html.twig', [
            'result' => $result,
        ]);
    }
}
