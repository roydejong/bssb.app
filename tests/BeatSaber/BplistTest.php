<?php

namespace BeatSaber;

use app\BeatSaber\Bplist;
use app\Models\LevelRecord;
use PHPUnit\Framework\TestCase;

class BplistTest extends TestCase
{
    // -----------------------------------------------------------------------------------------------------------------
    // Metadata

    public function testGetAndSetMetadata()
    {
        $bpl = new Bplist();

        $this->assertSame("Untitled Playlist", $bpl->getTitle(), "Default title should be Untitled");
        $this->assertSame("bssb.app", $bpl->getAuthor(), "Default author should be BSSB");
        $this->assertSame("", $bpl->getDescription(), "Default description should be empty");

        $bpl->setTitle("My playlist");
        $bpl->setAuthor("My author");
        $bpl->setDescription("My description");

        $this->assertSame("My playlist", $bpl->getTitle(), "Default title should be empty");
        $this->assertSame("My author", $bpl->getAuthor(), "Default author should be empty");
        $this->assertSame("My description", $bpl->getDescription(), "Default description should be empty");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Image

    public function testSetImageFromLocalFile()
    {
        $bpl = new Bplist();

        // Default state
        $this->assertFalse($bpl->getHasImage(),
            "New bplist should have no image");
        $this->assertNull($bpl->serialize()['image'],
            "New bplist should have NULL image when serialized");

        // Invalid image
        $this->assertFalse($bpl->setImageFromLocalFile("invalid_file"),
            "Setting invalid image on bplist should fail and return false");
        $this->assertFalse($bpl->getHasImage(),
            "Setting invalid image on bplist should have no effect on state");
        $this->assertNull($bpl->serialize()['image'],
            "Setting invalid image on bplist should have no effect on output");

        // Valid image
        $sampleImage = DIR_BASE . '/public/static/bsassets/MicDrop.png';
        $sampleImageB64 = "data:image/png;base64,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";

        $this->assertTrue($bpl->setImageFromLocalFile($sampleImage),
            "Setting a valid image should succeed and return true");
        $this->assertTrue($bpl->getHasImage(),
            "Setting a valid image should cause state to be updated");
        $this->assertSame($sampleImageB64, $bpl->serialize()['image'],
            "Setting a valid image should result in a base64-encoded image in output");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Songs

    public function testAddSongs()
    {
        // Initialize
        $bpl = new Bplist();
        $this->assertEmpty($bpl->getSongs(),
            "A new bplist should have an empty song list");

        // Manual add
        $bpl->addSong("bla_123", "Manually Added Song", "testkey");
        $this->assertSame(
            ["hash" => "bla_123", "songName" => "Manually Added Song", "key" => "testkey"],
            $bpl->getSongs()[0],
            "It should be possible to manually insert songs by hash and name"
        );

        // Add via LevelRecord
        $testLevelRecord = new LevelRecord();
        $testLevelRecord->beatsaverId = "keyTest";
        $testLevelRecord->levelId = "custom_level_1234567890000000000000000000000000000000";
        $testLevelRecord->hash = "1234567890000000000000000000000000000000";
        $testLevelRecord->songName = "TestName";
        $testLevelRecord->songAuthor = "TestAuthor";
        $this->assertTrue($bpl->addSongByLevelRecord($testLevelRecord));
        $this->assertSame([
                'hash' => '1234567890000000000000000000000000000000',
                'songName' => 'TestAuthor - TestName',
                'key' => 'keyTest'
            ],
            $bpl->getSongs()[1],
            "It should be possible to insert songs by LevelRecord"
        );
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    /**
     * @depends testGetAndSetMetadata
     * @depends testSetImageFromLocalFile
     * @depends testAddSongs
     */
    public function testToJson()
    {
        $bpl = new Bplist();
        $bpl->setTitle("Full Test");
        $bpl->setAuthor("testToJson");
        $bpl->setDescription("Hello world!");
        $bpl->setImageFromLocalFile(DIR_BASE . '/public/static/bsassets/MicDrop.png');
        $bpl->setSyncUrl("https://site.web/file.bplist");
        $bpl->addSong(hash: "1234567890000000000000000000000000000000", songName: "A song", key: "abcd");
        $bpl->addSong(hash: "1234567890000000000FEEFA00000000000BEEBA", songName: "Another song");

        $this->assertSame(
            json_encode(json_decode(file_get_contents(__DIR__ . "/sample/testToJson.bplist"))),
            json_encode(json_decode(($bpl->toJson()))),
            "toJson() should generate a correct and nicely formatted bplist"
        );
    }
}
