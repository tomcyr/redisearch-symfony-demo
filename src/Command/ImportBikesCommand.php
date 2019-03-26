<?php

namespace App\Command;

use Ehann\RediSearch\Fields\GeoLocation;
use Ehann\RediSearch\Index;
use Ehann\RedisRaw\PredisAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;

class ImportBikesCommand extends Command
{
    protected static $defaultName = 'app:import-bikes';

    private static $path = __DIR__ . '/../../data/nextbike';

    private static $indexName = 'bikes';

    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import bikes from file')
            ->addOption('drop', 'd', InputOption::VALUE_OPTIONAL, 'Drop existing index');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $redis = (new PredisAdapter())->connect($this->params->get('redisHost'), $this->params->get('redisPort'));
        $bikeIndex = new Index($redis, self::$indexName);
        if ($input->getOption('drop')) {
            $bikeIndex->drop();
        }
        $bikeIndex
            ->addTextField('country')
            ->addTextField('city')
            ->addTextField('name')
            ->addNumericField('bikeCount')
            ->addNumericField('number')
            ->addGeoField('place')
            ->create();


        $finder = new Finder();
        $finder->files()->in(self::$path);

        $documents = [];
        foreach ($finder as $file) {
            $io->note(sprintf('Loading file %s', $file->getRelativePathname()));
            $bikes = new \SimpleXMLElement($file->getContents());
            foreach ($bikes->country as $country) {
                foreach ($country->city as $city) {
                    $countryName = (string)$country->attributes()->country_name;
                    $cityName = (string)$city->attributes()->name;
                    foreach ($city->place as $place) {
                        $attributes = $place->attributes();
                        $lat = (string)$attributes->lat;
                        $lng = (string)$attributes->lng;
                        $placeName = (string)$attributes->name;
                        $placeUid = (string)$attributes->uid;
                        $placeNumber = (int)$attributes->number;
                        $bikes = (int)$attributes->bikes;

                        $document = $bikeIndex->makeDocument($placeUid);
                        $document->country->setValue($countryName);
                        $document->city->setValue($cityName);
                        $document->name->setValue($placeName);
                        $document->number->setValue($placeNumber);
                        $document->bikeCount->setValue($bikes);
                        $document->place->setValue(new GeoLocation($lng, $lat));

                        $documents[] = $document;
                    }
                }
            }
        }

        $bikeIndex->addMany($documents);

        $io->success('Import finished.');
    }
}
