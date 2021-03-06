<?php

namespace app\Models;

use app\BeatSaber\LevelId;
use app\External\BeatSaver;
use SoftwarePunt\Instarecord\Model;

class LevelRecord extends Model
{
    public int $id;
    public string $levelId;
    public ?string $hash;
    public ?string $beatsaverId;
    public ?string $coverUrl;
    public string $name;
    public string $songName;
    public ?string $songAuthor;
    public ?string $levelAuthor;
    public ?int $duration;
    public ?string $description;
    public int $statPlayCount;

    // -----------------------------------------------------------------------------------------------------------------
    // Data helpers

    public function getIsCustomLevel(): bool
    {
        return LevelId::isCustomLevel($this->levelId);
    }

    public function describeSong(): string
    {
        $parts = [];
        if ($this->songAuthor)
            $parts[] = $this->songAuthor;
        if ($this->songName)
            $parts[] = $this->songName;
        return implode(' - ', $parts);
    }

    public function describeDuration(): string
    {
        if ($this->duration === null || $this->duration <= 0) {
            return "Unknown";
        }

        $mins = floor($this->duration / 60);
        $secs = $this->duration - ($mins * 60);

        $text = "";

        if ($mins > 0) {
            if ($mins === 1) {
                $text = "1 minute";
            } else {
                $text = "{$mins} minutes";
            }
        }

        if ($secs > 0) {
            if (!empty($text)) {
                $text .= ", ";
            }

            if ($secs === 1) {
                $text .= "1 second";
            } else {
                $text .= "{$secs} seconds";
            }
        }

        return $text;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // OST/DLC cover art helper

    public function syncCoverArt(): bool
    {
        if ($this->getIsCustomLevel()) {
            // Custom level assets - download to local if possible
            if (str_starts_with($this->coverUrl, "https://beatsaver.com/cdn/")) {
                if ($localCoverUrl = BeatSaver::downloadCoverArt($this->coverUrl)) {
                    $this->coverUrl = "https://bssb.app{$localCoverUrl}";
                    return $this->save();
                } else {
                    return false;
                }
            }
            return true;
        } else {
            // Base game assets - must be provided manually
            if (!$this->levelId || strpos($this->levelId, '.') !== false) {
                // Need valid level ID / as a precaution filter out dots to prevent any nasty traversal stuff
                \Sentry\captureMessage("syncNativeCover() rejected invalid level with id: {$this->levelId}");
                return false;
            }
            return $this->setNativeCover($this->levelId);
        }
    }

    private function setNativeCover(string $levelIdOrAssetName): bool
    {
        $expectedPath = "/static/bsassets/{$levelIdOrAssetName}.png";
        $expectedDiskPath = DIR_BASE . "/public" . $expectedPath;

        if (!file_exists($expectedDiskPath)) {
            // Not found, suspicious
            \Sentry\captureMessage("setNativeCover() failed, no art found - for id: {$this->levelId}");
            return false;
        }

        $this->coverUrl = "https://bssb.app{$expectedPath}";
        return $this->save();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Beat saver

    public function syncFromBeatSaver(): bool
    {
        if (!$this->getIsCustomLevel()) {
            return false;
        }

        if ($this->levelId === "custom_level_3C01DA2A69BA6EB3C2EFD50EEB7C431F09C44C3B") {
            // "Berlin Child - One More Time" ships with mods, but this version is not on BeatSaver
            $this->name = "One More Time";
            $this->songName = "One More Time";
            $this->songAuthor = "Berlin Child";
            $this->levelAuthor = "Freeek";
            $this->duration = 179;
            $this->setNativeCover("OneMoreTime");
            return $this->save();
        }

        $mapData = BeatSaver::fetchMapDataByHash($this->hash);

        if ($mapData) {
            $this->beatsaverId = $mapData['key'];
            $this->name = $mapData['name'];
            $this->description = $mapData['description'] ?? null;

            if (isset($mapData['coverURL'])) {
                $this->coverUrl = "https://beatsaver.com{$mapData['coverURL']}";
            }

            if (isset($mapData['metadata'])) {
                $this->songName = $mapData['metadata']['songName'];
                $this->songAuthor = $mapData['metadata']['songAuthorName'] ?? null;
                $this->levelAuthor = $mapData['metadata']['levelAuthorName'] ?? null;
                $this->duration = intval($mapData['metadata']['duration'] ?? 0);
            }

            return $this->save();
        }

        return false;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Sync

    public static function syncFromAnnounce(string $levelId, ?string $songName, ?string $songAuthor): LevelRecord
    {
        $customLevelHash = LevelId::getHashFromLevelId($levelId);
        $isCustomLevel = !empty($customLevelHash);

        $levelRecord = new LevelRecord();
        $levelRecord->levelId = $levelId;
        $levelRecord->hash = $customLevelHash;

        if ($existingRecord = $levelRecord->fetchExisting()) {
            // We already have a record for this song (by id/hash), an announce won't tell us anything new
            if ($isCustomLevel) {
                if (!$existingRecord->beatsaverId) {
                    // Try asking beat saver API, we don't have API data yet
                    $existingRecord->syncFromBeatSaver();
                }
            }
            // Try to sync/update cover art as needed
            $existingRecord->syncCoverArt();
            return $existingRecord;
        }

        $levelRecord->beatsaverId = null;
        $levelRecord->coverUrl = null;
        $levelRecord->name = $songName ?? "Unknown";
        $levelRecord->songName = $songName ?? "Unknown";
        $levelRecord->songAuthor = $songAuthor;
        $levelRecord->levelAuthor = null;
        $levelRecord->duration = null;
        $levelRecord->description = null;
        $levelRecord->save();

        // Try asking BeatSaver for info, if this is a custom level
        if ($customLevelHash) {
            $levelRecord->syncFromBeatSaver();
        }

        return $levelRecord;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Stats

    public function incrementPlayStat(): bool
    {
        return LevelRecord::query()
            ->update()
            ->set('stat_play_count = stat_play_count + 1')
            ->where('id = ?', $this->id)
            ->execute() > 0;
    }
}