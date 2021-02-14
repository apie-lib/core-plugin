<?php
namespace Apie\Tests\CorePlugin\DataLayers;

use Apie\CorePlugin\DataLayers\NullDataLayer;
use Apie\MockObjects\ApiResources\SimplePopo;
use PHPUnit\Framework\TestCase;

class NullDataLayerTest extends TestCase
{
    public function testNothing()
    {
        $testItem = new NullDataLayer();
        $resource = $this->prophesize(SimplePopo::class);
        // added so any method call will throw an error.
        $resource->getId()->shouldNotBeCalled();
        $testItem->persistNew($resource->reveal(), []);
        $testItem->persistExisting($resource->reveal(), []);
        $testItem->remove(SimplePopo::class, 1, []);
    }
}
