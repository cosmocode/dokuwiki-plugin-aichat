<?php

namespace TikToken;

use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase
{
    public function testEncode(): void
    {
        $encoder = new Encoder();

        $longText = <<<EOT
            BPE ensures that the most common words are represented in the vocabulary as a single token while the rare words are broken down into two or more subword tokens and this is in agreement with what a subword-based tokenization algorithm does.
            EOT;

        $this->assertEquals([1212, 318, 617, 2420], $encoder->encode('This is some text'));
        $this->assertEquals([10134, 23858, 21746], $encoder->encode('hasOwnProperty'));
        $this->assertEquals([10163, 2231, 30924, 3829], $encoder->encode('1234567890'));
        $this->assertEquals([15496, 11854, 616, 1468, 1545], $encoder->encode('Hello darkness my old friend'));
        $this->assertEquals([31373, 50169, 233, 995, 12520, 234, 235], $encoder->encode('hello 👋 world 🌍'));
        $this->assertEquals([33, 11401, 19047, 326, 262, 749, 2219, 2456, 389, 7997, 287, 262, 25818, 355, 257, 2060, 11241, 981, 262, 4071, 2456, 389, 5445, 866, 656, 734, 393, 517, 850, 4775, 16326, 290, 428, 318, 287, 4381, 351, 644, 257, 850, 4775, 12, 3106, 11241, 1634, 11862, 857, 13], $encoder->encode($longText));
        $this->assertEquals([33, 11401, 19047, 326, 262, 749, 2219, 2456, 389, 7997, 287, 262, 25818, 355, 257, 2060, 11241, 981, 262, 4071, 2456, 389, 5445, 866, 656, 734, 393, 517, 850, 4775, 16326, 290, 428, 318, 287, 4381, 351, 644, 257, 850, 4775, 12, 3106, 11241, 1634, 11862, 857, 13], $encoder->encode($longText));
        $this->assertEquals([38374, 268, 292, 256, 446, 274, 31215, 285, 8836, 13], $encoder->encode('Buenas tardes para mí.'));
        $this->assertEquals([65, 2634, 65, 2634], $encoder->encode('bébé'));
        $this->assertEquals([344, 979, 1556, 555, 48659, 660, 18702, 84, 2634, 551, 1216, 272, 16175, 15152, 28141, 1490, 22161, 390, 256, 7834, 8591, 4938, 43816], $encoder->encode('ceci est un texte accentué en français à visée de tester la validité'));
    }
}
