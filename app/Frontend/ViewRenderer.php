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

    private function processContext(?array $userContext): array
    {
        if ($userContext === null) {
            $userContext = [];
        }

        // Add git version (for cache busting)
        $gitRefPath = DIR_BASE . '/.git/refs/heads/main';
        $userContext['version_hash'] = trim(file_get_contents($gitRefPath));
        $userContext['version_hash_short'] = substr($userContext['version_hash'],0,7);
        $userContext['version_date'] = filemtime($gitRefPath);

        return $userContext;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Render

    public function render(string $viewFileName, ?array $context): string
    {
        return $this->twig->render($viewFileName, $this->processContext($context));
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