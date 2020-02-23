<?php


namespace Palladiumlab\Helpers\Bitrix\Sitemap;


use Bitrix\Main\Web\Uri;

class Generator
{
    private $iblockIdList;
    private $excludedSections;
    private $sefFolders;
    private $dumpUrl;
    private $includeSubsections;

    private $urlTemplate = '/#SECTION_CODE#/filter/#SMART_FILTER_PATH#/apply/';
    private $fileName = 'sitemap-filters.xml';
    /** @var false|resource */
    private $fileResource;
    /** @var int */
    private $counter;

    public function __construct(array $iblockIdList, array $sefFolders, bool $includeSubsections = false, bool $dumpUrl = false)
    {
        if (empty($sefFolders)) {
            $sefFolders = [''];
        }
        $this->iblockIdList = $iblockIdList;
        $this->excludedSections = [];
        $this->sefFolders = array_map(function ($sefFolder) {
            return empty($sefFolder) ? $sefFolder : "/{$sefFolder}";
        }, $sefFolders);
        $this->dumpUrl = $dumpUrl;
        $this->includeSubsections = $includeSubsections;
        $this->counter = 0;

        $this->deleteOldFile();
        $this->fileResource = fopen(__DIR__ . "/{$this->fileName}", 'w+');
    }

    public function deleteOldFile()
    {
        unlink(__DIR__ . "/{$this->fileName}");
        return $this;
    }

    public function setUrlTemplate(string $urlTemplate)
    {
        $this->urlTemplate = $urlTemplate;
        return $this;
    }

    public function setFilename(string $fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function setExcludedSections(array $excludedSections)
    {
        $this->excludedSections = $excludedSections;
        return $this;
    }

    public function run(bool $dumpCounter = false)
    {
        $this->counter = 0;

        fwrite($this->fileResource, '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        foreach ($this->iblockIdList as $iblockId) {
            $rootSections = $this->getSections($iblockId);
            foreach ($rootSections as $section) {
                $this->writeSection($iblockId, $section, [$section['CODE']]);
            }
        }
        fwrite($this->fileResource, '</urlset>');
        fclose($this->fileResource);

        if ($dumpCounter) {
            dump($this->counter);
        }
    }

    private function getSections(int $iblockId, int $parentSectionId = 0)
    {
        $filter = [
            '=IBLOCK_ID' => $iblockId,
            '=ACTIVE' => 'Y',
            '!CODE' => $this->excludedSections,
            '=IBLOCK_SECTION_ID' => false,
        ];
        if ($parentSectionId > 0) {
            $filter['=IBLOCK_SECTION_ID'] = $parentSectionId;
        }

        return SectionTable::getList([
            'filter' => $filter,
            'select' => ['ID', 'CODE'],
        ])->fetchAll() ?: [];
    }

    private function writeSection(int $iblockId, array $rootSection, array $sectionCodePath)
    {
        $filterSitemap = new FilterSitemap($iblockId, (int)$rootSection['ID'], 999);
        $smartResult = $filterSitemap->execute();
        if ($smartResult) {
            $this->writeSmartPart($smartResult, implode('/', $sectionCodePath), $filterSitemap);
        }
        unset($filterSitemap, $smartResult);
        if ($this->includeSubsections) {
            foreach ($this->getSections($iblockId, (int)$rootSection['ID']) as $section) {
                $this->writeSection($iblockId, $section, array_merge($sectionCodePath, [$section['CODE']]));
            }
        }
    }

    private function writeSmartPart(array $smartResult, string $sectionCode, FilterSitemap $filterSitemap)
    {
        if (!empty($smartResult)) {
            $dateUpdate = date('c');
            $host = $this->getHostUrl();
            $localTemplate = str_replace('#SECTION_CODE#', $sectionCode, $this->urlTemplate);
            $smartPartsList = $filterSitemap->makeCombinations($smartResult);
            foreach ($smartPartsList as $smartParts) {
                foreach ($this->sefFolders as $sefFolder) {
                    ++$this->counter;
                    $smartUrl = $filterSitemap->makeSmartUrl($localTemplate, $smartParts);
                    $url = "$host{$sefFolder}{$smartUrl}";
                    if ($this->dumpUrl) {
                        echo($url . '<br>');
                    }
                    fwrite($this->fileResource, "<url><loc>{$url}</loc><lastmod>{$dateUpdate}</lastmod></url>");
                }
            }
            unset($smartPartsList);
        }
    }

    public function getHostUrl()
    {
        $request = bitrix_request();
        $protocol = ($request->isHttps() ? 'https' : 'http');
        if (defined("SITE_SERVER_NAME") && SITE_SERVER_NAME) {
            $host = SITE_SERVER_NAME;
        } else {
            $host = (Option::get('main', 'server_name', $request->getHttpHost()) ?: $request->getHttpHost());
        }
        $parsedUri = new Uri($protocol . '://' . $host);
        return rtrim($parsedUri->getLocator(), "/");
    }
}
