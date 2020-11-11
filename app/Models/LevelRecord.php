<?php

namespace app\Models;

use app\BeatSaber\LevelId;
use app\External\BeatSaver;
use Instasell\Instarecord\Model;

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

    public function getIsCustomLevel(): bool
    {
        return LevelId::isCustomLevel($this->levelId);
    }

    public function syncFromBeatSaver(): bool
    {
        if (!$this->getIsCustomLevel()) {
            return false;
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

    public static function syncFromAnnounce(string $levelId, string $songName, ?string $songAuthor): LevelRecord
    {
        $customLevelHash = LevelId::getHashFromLevelId($levelId);

        $levelRecord = new LevelRecord();
        $levelRecord->levelId = $levelId;
        $levelRecord->hash = $customLevelHash;

        if ($existingRecord = $levelRecord->fetchExisting()) {
            // We already have a record for this song (by id/hash), an announce won't tell us anything new
            if ($customLevelHash && !$existingRecord->beatsaverId) {
                // ...but it might be worth trying beat saver again as we don't have correct data.
                $existingRecord->syncFromBeatSaver();
            }
            return $existingRecord;
        }

        $levelRecord->beatsaverId = null;
        $levelRecord->coverUrl = null;
        $levelRecord->name = $songName;
        $levelRecord->songName = $songName;
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
}