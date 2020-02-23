<?php


namespace Palladiumlab\Vendor;

use Illuminate\View\Factory;
use Palladiumlab\Templates\Singleton;
use Philo\Blade\Blade;

class BladeEngine extends Singleton
{
    /** @var Blade|null $blade */
    protected $blade = null;
    /** @var Factory|null $viewFactory */
    protected $viewFactory = null;

    protected function __construct()
    {
        parent::__construct();
        list($templatesPath, $cachePath) = [
            ROOT_DIR . template_path() . '/templates/blade/',
            ROOT_DIR . template_path() . '/cache/blade/'
        ];

        $this->checkDirectory($templatesPath);
        $this->checkDirectory($cachePath);

        $this->blade = new Blade(
            $templatesPath,
            $cachePath
        );
        $this->viewFactory = $this->blade->view();
    }

    protected function checkDirectory($path)
    {
        if (!is_dir($path)) {
            mkdir($path);
        }
    }

    public static function render(string $template, array $vars = [])
    {
        return self::getInstance()->getView()->make($template, $vars)->render();
    }

    public function getView()
    {
        return $this->viewFactory;
    }

    /**
     * @return BladeEngine
     */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }
}
