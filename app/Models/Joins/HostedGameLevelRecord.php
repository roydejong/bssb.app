<?php

namespace app\Models\Joins;

use app\Models\HostedGame;
use SoftwarePunt\Instarecord\Models\IReadOnlyModel;

class HostedGameLevelRecord extends HostedGame implements IReadOnlyModel
{
    public ?string $beatsaverId;
    public ?string $coverUrl;
    public ?string $levelName;

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    public function serializeLevel(): ?array
    {
        if ($this->levelId === null)
            return null;

        return [
            'levelId' => $this->levelId,
            'songName' => $this->songName,
            'songSubName' => null, // TODO add sub name field
            'songAuthorName' => $this->songAuthor,
            'levelAuthorName' => null, // not currently included in join
            'coverUrl' => $this->coverUrl
        ];
    }
}