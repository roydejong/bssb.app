<?php

namespace app\Frontend;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ViewRenderer
{
    // -----------------------------------------------------------------------------------------------------------------
    // Setup

    protected Environment $twig;

    private function __construct()
    {
        global $config;

        $this->twig = new Environment(new FilesystemLoader(DIR_VIEWS), [
            'cache' => $config['cache_enabled'] ? DIR_CACHE : false
        ]);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Render

    public function render(string $viewFileName, ?array $context): string
    {
        return $this->twig->render($viewFileName, $context ?? []);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Singleton

    private static ViewRenderer $viewRenderer;

    public static function instance(): ViewRenderer
    {
        if (!isset(self::$viewRenderer)) {
            self::$viewRenderer = new ViewRenderer();
        }

        return self::$viewRenderer;
    }
}