<?php

namespace Fenestron\TimeMessenger\Tests\Unit;

use Fenestron\TimeMessenger\DirectChannel;
use PHPUnit\Framework\TestCase;

class DirectChannelTest extends TestCase
{
    public function test_recognizes_valid_direct_channel_name(): void
    {
        $this->assertTrue(DirectChannel::isDirectChannelName(
            '5uwtoxpropnx98e4pirshw9pgw__xx53fxcks7batyiqjcc87j37mc',
        ));
    }

    public function test_rejects_human_readable_names(): void
    {
        $this->assertFalse(DirectChannel::isDirectChannelName('general'));
        $this->assertFalse(DirectChannel::isDirectChannelName('team-backend'));
    }

    public function test_rejects_separator_with_wrong_lengths(): void
    {
        $this->assertFalse(DirectChannel::isDirectChannelName('abc__def'));
    }

    public function test_rejects_three_groups(): void
    {
        $this->assertFalse(DirectChannel::isDirectChannelName(
            '5uwtoxpropnx98e4pirshw9pgw__xx53fxcks7batyiqjcc87j37mc__5uwtoxpropnx98e4pirshw9pgw',
        ));
    }

    public function test_rejects_extra_characters_around_pattern(): void
    {
        $this->assertFalse(DirectChannel::isDirectChannelName(
            ' 5uwtoxpropnx98e4pirshw9pgw__xx53fxcks7batyiqjcc87j37mc',
        ));
        $this->assertFalse(DirectChannel::isDirectChannelName(
            '5uwtoxpropnx98e4pirshw9pgw__xx53fxcks7batyiqjcc87j37mcx',
        ));
    }

    public function test_rejects_empty_string(): void
    {
        $this->assertFalse(DirectChannel::isDirectChannelName(''));
    }

    public function test_rejects_uppercase_ids(): void
    {
        $this->assertFalse(DirectChannel::isDirectChannelName(
            '5UWTOXPROPNX98E4PIRSHW9PGW__XX53FXCKS7BATYIQJCC87J37MC',
        ));
    }
}
