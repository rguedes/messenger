<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Messenger\OnlineStatus;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\StoreMessengerIp;
use RTippin\Messenger\Tests\FeatureTestCase;

class OnlineStatusTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function online_status_sets_away_cache()
    {
        app(OnlineStatus::class)->withoutDispatches()->execute(true);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));

        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function online_status_sets_online_cache()
    {
        app(OnlineStatus::class)->withoutDispatches()->execute(false);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));

        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function online_status_sets_no_cache_when_disabled_from_config()
    {
        Messenger::setOnlineStatus(false);

        app(OnlineStatus::class)->withoutDispatches()->execute(false);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function online_status_does_not_touch_provider_when_set_to_offline()
    {
        $before = now()->subMinutes(5);

        Carbon::setTestNow($before);

        $this->tippin->update([
            'updated_at' => $before,
        ]);

        Messenger::getProviderMessenger()->update([
            'online_status' => 0,
        ]);

        app(OnlineStatus::class)->withoutDispatches()->execute(false);

        $this->assertDatabaseHas('users', [
            'id' => $this->tippin->getKey(),
            'updated_at' => $before,
        ]);
    }

    /** @test */
    public function online_status_touches_provider()
    {
        $before = now()->subMinutes(5);

        $this->tippin->update([
            'updated_at' => $before,
        ]);

        app(OnlineStatus::class)->withoutDispatches()->execute(true);

        $this->assertNotSame($before->toDayDateTimeString(), $this->tippin->updated_at->toDayDateTimeString());
    }

    /** @test */
    public function online_status_away_fires_event()
    {
        Event::fake([
            StatusHeartbeatEvent::class,
        ]);

        app(OnlineStatus::class)->execute(true);

        Event::assertDispatched(function (StatusHeartbeatEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertTrue($event->away);
            $this->assertSame('127.0.0.1', $event->IP);

            return true;
        });
    }

    /** @test */
    public function online_status_online_fires_event()
    {
        Event::fake([
            StatusHeartbeatEvent::class,
        ]);

        app(OnlineStatus::class)->execute(false);

        Event::assertDispatched(function (StatusHeartbeatEvent $event) {
            return $event->away === false;
        });
    }

    /** @test */
    public function online_status_triggers_listener()
    {
        Bus::fake();

        app(OnlineStatus::class)->execute(true);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === StoreMessengerIp::class;
        });
    }
}
