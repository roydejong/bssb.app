<?php

namespace tests;

use app\BeatSaber\LevelDifficulty;
use PHPUnit\Framework\TestCase;

class LevelDifficultyTest extends TestCase
{
    public function testDescribe()
    {
        $this->assertSame("Easy", LevelDifficulty::describe(0));
        $this->assertSame("Normal", LevelDifficulty::describe(1));
        $this->assertSame("Hard", LevelDifficulty::describe(2));
        $this->assertSame("Expert", LevelDifficulty::describe(3));
        $this->assertSame("Expert+", LevelDifficulty::describe(4));
        $this->assertSame("Unknown", LevelDifficulty::describe(5));
        $this->assertSame("Unknown", LevelDifficulty::describe(null));
    }
}
