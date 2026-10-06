<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Data;

use Fenestron\TimeMessenger\Data\GetPostsByIdsData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GetPostsByIdsDataTest extends TestCase
{
    public function test_to_body_returns_ids_as_is(): void
    {
        $data = new GetPostsByIdsData(['post1', 'post2']);

        $this->assertSame(['post1', 'post2'], $data->toBody());
    }

    public function test_throws_when_ids_are_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The list of ids must not be empty');

        new GetPostsByIdsData([]);
    }
}
