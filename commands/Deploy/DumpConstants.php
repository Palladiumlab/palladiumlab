<?php namespace Palladiumlab\Commands\Deploy;

use Bitrix\Catalog\GroupTable as CatalogPriceTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery\Services\Table as DeliveryServiceTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TwigGenerator\Builder\BaseBuilder;
use TwigGenerator\Builder\Generator;

class DumpConstants extends Command
{
    protected $templatePath = ROOT_DIR . '/local/templates/twig/dump-constant/';
    protected $outputName = 'const.php';
    protected $outputDir = ROOT_DIR . '/local/php_interface';
    protected $templateName = 'DumpConstants.php.twig';

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('deploy:dump-constants')
            ->setAliases(['constants'])
            ->setDescription('Автогенерация констант');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->getBuilder();
	$builder->setTemplateName($this->templateName);
        $builder->setOutputName($this->outputName);
        foreach ($this->getVariables() as $variable => $method) {
            if ($variableResult = $this->$method()) {
                $builder->setVariable($variable, $variableResult);
            }
        }
        $generator = new Generator();
        $generator->setTemplateDirs([
            $this->templatePath
        ]);
        $generator->setMustOverwriteIfExists(true);
        $generator->addBuilder($builder);
        $generator->writeOnDisk($this->outputDir);
        $output->writeln("<info>All constants are generated!</info>");
    }

    protected function getBuilder()
    {
        return new class extends BaseBuilder {

        };
    }

    protected function getVariables()
    {
        return [
            'iblocks' => 'getIblocks',
            'hlblocks' => 'getHLblocks',
            'webForms' => 'getWebForms',
            'prices' => 'getPrices',
            'services' => 'getServices',
            'paySystems' => 'getPaySystems',
        ];
    }

    private function getIblocks()
    {
        try {
            $result = false;
            if (Loader::includeModule('iblock')) {
                $result = [];
                $iblocksList = IblockTable::getList([
                    'order' => ['ID' => 'ASC']
                ]);
                while ($iblock = $iblocksList->fetch()) {
                    if (!empty($iblock['CODE'])) {
                        $result[$iblock['IBLOCK_TYPE_ID']][] = [
                            'name' => $iblock['NAME'],
                            'site_id' => $iblock['LID'],
                            'code' => 'IBLOCK_' . strtoupper($iblock['CODE']) . '_ID',
                            'id' => $iblock['ID'],
                        ];
                    }
                }
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getHLblocks()
    {
        try {
            $result = false;
            if (Loader::includeModule('highloadblock')) {
                $result = [];
                $hlblocksList = HighloadBlockTable::getList([
                    'order' => ['ID' => 'ASC']
                ]);
                while ($hlblock = $hlblocksList->fetch()) {
                    $result[] = [
                        'name' => $hlblock['NAME'],
                        'code' => 'HLBLOCK_' . mb_strtoupper($hlblock['NAME']) . '_ID',
                        'id' => $hlblock['ID'],
                    ];
                }
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getWebForms()
    {
        try {
            $result = false;
            if (Loader::includeModule('form')) {
                $result = [];
                $webFormList = \CForm::GetList($by = 's_id', $order = 'asc', [], $isFiltered);
                while ($webForm = $webFormList->fetch()) {
                    $result[] = [
                        'name' => $webForm['SID'],
                        'code' => 'WEBFORM_' . mb_strtoupper($webForm['SID']) . '_ID',
                        'id' => $webForm['ID'],
                    ];
                    $result[] = [
                        'name' => $webForm['SID'],
                        'code' => 'WEBFORM_' . mb_strtoupper($webForm['SID']) . '_SID',
                        'id' => '"' . $webForm['SID'] . '"',
                    ];
                }
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getPrices()
    {
        try {
            $result = false;
            if (Loader::includeModule('sale')) {
                $result = [];
                $priceList = CatalogPriceTable::getList([
                    'order' => ['ID' => 'ASC']
                ]);
                while ($price = $priceList->fetch()) {
                    $result[] = [
                        'name' => $price['NAME'],
                        'code' => 'PRICE_' . mb_strtoupper($price['NAME']) . '_ID',
                        'id' => $price['ID'],
                    ];
                    $result[] = [
                        'name' => $price['NAME'],
                        'code' => 'PRICE_' . mb_strtoupper($price['NAME']) . '_CODE',
                        'id' => '"' . $price['NAME'] . '"',
                    ];
                }
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getServices()
    {
        try {
            $result = false;
            if (Loader::includeModule('sale')) {
                $result = [];
                $elementList = DeliveryServiceTable::getList([
                    'filter' => ['!XML_ID' => null],
                    'order' => ['ID' => 'ASC']
                ]);
                while ($element = $elementList->fetch()) {
                    if (!empty($element['XML_ID'])) {
                        $result[] = [
                            'name' => $element['XML_ID'],
                            'code' => 'DELIVERY_SERVICE_' . mb_strtoupper($element['XML_ID']) . '_ID',
                            'id' => $element['ID'],
                        ];
                    }
                }
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getPaySystems()
    {
        try {
            $result = false;
            if (Loader::includeModule('sale')) {
                $result = [];
                $elementList = PaySystemActionTable::getList([
                    'filter' => ['!XML_ID' => null],
                    'order' => ['ID' => 'ASC']
                ]);
                while ($element = $elementList->fetch()) {
                    if (!empty($element['XML_ID']) && !empty($element['PAY_SYSTEM_ID'])) {
                        $result[] = [
                            'name' => $element['XML_ID'],
                            'code' => 'PAY_SYSTEM_' . mb_strtoupper($element['XML_ID']) . '_ID',
                            'id' => $element['PAY_SYSTEM_ID'],
                        ];
                    }
                }
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
}
