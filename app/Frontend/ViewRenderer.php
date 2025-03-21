<?php

namespace app\Frontend;

use app\HTTP\QueryParamTransform;
use app\Session\Session;
use app\Utils\TimeAgo;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

class ViewRenderer
{
    // -----------------------------------------------------------------------------------------------------------------
    // Setup

    protected Environment $twig;
    protected array $globals;

    private function __construct()
    {
        global $bssbConfig;

        $this->twig = new Environment(new FilesystemLoader(DIR_VIEWS), [
            'cache' => $bssbConfig && $bssbConfig['cache_enabled'] ? DIR_CACHE : false
        ]);

        $this->twig->addFilter(new TwigFilter('timeago', function ($input): string {
            try {
                if ($input instanceof \DateTime) {
                    $dt = $input;
                } else {
                    $dt = new \DateTime($input);
                }
                return TimeAgo::format($dt);
            } catch (\Exception) {
                return "unknown";
            }
        }));

        $this->twig->addFilter(new TwigFilter('timeago_html', function ($input): string {
            try {
                if ($input instanceof \DateTime) {
                    $dt = $input;
                    $inputText = $dt->format('r');
                } else {
                    $dt = new \DateTime($input);
                    $inputText = $input;
                }
                $timeAgo = TimeAgo::format($dt);
                return "<abbr title='{$inputText}'>{$timeAgo}</abbr>";
            } catch (\Exception) {
                return "unknown";
            }
        }));

        $this->twig->addFilter(new TwigFilter('with_query_param', function ($input, $key, $value): string {
            return QueryParamTransform::fromUrl($input)
                ->set($key, $value)
                ->toUrl();
        }));

        $this->globals = [];
    }

    public function setGlobal(string $key, mixed $value): void
    {
        $this->globals[$key] = $value;
    }

    private function processContext(?array $userContext): array
    {
        $combinedContext = [];

        // Write globals
        foreach ($this->globals as $key => $value)
            $combinedContext[$key] = $value;

        // Add version info (for cache busting)
        $versionFilePath = DIR_BASE . "/.version";
        $combinedContext['version_hash'] = trim(@file_get_contents($versionFilePath));
        $combinedContext['version_hash_short'] = substr($combinedContext['version_hash'],0,7);
        $combinedContext['version_date'] = @filemtime($versionFilePath);

        // Add session info
        $session = Session::getInstance();

        if ($session->getIsSteamAuthed()) {
            $player = $session->getPlayer();

            $combinedContext['steam_authed'] = true;
            $combinedContext['self_player_id'] = $player?->id ?? null;
            $combinedContext['self_player_name'] = $player?->userName ?? "Steam User";
            $combinedContext['self_face_render'] = $player?->renderFaceHtml();
            $combinedContext['self_is_admin'] = $player?->getIsSiteAdmin();
            $combinedContext['self_profile_url'] = $player?->getWebDetailUrl();
        }

        // Config data
        global $bssbConfig;
        $combinedContext['config'] = [
            'enable_guide' => !!($bssbConfig['enable_guide'] ?? false)
        ];

        // Write user context last
        if ($userContext)
            foreach ($userContext as $key => $value)
                $combinedContext[$key] = $value;

        return $combinedContext;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Render

    public function render(string $viewFileName, ?array $context, bool $processContext = true): string
    {
        $contextArg = $processContext ? $this->processContext($context) : $context;
        return $this->twig->render($viewFileName, $contextArg);
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