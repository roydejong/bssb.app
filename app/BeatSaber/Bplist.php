<?php

namespace app\BeatSaber;

use app\Models\LevelRecord;
use app\Utils\Base64;

/**
 * Utility for generating *.bplist files (Beat Saber Playlists / BeatDrop format).
 */
class Bplist
{
    // -----------------------------------------------------------------------------------------------------------------
    // Metadata

    private string $title = "Untitled Playlist";
    private string $author = "bssb.app";
    private string $description = "";
    private ?string $syncUrl = null;

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setSyncUrl(?string $syncUrl): void
    {
        $this->syncUrl = $syncUrl;
    }

    public function getSyncUrl(): ?string
    {
        return $this->syncUrl;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Image data

    private ?string $imageDataRaw = null;

    public function setImageFromLocalFile(string $localPath): bool
    {
        $imageData = Base64::getDataForImageFile($localPath);
        if ($imageData) {
            $this->imageDataRaw = $imageData;
            return true;
        }
        return false;
    }

    public function getHasImage(): bool
    {
        return !!$this->imageDataRaw;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Song data

    /**
     * @var array
     */
    private array $songs = [];

    public function addSong(string $hash, string $songName, ?string $key = null): void
    {
        $songData = [
            'hash' => $hash,
            'songName' => $songName
        ];

        if ($key) {
            $songData['key'] = $key;
        }

        $this->songs[] = $songData;
    }

    public function addSongByLevelRecord(LevelRecord $record): bool
    {
        if ($record->hash) {
            $this->addSong(hash: $record->hash, songName: $record->describeSong(), key: $record->beatsaverId);
            return true;
        }
        return false;
    }

    public function getSongs(): array
    {
        return $this->songs;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialization

    public function serialize(): array
    {
        return [
            'playlistTitle' => $this->title,
            'playlistAuthor' => $this->author,
            'playlistDescription' => $this->description,
            'syncURL' => $this->syncUrl,
            'image' => $this->imageDataRaw,
            'songs' => $this->songs
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->serialize(), JSON_PRETTY_PRINT);
    }
}