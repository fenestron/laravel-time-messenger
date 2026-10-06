<?php

namespace Fenestron\TimeMessenger\Tests\Unit\Data;

use Fenestron\TimeMessenger\Data\GetChannelPostsData;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GetChannelPostsDataTest extends TestCase
{
    public function test_to_query_is_empty_by_default(): void
    {
        $this->assertSame([], (new GetChannelPostsData)->toQuery());
    }

    public function test_to_query_maps_all_provided_fields(): void
    {
        $data = new GetChannelPostsData(
            page: 2,
            perPage: 30,
            before: 'post1',
            after: 'post2',
            skipFetchThreads: true,
            collapsedThreads: false,
            collapsedThreadsExtended: true,
        );

        $this->assertSame([
            'page'                     => 2,
            'per_page'                 => 30,
            'before'                   => 'post1',
            'after'                    => 'post2',
            'skipFetchThreads'         => true,
            'collapsedThreads'         => false,
            'collapsedThreadsExtended' => true,
        ], $data->toQuery());
    }

    public function test_to_query_accepts_since_alone(): void
    {
        $this->assertSame(['since' => 1000], (new GetChannelPostsData(since: 1000))->toQuery());
    }

    /**
     * @param  array<string, int|string>  $conflicting
     */
    #[DataProvider('paramsConflictingWithSince')]
    public function test_throws_when_since_is_combined_with_paging(array $conflicting): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The since parameter cannot be combined');

        new GetChannelPostsData(...['since' => 1000, ...$conflicting]);
    }

    /**
     * @return array<string, array{array<string, int|string>}>
     */
    public static function paramsConflictingWithSince(): array
    {
        return [
            'page'     => [['page' => 1]],
            'per_page' => [['perPage' => 10]],
            'before'   => [['before' => 'post1']],
            'after'    => [['after' => 'post1']],
        ];
    }
}
