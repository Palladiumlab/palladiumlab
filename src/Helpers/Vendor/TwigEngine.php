<?php


namespace Palladiumlab\Core;


use Palladiumlab\Templates\Singleton;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigEngine extends Singleton
{
    /** @var Environment */
    protected $engine;

    protected function __construct()
    {
        parent::__construct();
        $templateRoot = $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH;
        $loader = new FilesystemLoader($templateRoot . '/templates/twig');
        $this->engine = new Environment($loader, [
            'cache' => $templateRoot . '/cache/twig',
        ]);
    }

    public static function render(string $template, array $vars = [])
    {
        try {
            return self::getInstance()->getEngine()->render("{$template}.twig", $vars);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return TwigEngine
     */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }
}