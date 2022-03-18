<?php

namespace app\BeatSaber;

use app\BeatSaber\Enums\GameplayEnabledObstacleType;
use app\BeatSaber\Enums\GameplayEnergyType;
use app\BeatSaber\Enums\GameplayModifier;
use app\BeatSaber\Enums\GameplaySongSpeed;
use SoftwarePunt\Instarecord\Serialization\IDatabaseSerializable;

class GameplayModifiers implements IDatabaseSerializable
{
    private ?string $sourceJson;

    public ?GameplayEnergyType $energyType = null;
    public ?bool $noFailOn0Energy = null;
    public ?bool $instaFail = null;
    public ?bool $failOnSaberClash = null;
    public ?GameplayEnabledObstacleType $enabledObstacleType = null;
    public ?bool $fastNotes = null;
    public ?bool $strictAngles = null;
    public ?bool $disappearingArrows = null;
    public ?bool $ghostNotes = null;
    public ?bool $noBombs = null;
    public ?GameplaySongSpeed $songSpeed = null;
    public ?bool $noArrows = null;
    public ?bool $proMode = null;
    public ?bool $zenMode = null;
    public ?bool $smallCubes = null;
    public ?float $songSpeedMul = null;
    public ?float $cutAngleTolerance = null;
    public ?float $notesUniformScale = null;

    public function __construct(?string $sourceData = null)
    {
        $this->sourceJson = $sourceData;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Modifiers

    /**
     * @return GameplayModifier[]
     */
    public function getModifiers(): array
    {
        $results = [];

        if ($this->disappearingArrows)
            $results[] = GameplayModifier::DisappearingArrows;

        if ($this->songSpeed === GameplaySongSpeed::Faster)
            $results[] = GameplayModifier::FasterSong;

        if ($this->energyType === GameplayEnergyType::Battery && !$this->instaFail)
            $results[] = GameplayModifier::FourLives;

        if ($this->noArrows)
            $results[] = GameplayModifier::NoArrows;

        if ($this->noBombs)
            $results[] = GameplayModifier::NoBombs;

        if ($this->enabledObstacleType === GameplayEnabledObstacleType::NoObstacles)
            $results[] = GameplayModifier::NoObstacles;

        if ($this->instaFail)
            $results[] = GameplayModifier::OneLife;

        if ($this->proMode)
            $results[] = GameplayModifier::ProMode;

        if ($this->songSpeed === GameplaySongSpeed::Slower)
            $results[] = GameplayModifier::SlowerSong;

        if ($this->smallCubes)
            $results[] = GameplayModifier::SmallCubes;

        if ($this->strictAngles)
            $results[] = GameplayModifier::StrictAngles;

        if ($this->fastNotes)
            $results[] = GameplayModifier::SuperFastNotes;

        if ($this->zenMode)
            $results[] = GameplayModifier::ZenMode;

        return $results;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Parse

    public function fillFromArray(array $data): void
    {
        $getMixed = function (string $key) use ($data): mixed {
            return $data[$key] ?? null;
        };
        $getBool = function (string $key) use ($data, $getMixed): bool {
            $val = $getMixed($key);
            return ($val === 1 || $val === true || strval($val) === "true");
        };
        $getInt = function (string $key) use ($data, $getMixed): int {
            return intval($getMixed($key));
        };
        $getFloat = function (string $key) use ($data, $getMixed): int {
            return floatval($getMixed($key));
        };

        $this->energyType = GameplayEnergyType::tryFrom($getInt('energyType'));
        $this->noFailOn0Energy = $getBool('noFailOn0Energy');
        $this->instaFail = $getBool('instaFail');
        $this->failOnSaberClash = $getBool('failOnSaberClash');
        $this->enabledObstacleType = GameplayEnabledObstacleType::tryFrom($getInt('enabledObstacleType'));
        $this->fastNotes = $getBool('fastNotes');
        $this->strictAngles = $getBool('strictAngles');
        $this->disappearingArrows = $getBool('disappearingArrows');
        $this->ghostNotes = $getBool('ghostNotes');
        $this->noBombs = $getBool('noBombs');
        $this->songSpeed = GameplaySongSpeed::tryFrom($getBool('songSpeed'));
        $this->noArrows = $getBool('noArrows');
        $this->proMode = $getBool('proMode');
        $this->zenMode = $getBool('zenMode');
        $this->smallCubes = $getBool('smallCubes');
        $this->songSpeedMul = $getFloat('songSpeedMul');
        $this->cutAngleTolerance = $getFloat('cutAngleTolerance');
        $this->notesUniformScale = $getFloat('notesUniformScale');
    }

    public static function fromArray(array $data): GameplayModifiers
    {
        $modifiers = new GameplayModifiers(json_encode($data));
        $modifiers->fillFromArray($data);
        return $modifiers;
    }

    public function fillFromJson(?string $json): void
    {
        if (empty($json))
            return;

        $parsedArray = @json_decode($json, true);

        if (empty($parsedArray))
            return;

        $this->fillFromArray($parsedArray);
    }

    public static function tryFromJson(?string $json): ?GameplayModifiers
    {
        $modifiers = new GameplayModifiers($json);
        $modifiers->fillFromJson($json);
        return $modifiers;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialization

    public function dbSerialize(): string
    {
        if ($this->sourceJson)
            return $this->sourceJson;

        return json_encode($this);
    }

    public function dbUnserialize(string $storedValue): void
    {
        $this->sourceJson = $storedValue;
        $this->fillFromJson($storedValue);
    }
}