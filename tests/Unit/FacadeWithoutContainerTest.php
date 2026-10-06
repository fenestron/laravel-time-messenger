<?php

namespace Fenestron\TimeMessenger\Tests\Unit;

use Fenestron\TimeMessenger\Laravel\Facades\Time;
use Fenestron\TimeMessenger\TimeClient;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class FacadeWithoutContainerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();

        parent::tearDown();
    }

    public function test_swap_makes_the_facade_work_without_a_container(): void
    {
        $http = new Factory;
        $http->fake(['https://time.test/api/v4/users/me' => Factory::response(['id' => 'self'])]);

        Time::swap(new TimeClient('https://time.test', 'secret', http: $http));

        $this->assertSame('self', Time::users()->get('me')['id']);
        $http->assertSent(fn (Request $request): bool => $request->url() === 'https://time.test/api/v4/users/me');
    }
}
