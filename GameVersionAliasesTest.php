<?php

namespace tests;

use app\BeatSaber\GameVersionAliases;
use app\Common\CVersion;
use PHPUnit\Framework\TestCase;

class GameVersionAliasesTest extends TestCase
{
    public function testGetAliasesFor()
    {
        $this->assertEmpty(
            GameVersionAliases::getAliasesFor(new CVersion("1.2.3"), false),
            "Should not return any versions for unknown / invalid versions (no includeBaseVersion)"
        );

        $this->assertEquals(
            [new CVersion("1.2.3")],
            GameVersionAliases::getAliasesFor(new CVersion("1.2.3"), true),
            "Should only return self versions for unknown / invalid versions (with includeBaseVersion)"
        );

        $this->assertEquals(
            [new CVersion("1.18.0"), new CVersion("1.18.1")],
            GameVersionAliases::getAliasesFor(new CVersion("1.18.0"), true),
            "Should return aliased version for 1.80.0 (left side alias)"
        );

        $this->assertEquals(
            [new CVersion("1.18.0"), new CVersion("1.18.1")],
            GameVersionAliases::getAliasesFor(new CVersion("1.18.1"), true),
            "Should return aliased version for 1.80.1, sorted in order (right side alias)"
        );
    }
}
