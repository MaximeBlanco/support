<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * phpunit.xml forces the null broadcaster, which waves every subscription
 * through. These tests put a real one in place before the application boots —
 * switching it afterwards leaves the new driver without the channels that
 * routes/channels.php registered, and every subscription would then be refused
 * for the wrong reason.
 */
class BroadcastAuthorizationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        $this->setEnvironment([
            'BROADCAST_CONNECTION' => 'reverb',
            'REVERB_APP_KEY' => 'test-key',
            'REVERB_APP_SECRET' => 'test-secret',
            'REVERB_APP_ID' => 'test-app',
        ]);

        parent::setUp();

        $this->seedRoles();
    }

    protected function tearDown(): void
    {
        $this->setEnvironment([
            'BROADCAST_CONNECTION' => 'null',
            'REVERB_APP_KEY' => '',
            'REVERB_APP_SECRET' => '',
            'REVERB_APP_ID' => '',
        ]);

        parent::tearDown();
    }

    /**
     * Laravel reads env from $_ENV and $_SERVER, which phpunit.xml has already
     * filled in — putenv would be ignored.
     *
     * @param  array<string, string>  $values
     */
    private function setEnvironment(array $values): void
    {
        foreach ($values as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public function test_the_real_broadcaster_is_in_place(): void
    {
        $this->assertSame('reverb', config('broadcasting.default'));
    }

    public function test_a_user_can_subscribe_to_their_own_channel(): void
    {
        $mine = $this->requester();

        $this->actingAs($mine)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-users.'.$mine->getKey(),
                'socket_id' => '1.1',
            ])
            ->assertOk();
    }

    public function test_a_user_cannot_subscribe_to_someone_elses_channel(): void
    {
        $mine = $this->requester();
        $other = $this->requester();

        $this->actingAs($mine)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-users.'.$other->getKey(),
                'socket_id' => '1.1',
            ])
            ->assertForbidden();
    }

    public function test_a_guest_cannot_subscribe_at_all(): void
    {
        $user = $this->requester();

        $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-users.'.$user->getKey(),
            'socket_id' => '1.1',
        ])->assertForbidden();
    }

    public function test_an_unknown_channel_is_refused(): void
    {
        $user = $this->requester();

        $this->actingAs($user)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-tickets.all',
                'socket_id' => '1.1',
            ])
            ->assertForbidden();
    }
}
