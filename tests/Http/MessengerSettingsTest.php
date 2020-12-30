<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerSettingsTest extends FeatureTestCase
{
    /** @test */
    public function messenger_created_when_called_from_user_without_messenger()
    {
        $newUser = $this->generateJaneSmith();

        $this->assertDatabaseMissing('messengers', [
            'owner_id' => $newUser->getKey(),
        ]);

        $this->actingAs($newUser);

        $this->getJson(route('api.messenger.settings'))
            ->assertSuccessful()
            ->assertJson([
                'owner_id' => $newUser->getKey(),
                'dark_mode' => true,
            ]);

        $this->assertDatabaseHas('messengers', [
            'owner_id' => $newUser->getKey(),
        ]);
    }

    /** @test */
    public function updating_messenger_settings_validations()
    {
        $this->actingAs($this->userTippin());

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => 'invalid',
            'message_sound' => false,
            'call_ringtone_sound' => 66,
            'notify_sound' => false,
            'dark_mode' => false,
            'online_status' => 5,
        ])
            ->assertJsonValidationErrors([
                'message_popups',
                'call_ringtone_sound',
                'online_status',
            ]);
    }

    /** @test */
    public function updating_messenger_settings_and_set_status_to_away()
    {
        $user = $this->userTippin();

        $this->actingAs($user);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => false,
            'message_sound' => false,
            'call_ringtone_sound' => false,
            'notify_sound' => false,
            'dark_mode' => false,
            'online_status' => 2,
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_popups' => false,
                'message_sound' => false,
                'call_ringtone_sound' => false,
                'notify_sound' => false,
                'dark_mode' => false,
                'online_status' => 2,
            ]);

        $this->assertEquals(2, $user->onlineStatus());
    }

    /** @test */
    public function updating_messenger_settings_and_set_status_to_online()
    {
        $user = $this->userTippin();

        $this->actingAs($user);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => true,
            'message_sound' => true,
            'call_ringtone_sound' => true,
            'notify_sound' => true,
            'dark_mode' => true,
            'online_status' => 1,
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_popups' => true,
                'message_sound' => true,
                'call_ringtone_sound' => true,
                'notify_sound' => true,
                'dark_mode' => true,
                'online_status' => 1,
            ]);

        $this->assertEquals(1, $user->onlineStatus());
    }

    /** @test */
    public function updating_messenger_settings_and_set_status_to_offline()
    {
        $user = $this->userTippin();

        $this->actingAs($user);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => true,
            'message_sound' => true,
            'call_ringtone_sound' => true,
            'notify_sound' => true,
            'dark_mode' => true,
            'online_status' => 0,
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_popups' => true,
                'message_sound' => true,
                'call_ringtone_sound' => true,
                'notify_sound' => true,
                'dark_mode' => true,
                'online_status' => 0,
            ]);

        $this->assertEquals(0, $user->onlineStatus());
    }
}