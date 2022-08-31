<?php

namespace app\Controllers\API\V1;

use app\BeatSaber\Enums\PlayerLevelEndReason;
use app\BeatSaber\Enums\PlayerLevelEndState;
use app\BeatSaber\Enums\PlayerScoreRank;
use app\HTTP\Request;
use app\HTTP\Response;
use app\HTTP\Responses\BadRequestResponse;
use app\HTTP\Responses\NotFoundResponse;
use app\Models\LevelHistory;
use app\Models\LevelHistoryPlayer;
use app\Models\Player;

class AnnounceResultsController
{
    public function announceResults(Request $request): Response
    {
        global $bssbConfig;

        // -------------------------------------------------------------------------------------------------------------
        // Input

        $isClientRequest = $request->getIsValidModClientRequest();
        $isDediRequest = $request->getIsValidBeatDediRequest();

        if ((!$isClientRequest && !$isDediRequest)
            || !$request->getIsJsonRequest()
            || $request->method !== "POST") {
            return new BadRequestResponse();
        }

        $modClientInfo = $request->getModClientInfo();
        $input = $request->getJson();

        $sessionGameId = $input['SessionGameId'] ?? null;
        $results = $input['Results'] ?? null;

        if (empty($sessionGameId)) {
            return new BadRequestResponse();
        }

        // -------------------------------------------------------------------------------------------------------------
        // Lookup

        /**
         * @var $levelHistory LevelHistory|null
         */
        $levelHistory = LevelHistory::query()
            ->where('session_game_id = ?', $sessionGameId)
            ->querySingleModel();

        if (empty($levelHistory)) {
            return new NotFoundResponse();
        }

        if ($levelHistory->endedAt && !($bssbConfig['allow_multiple_results'] ?? false)) {
            return new Response(200, "lmao ignored 👎");
        }

        // -------------------------------------------------------------------------------------------------------------
        // Process

        $levelHistory->endedAt = new \DateTime('now');
        $levelHistory->save();

        /**
         * @var $historyPlayers LevelHistoryPlayer[]
         */
        $historyPlayers = LevelHistoryPlayer::query()
            ->where('level_history_id = ?', $levelHistory->id)
            ->queryAllModelsIndexed('playerId');

        if (!empty($historyPlayers) && $results && is_array($results)) {
            $playerIds = array_keys($historyPlayers);

            /**
             * @var $playerProfiles Player[]
             */
            $playerProfiles = Player::query()
                ->where('id IN (?)', $playerIds)
                ->queryAllModelsIndexed('id');

            $userIdToPlayerId = [];

            foreach ($playerProfiles as $playerProfile) {
                $userIdToPlayerId[$playerProfile->userId] = $playerProfile->id;
            }

            $rankedHistoryPlayers = [];

            // Fill history players from the submitted results
            foreach ($results as $resultItem) {
                $userId = $resultItem['UserId'] ?? null;

                if (empty($userId) || !isset($userIdToPlayerId[$userId]))
                    continue;

                $playerId = $userIdToPlayerId[$userId];
                $playerProfile = $playerProfiles[$playerId];
                $historyPlayer = $historyPlayers[$playerId];

                $historyPlayer->endReason = isset($resultItem['LevelEndReason']) ? PlayerLevelEndReason::tryFrom(intval($resultItem['LevelEndReason'])) : null;
                $historyPlayer->endState = isset($resultItem['LevelEndState']) ? PlayerLevelEndState::tryFrom(intval($resultItem['LevelEndState'])) : null;
                $historyPlayer->multipliedScore = intval($resultItem['MultipliedScore'] ?? 0);
                $historyPlayer->modifiedScore = intval($resultItem['ModifiedScore'] ?? 0);
                $historyPlayer->scoreRank = isset($resultItem['Rank']) ? PlayerScoreRank::tryFrom(intval($resultItem['Rank'])) : null;
                $historyPlayer->goodCuts = intval($resultItem['GoodCuts'] ?? 0);
                $historyPlayer->badCuts = intval($resultItem['BadCuts'] ?? 0);
                $historyPlayer->missCount = intval($resultItem['MissCount'] ?? 0);
                $historyPlayer->fullCombo = intval($resultItem['FullCombo'] ?? 0) === 1;
                $historyPlayer->maxCombo = intval($resultItem['MaxCombo'] ?? 0);

                if ($historyPlayer->maxCombo <= 0 || $historyPlayer->endState != PlayerLevelEndState::SongFinished)
                    // Full combo flag gets set incorrectly sometimes it seems, guard against this
                    $historyPlayer->fullCombo = false;

                $badgeData = $resultItem['Badge'] ?? null;
                if ($badgeData && is_array($badgeData)) {
                    $historyPlayer->badgeKey = $badgeData['Key'] ?? null;
                    $historyPlayer->badgeTitle = $badgeData['Title'] ?? null;
                    $historyPlayer->badgeSubtitle = $badgeData['Subtitle'] ?? null;
                }

                $rankedHistoryPlayers[] = $historyPlayer;
            }

            // Determine player placement, and calculate final player counts
            $playedPlayerCount = 0;
            $finishedPlayerCount = 0;

            usort($rankedHistoryPlayers, function (LevelHistoryPlayer $a, LevelHistoryPlayer $b): int {
                $aVal = $a->modifiedScore;
                $bVal = $b->modifiedScore;
                if ($aVal === $bVal) return 0;
                return $aVal > $bVal ? -1 : +1;
            });

            $placement = 1;
            foreach ($rankedHistoryPlayers as $player) {
                if ($player->endState !== PlayerLevelEndState::NotStarted) {
                    $playedPlayerCount++;
                    $player->placement = $placement;
                    $placement++;
                    if ($player->endState === PlayerLevelEndState::SongFinished) {
                        $finishedPlayerCount++;
                    }
                } else {
                    $player->placement = null; // did not start level, do not rank
                }
                $player->save();
            }

            $levelHistory->playedPlayerCount = $playedPlayerCount;
            $levelHistory->finishedPlayerCount = $finishedPlayerCount;
            $levelHistory->save();
        } else {
            $levelHistory->playedPlayerCount = 0;
            $levelHistory->finishedPlayerCount = 0;
            $levelHistory->save();
        }

        // Mark any players attached to the game but missing from these results as "did not play"
        LevelHistoryPlayer::query()
            ->update()
            ->set([
                'end_reason' => PlayerLevelEndReason::WasInactive->value,
                'end_state' => PlayerLevelEndState::NotStarted->value
            ])
            ->where('level_history_id = ?', $levelHistory->id)
            ->andWhere('end_reason IS NULL')
            ->andWhere('end_state IS NULL')
            ->execute();

        return new Response(200, "👍 very nice");
    }
}