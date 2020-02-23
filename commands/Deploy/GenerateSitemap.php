<?php

namespace Palladiumlab\Commands\Deploy;


use Palladiumlab\Helpers\Bitrix\Sitemap\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSitemap extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deploy:generate-sitemap')
            ->setAliases(['sitemap'])
            ->setDescription('Автогенерация карты сайта по фильтрам');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $iblockIdList = [ // Идентификаторы инфоблоков по категориям которых строится sitemap
            0,
        ];

        $excludedSections = [ // Символьные коды категорий, по которым НЕ нужен sitemap
            'accessories',
        ];

        $sefFolders = [ // Папки сайтов для sitemap ['catalog', 'blog']
            '',
        ];

        define('URL_PROPERTIES_COUNT', 3); // Вложенность (количество) свойств в url

        (new Generator($iblockIdList, $sefFolders, true, false))
            ->setExcludedSections($excludedSections)
            ->setFilename('sitemap-filters.xml')
            ->setUrlTemplate('/#SECTION_CODE#/filter/#SMART_FILTER_PATH#/apply/')
            ->run();

        $output->writeln("<info>Sitemap was generated!</info>");
    }
}
