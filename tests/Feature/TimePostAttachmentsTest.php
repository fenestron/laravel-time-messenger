<?php

namespace Fenestron\TimeMessenger\Tests\Feature;

use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\Tests\TestCase;
use Fenestron\TimeMessenger\TimePostAttachments;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * The key property behind the two-step design: {@see TimePostAttachments::refsFromPost()} makes
 * no requests at all. It is called from a websocket listener, where going to the network would
 * stall every other event.
 */
class TimePostAttachmentsTest extends TestCase
{
    private string $infoUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->infoUrl = config('time-messenger.base_url').'/api/v4/files';

        Http::preventStrayRequests();
    }

    public function test_reads_id_name_and_size_from_the_frame_metadata(): void
    {
        $refs = TimePostAttachments::refsFromPost([
            'metadata' => ['files' => [
                ['id' => 'file-1', 'name' => 'act.pdf', 'size' => 128371, 'mime_type' => 'application/pdf'],
                ['id' => 'file-2', 'name' => 'estimate.xlsx', 'size' => 2048],
            ]],
        ]);

        $this->assertSame([
            ['id' => 'file-1', 'name' => 'act.pdf', 'size' => 128371],
            ['id' => 'file-2', 'name' => 'estimate.xlsx', 'size' => 2048],
        ], $refs);
    }

    public function test_skips_entries_without_an_id(): void
    {
        $refs = TimePostAttachments::refsFromPost([
            'metadata' => ['files' => [
                'a string instead of an object',
                ['name' => 'no-id.pdf', 'size' => 10],
                ['id' => '', 'name' => 'empty-id.pdf'],
                ['id' => 'file-3', 'name' => 'alive.pdf', 'size' => 10],
            ]],
        ]);

        $this->assertSame([['id' => 'file-3', 'name' => 'alive.pdf', 'size' => 10]], $refs);
    }

    public function test_falls_back_to_file_ids_without_metadata(): void
    {
        $refs = TimePostAttachments::refsFromPost(['file_ids' => ['file-9', '', 'file-10']]);

        $this->assertSame([
            ['id' => 'file-9', 'name' => '', 'size' => 0],
            ['id' => 'file-10', 'name' => '', 'size' => 0],
        ], $refs);
    }

    public function test_post_without_attachments_gives_an_empty_list(): void
    {
        $this->assertSame([], TimePostAttachments::refsFromPost([]));
        $this->assertSame([], TimePostAttachments::refsFromPost(['metadata' => ['files' => []]]));
        $this->assertSame([], TimePostAttachments::refsFromPost(['file_ids' => 'not an array']));
    }

    public function test_known_metadata_costs_no_request(): void
    {
        $refs = Time::attachments()->resolveMissing(
            [['id' => 'file-1', 'name' => 'act.pdf', 'size' => 10]],
            sizeRequired: true,
        );

        $this->assertSame([['id' => 'file-1', 'name' => 'act.pdf', 'size' => 10]], $refs);
    }

    public function test_size_is_requested_only_when_it_is_needed(): void
    {
        // The size is needed only by callers that filter files before downloading them;
        // for everyone else an extra `info()` per attachment with a known name is wasted.
        Http::fake([$this->infoUrl.'/*' => Http::response(['name' => 'act.pdf', 'size' => 999])]);

        $refs = Time::attachments()->resolveMissing([['id' => 'file-1', 'name' => 'act.pdf', 'size' => 0]]);

        $this->assertSame(0, $refs[0]['size']);
        Http::assertNothingSent();
    }

    public function test_fills_the_missing_name_and_size_from_info(): void
    {
        Http::fake([$this->infoUrl.'/file-9/info' => Http::response(['name' => 'from-info.pdf', 'size' => 4242])]);

        $refs = Time::attachments()->resolveMissing(
            [['id' => 'file-9', 'name' => '', 'size' => 0]],
            sizeRequired: true,
        );

        $this->assertSame([['id' => 'file-9', 'name' => 'from-info.pdf', 'size' => 4242]], $refs);

        Http::assertSent(fn (Request $request): bool => $request->url() === $this->infoUrl.'/file-9/info');
    }

    public function test_failed_info_leaves_the_id_as_the_name_instead_of_throwing(): void
    {
        // One attachment with an unknown name must not cost the whole message.
        Http::fake([$this->infoUrl.'/file-9/info' => Http::response('', 500)]);

        $refs = Time::attachments()->resolveMissing(
            [['id' => 'file-9', 'name' => '', 'size' => 0]],
            sizeRequired: true,
        );

        $this->assertSame([['id' => 'file-9', 'name' => 'file-9', 'size' => 0]], $refs);
    }
}
