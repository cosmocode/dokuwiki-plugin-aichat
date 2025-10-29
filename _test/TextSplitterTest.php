<?php

namespace dokuwiki\plugin\aichat\test;

use dokuwiki\plugin\aichat\TextSplitter;
use DokuWikiTest;
use TikToken\Encoder;

/**
 * Tests for the TextSplitter class
 *
 * @group plugin_aichat
 * @group plugins
 */
class TextSplitterTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['aichat'];

    const CHUNKSIZE = 10; // 10 token chunks for testing
    const OVERLAP = 5;  // 2 token overlap for testing
    private TextSplitter $splitter;
    private Encoder $encoder;

    public function setUp(): void
    {
        parent::setUp();
        $this->encoder = new Encoder();
        $this->splitter = new TextSplitter(self::CHUNKSIZE, $this->encoder, self::OVERLAP);
    }

    /**
     * Test basic text splitting functionality
     */
    public function testSplitIntoChunks(): void
    {
        $text = "This is the first sentence. This is the second sentence. This is the third sentence.";
        $chunks = $this->splitter->splitIntoChunks($text);

        $this->assertIsArray($chunks);
        $this->assertNotEmpty($chunks);

        $this->assertGreaterThan(1, count($chunks)); // Should be split into multiple chunks


        foreach ($chunks as $chunk) {
            // Each chunk should be non-empty
            $this->assertNotEmpty(trim($chunk));

            // Each chunk should be within the token limit
            $tokenCount = count($this->encoder->encode($chunk));
            $this->assertLessThanOrEqual(self::CHUNKSIZE, $tokenCount);
        }
    }

    /**
     * Test splitting with empty text
     */
    public function testSplitEmptyText(): void
    {
        $chunks = $this->splitter->splitIntoChunks('');
        $this->assertIsArray($chunks);
        $this->assertEmpty($chunks);
    }

    /**
     * Test splitting with whitespace only
     */
    public function testSplitWhitespaceOnly(): void
    {
        $chunks = $this->splitter->splitIntoChunks('   ');
        $this->assertIsArray($chunks);
        $this->assertEmpty($chunks);
    }

    /**
     * Test splitting a single short sentence
     */
    public function testSplitSingleShortSentence(): void
    {
        $text = "This is a short sentence.";
        $chunks = $this->splitter->splitIntoChunks($text);

        $this->assertCount(1, $chunks);
        $this->assertEquals($text, $chunks[0]);
    }

    /**
     * Test splitting multiple sentences that fit in one chunk
     */
    public function testSplitMultipleSentencesOneChunk(): void
    {
        $text = "First sentence. Second sentence. Third sentence.";
        $chunks = $this->splitter->splitIntoChunks($text);

        $this->assertCount(1, $chunks);
        $this->assertEquals($text, $chunks[0]);
    }

    /**
     * Test that chunks have proper overlap
     */
    public function testChunkOverlap(): void
    {
        $text = "First sentence. Second sentence. Third sentence. Fourth sentence. Fifth sentence.";

        $chunks = $this->splitter->splitIntoChunks($text);
        $this->assertGreaterThan(1, count($chunks));

        $this->assertStringEndsWith('Third sentence.', $chunks[0]);
        $this->assertStringStartsWith('Third sentence.', $chunks[1]);
    }

    /**
     * Test splitLongSentence protected method
     */
    public function testSplitLongSentence(): void
    {
        // Create a very long sentence without periods
        $longSentence = str_repeat("long word is long ", 20);

        $result = self::callInaccessibleMethod($this->splitter, 'splitLongSentence', [$longSentence]);

        $this->assertIsArray($result);
        $this->assertGreaterThan(1, count($result));

        // Each sub-sentence should be shorter than the original
        foreach ($result as $subSentence) {
            $this->assertLessThan(strlen($longSentence), strlen($subSentence));
        }

        // Verify all pieces together reconstruct the original
        $reconstructed = implode('', $result);
        $this->assertEquals($longSentence, $reconstructed);
    }

    /**
     * Test splitString protected method
     */
    public function testSplitString(): void
    {
        $text = str_repeat("verylongwordwithoutspaces", 20);
        $tokenLength = count($this->encoder->encode($text));
        $chunkSize = 5;

        $result = self::callInaccessibleMethod($this->splitter, 'splitString', [$text, $tokenLength, $chunkSize]);

        $this->assertIsArray($result);
        $this->assertGreaterThan(1, count($result));

        // Each sub-sentence should be shorter than the original
        foreach ($result as $subSentence) {
            $this->assertLessThan(strlen($text), strlen($subSentence));
        }

        // Verify all pieces together reconstruct the original
        $reconstructed = implode('', $result);
        $this->assertEquals($text, $reconstructed);
    }

    /**
     * Test rememberSentence protected method
     */
    public function testRememberSentence(): void
    {
        // Clear the sentence queue first
        self::setInaccessibleProperty($this->splitter, 'sentenceQueue', []);

        // Sentence queue should be empty now
        $initialQueue = self::getInaccessibleProperty($this->splitter, 'sentenceQueue');
        $this->assertEmpty($initialQueue);

        // Add a sentence
        self::callInaccessibleMethod($this->splitter, 'rememberSentence', ['First sentence.']);
        $queue = self::getInaccessibleProperty($this->splitter, 'sentenceQueue');
        $this->assertGreaterThanOrEqual(1, count($queue));
        $this->assertContains('First sentence.', $queue);

        // Add another sentence
        self::callInaccessibleMethod($this->splitter, 'rememberSentence', ['Second sentence.']);
        $queue = self::getInaccessibleProperty($this->splitter, 'sentenceQueue');
        $this->assertGreaterThan(1, $queue);
        $this->assertContains('Second sentence.', $queue);

        // add a whole bunch of sentences to exceed the overlap limit
        for ($i = 0; $i < 20; $i++) {
            self::callInaccessibleMethod($this->splitter, 'rememberSentence', ["Sentence $i."]);
        }

        // each of our sentences is at least 2 tokens, our limit is 5, so we should not have more than 2 in queue
        $queue = self::getInaccessibleProperty($this->splitter, 'sentenceQueue');
        $this->assertLessThanOrEqual(2, count($queue));
    }
}
