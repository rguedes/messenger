<?php

namespace RTippin\Messenger\Tests\Actions;

use Exception;
use RTippin\Messenger\Actions\Calls\CallBrokerTeardown;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallBrokerTeardownTest extends FeatureTestCase
{
    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $group = $this->createGroupThread($tippin);

        $this->call = $this->createCall($group, $tippin);
    }

    /** @test */
    public function call_teardown_tears_down_call()
    {
        $this->mock(VideoDriver::class)
            ->shouldReceive('destroy')
            ->with($this->call)
            ->andReturn(true);

        app(CallBrokerTeardown::class)->execute($this->call);
    }

    /** @test */
    public function call_teardown_throws_exception_if_destroy_failed()
    {
        $this->mock(VideoDriver::class)
            ->shouldReceive('destroy')
            ->with($this->call)
            ->andReturn(false);

        $this->expectException(Exception::class);

        app(CallBrokerTeardown::class)->execute($this->call);
    }
}
