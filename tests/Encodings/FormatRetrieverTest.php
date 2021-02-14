<?php
namespace Apie\Tests\CorePlugin\Encodings;

use Apie\CorePlugin\Encodings\FormatRetriever;
use PHPUnit\Framework\TestCase;

class FormatRetrieverTest extends TestCase
{
    private $testItem;

    protected function setUp(): void
    {
        $this->testItem = new FormatRetriever([
            'application/json' => 'json',
            'application/xml' => 'xml',
        ]);
    }

    /**
     * @dataProvider  getFormatProvider
     */
    public function testGetFormat(?string $expected, string $input)
    {
        $this->assertEquals($expected, $this->testItem->getFormat($input));
    }

    public function getFormatProvider()
    {
        yield ['xml', 'application/xml'];
        yield ['json', 'application/json'];
        yield [null, 'text/json'];
    }

    /**
     * @dataProvider  getContentTypeProvider
     */
    public function testGetContentType(?string $expected, string $input)
    {
        $this->assertEquals($expected, $this->testItem->getContentType($input));
    }

    public function getContentTypeProvider()
    {
        yield ['application/xml', 'xml'];
        yield ['application/json', 'json'];
        yield [null, 'html'];
    }
}
