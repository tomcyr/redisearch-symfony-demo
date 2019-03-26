<?php

namespace App\Command;

use Ehann\RediSearch\Index;
use Ehann\RedisRaw\PredisAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;

class ImportRecipesCommand extends Command
{
    protected static $defaultName = 'app:import-recipes';

    private static $path = __DIR__ . '/../../data/recipes';

    private static $indexName = 'recipes';

    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import recipes from files')
            ->addOption('drop', 'd', InputOption::VALUE_OPTIONAL, 'Drop existing index');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $redis = (new PredisAdapter())->connect($this->params->get('redisHost'), $this->params->get('redisPort'));
        $recipeIndex = new Index($redis, self::$indexName);
        if ($input->getOption('drop')) {
            $recipeIndex->drop();
        }
        $recipeIndex
            ->addTextField('title', 1.0, true)
            ->addTextField('description', .5)
            ->addTextField('ingredients', .7)
            ->addTextField('directions', .3)
            ->addNumericField('prepTimeMin')
            ->addNumericField('cookTimeMin')
            ->addNumericField('servings')
            ->addTextField('tags')
            ->addTextField('author')
            ->addTextField('sourceUrl')
            ->addTextField('tags')
            ->create();

        $finder = new Finder();
        $finder->files()->in(self::$path);

        $documents = [];
        foreach ($finder as $file) {
            $io->note(sprintf('Loading file %s', $file->getRelativePathname()));
            $recipe = json_decode($file->getContents());

            $document = $recipeIndex->makeDocument();
            $document->title->setValue($recipe->title);
            $document->description->setValue($recipe->description);
            $document->ingredients->setValue(implode('<br/>', $recipe->ingredients));
            $document->directions->setValue(implode('<br/>', $recipe->directions));
            if (property_exists($recipe, 'prep_time_min')) {
                $document->prepTimeMin->setValue($recipe->prep_time_min);
            }
            if (property_exists($recipe, 'cook_time_min')) {
                $document->cookTimeMin->setValue($recipe->cook_time_min);
            }
            $document->servings->setValue($recipe->servings);
            $document->tags->setValue(implode('<br/>', $recipe->tags));
            $document->author->setValue($recipe->author->name);
            $document->sourceUrl->setValue($recipe->source_url);

            $documents[] = $document;
        }

        $recipeIndex->addMany($documents);

        $io->success('Import finished.');
    }
}
