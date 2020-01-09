<?php namespace Palladiumlab\Commands\Deploy;

use Bitrix\Catalog\GroupTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery\Services\Table;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpConstantsCommand extends Command
{
    const CONST_PATH = ROOT_DIR . '/local/const.php';

    private $stream = null;

    public function __construct(string $name = null)
    {
        Loader::includeModule('iblock');
        Loader::includeModule('highloadblock');
        Loader::includeModule('form');
        Loader::includeModule('sale');
        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('deploy:dumpconstants')
            ->setAliases(['constants'])
            ->setDescription('Автогенерация констант');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->start();

        $this->writeGlobal();
        $this->generateIblocks();
        $this->generateHLblocks();
        $this->generateWebForms();
        $this->generatePrices();
        $this->generateServices();
        $this->generatePaySystems();

        $this->end();
        $output->writeln("<info>All constants are generated!</info>");
    }

    private function start()
    {
        file_put_contents(self::CONST_PATH, '<?php' . PHP_EOL);
        $this->stream = fopen(self::CONST_PATH, 'a');

        fwrite($this->stream, '/** Данный файл генерируется коммандой от корня сайта: "php local/console constants" */' . PHP_EOL . PHP_EOL);
    }

    private function writeGlobal()
    {
        //todo default constants
        /*$this->writeDescription("Общие константы");
        $constants = [
            'log_path' => ROOT_DIR . 'logs/',
        ];
        foreach ($constants as $constantName => $constantValue) {
            $this->writeConstant(
                mb_strtoupper($constantName),
                $constantValue
            );
        }
        $this->writeEol();*/
    }

    private function generateIblocks()
    {
        $this->writeDescription("Константы инфоблоков");
        $iblocksList = IblockTable::getList([
            'order' => ['ID' => 'ASC']
        ]);
        while ($iblock = $iblocksList->fetch()) {
            if (!empty($iblock['CODE'])) {
                $this->writeConstant(
                    'IBLOCK_' . mb_strtoupper($iblock['CODE']) . '_ID',
                    (int)$iblock['ID']
                );
            }
        }
        $this->writeEol();
    }

    private function writeDescription($text)
    {
        $result = '/**' . PHP_EOL;
        foreach (explode(PHP_EOL, $text) as $textItem) {
            $result .= '* ' . $textItem . PHP_EOL;
        }
        $result .= '*/' . PHP_EOL;
        fwrite($this->stream, $result);
    }

    private function writeConstant($name, $value)
    {
        $name = trim(preg_replace('/\W+/', '_', $name), '_');
        if (is_string($value)) {
            $value = "'{$value}'";
        }
        if (!empty($name)) {
            fwrite($this->stream, "define('{$name}', {$value});" . PHP_EOL);
        }
    }

    private function writeEol()
    {
        fwrite($this->stream, PHP_EOL . PHP_EOL);
    }

    private function generateHLblocks()
    {
        $hlblocksList = HighloadBlockTable::getList([
            'order' => ['ID' => 'ASC']
        ]);
        if ($hlblocksList->getSelectedRowsCount() > 0) {
            $this->writeDescription("Константы HL блоков");
        }
        while ($hlblock = $hlblocksList->fetch()) {
            $this->writeConstant(
                'HLBLOCK_' . mb_strtoupper($hlblock['NAME']) . '_ID',
                (int)$hlblock['ID']
            );
        }
        $this->writeEol();
    }

    private function generateWebForms()
    {
        $this->writeDescription("Константы веб-форм");
        $webFormList = \CForm::GetList($by = 's_id', $order = 'asc', [], $isFiltered);
        while ($webForm = $webFormList->fetch()) {
            $this->writeConstant(
                'WEBFORM_' . mb_strtoupper($webForm['SID']) . '_ID',
                (int)$webForm['ID']
            );
            $this->writeConstant(
                'WEBFORM_' . mb_strtoupper($webForm['SID']) . '_SID',
                $webForm['SID']
            );
        }
        $this->writeEol();
    }

    private function generatePrices()
    {
        $this->writeDescription("Константы типов цен");
        $groupList = GroupTable::getList([
            'order' => ['ID' => 'ASC']
        ]);
        while ($priceGroup = $groupList->fetch()) {
            $this->writeConstant(
                'PRICE_' . mb_strtoupper($priceGroup['NAME']) . '_ID',
                (int)$priceGroup['ID']
            );
            $this->writeConstant(
                'PRICE_' . mb_strtoupper($priceGroup['NAME']) . '_CODE',
                $priceGroup['NAME']
            );
        }
        $this->writeEol();
    }

    private function generateServices()
    {
        $this->writeDescription("Константы доставок");
        $elementList = Table::getList([
            'filter' => ['!XML_ID' => null],
            'order' => ['ID' => 'ASC']
        ]);
        while ($element = $elementList->fetch()) {
            if (empty($element['XML_ID'])) {
                continue;
            }
            $this->writeConstant(
                'DELIVERY_SERVICE_' . mb_strtoupper($element['XML_ID']) . '_ID',
                (int)$element['ID']
            );
        }
        $this->writeEol();
    }

    private function generatePaySystems()
    {
        $this->writeDescription("Константы платёжных систем");
        $elementList = PaySystemActionTable::getList([
            'filter' => ['!XML_ID' => null],
            'order' => ['ID' => 'ASC']
        ]);
        while ($element = $elementList->fetch()) {
            if (empty($element['XML_ID']) || empty($element['PAY_SYSTEM_ID'])) {
                continue;
            }
            $this->writeConstant(
                'PAY_SYSTEM_' . mb_strtoupper($element['XML_ID']) . '_ID',
                (int)$element['PAY_SYSTEM_ID']
            );
        }
        $this->writeEol();
    }

    private function end()
    {
        fclose($this->stream);
    }
}
