<?php

namespace app\Controllers;

use app\Common\CVersion;
use app\Frontend\View;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\RedirectResponse;

class GuideController
{
    private const LastUpdate = "2022-03-02 21:30";
    private const CurrentGameVersion = "1.19.1";
    private const AbsoluteMaxVersion = "1.18.3";
    private const PcSupportedVersion = "1.18.3";
    private const BmbfSupportedVersion = "1.17.1";

    private const PlatformSteamPC = "steam";
    private const PlatformOculusPC = "oculus-pc";
    private const PlatformQuest = "quest";
    private static $platformOptions = [
        self::PlatformSteamPC => "Steam (PC)",
        self::PlatformOculusPC => "Oculus (PC / Link)",
        self::PlatformQuest => "Oculus Quest"
    ];

    private static $allGameVersions = ["1.17.1", "1.18.0", "1.18.1",
        "1.18.2", "1.18.3", "1.19.0", "1.19.1"];

    public function getGuideIndex(Request $request): Response
    {
        $currentGameVersion = new CVersion(self::CurrentGameVersion);

        if ($request->method === "POST") {
            $platform = $request->postParams['platform'];
            $version = $request->postParams['version'];

            if (isset(self::$platformOptions[$platform]) &&
                (in_array($version, self::$allGameVersions) || $version === "other")) {
                return new RedirectResponse("/guide/{$platform}/{$version}");
            } else {
                return new Response(400);
            }
        }

        $view = new View('pages/guide.twig');
        $view->set('pageTitle', "Multiplayer Modding Guide");
        $view->set('pageDescr', "An interactive guide that will help you play custom songs in Beat Saber multiplayer.");
        $view->set('lastUpdate', self::LastUpdate);
        $view->set('currentGameVersion', $currentGameVersion);
        $view->set('allGameVersions', array_reverse(self::$allGameVersions));
        $view->set('platformOptions', self::$platformOptions);
        return $view->asResponse();
    }

    public function getGuideResult(Request $request, string $platform, string $version): Response
    {
        if (!isset(self::$platformOptions[$platform]))
            // Invalid platform selection, go back to guide
            return new RedirectResponse('/guide');

        if ($version === "latest")
            // Redirect to latest version
            return new RedirectResponse("/guide/{$platform}/" . self::CurrentGameVersion);

        if (!in_array($version, self::$allGameVersions) && $version !== "other")
            // Invalid request, go back to guide
            return new RedirectResponse('/guide');

        if ($version !== "other")
            $version = new CVersion($version);

        $platformName = self::$platformOptions[$platform];
        $versionText = $version;

        // -------------------------------------------------------------------------------------------------------------
        // Analysis

        $isPcVr = $platform === self::PlatformOculusPC || $platform === self::PlatformSteamPC;

        if ($isPcVr)
            $platformVersion = new CVersion(self::PcSupportedVersion);
        else
            $platformVersion = new CVersion(self::BmbfSupportedVersion);

        $absoluteMaxVersion = new CVersion(self::AbsoluteMaxVersion);

        $resultText = "";
        $resultInstruction = "";
        $resultBad = false;
        $resultEyes = null;
        $faqSets = [];

        if ($version === "other") {
            $versionText = "Other/Older Version";
            $resultText = "You've selected \"Other\", which means the version of Beat Saber you have is either too new or too old. Multiplayer mods are not supported for this version.";
            $resultInstruction = "If you want to use multiplayer mods, you should upgrade or downgrade your game for now. On {$platformName}, we recommend Beat Saber {$platformVersion}.";
            $resultBad = true;
            $resultEyes = "Eyes2";

            $faqSets[] = "mpDowngrade";
            $faqSets[] = "downgrade";
        } else if ($platform === self::PlatformQuest && $version->greaterThan($platformVersion)) {
            $resultText = "Sorry, you can't install mods for Beat Saber {$version} yet on Quest. You'll have to wait for BMBF and core mods to be updated first.";
            $resultInstruction = "If you want to use multiplayer mods, you should downgrade your game to Beat Saber {$platformVersion} for now.";
            $resultBad = true;
            $resultEyes = "Eyes10";

            $faqSets[] = "bmbfDowngrade";
            $faqSets[] = "downgrade";
        } else if ($version->greaterThan($absoluteMaxVersion)) {
            $resultText = "Sorry, that version is just too new! Multiplayer mods are not (yet) available for Beat Saber {$version} on any platform.";
            $resultInstruction = "If you want to use multiplayer mods, you should downgrade your game for now. On {$platformName}, we recommend Beat Saber {$platformVersion}.";
            $resultBad = true;
            $resultEyes = "Eyes10";

            $faqSets[] = "mpDowngrade";
            $faqSets[] = "downgrade";
        }

        // -------------------------------------------------------------------------------------------------------------
        // Render

        $view = new View('pages/guide-result.twig');
        $view->set('pageTitle', "{$platformName} {$version} - Multiplayer Modding Guide");
        $view->set('pageDescr', "How to use mods in Beat Saber {$version} multiplayer on {$platformName}.");
        $view->set('lastUpdate', self::LastUpdate);
        $view->set('platformKey', $platform);
        $view->set('platformName', $platformName);
        $view->set('version', $version);
        $view->set('versionText', $versionText);
        $view->set('platformVersion', $platformVersion);
        $view->set('eyes', $resultEyes);
        $view->set('resultText', $resultText);
        $view->set('resultInstruction', $resultInstruction);
        $view->set('resultBad', $resultBad);
        $view->set('noIndex', ($version === "other"));
        $view->set('faqSet', $faqSets);
        return $view->asResponse();
    }
}