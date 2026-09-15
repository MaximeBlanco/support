<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    public function test_the_login_screen_is_reachable(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_a_user_signs_in_with_the_right_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'not-the-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_registration_creates_a_requester(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Maxime Blanco')
            ->set('email', 'maxime@example.test')
            ->set('password', 'motdepasse1')
            ->set('password_confirmation', 'motdepasse1')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'maxime@example.test')->sole();

        $this->assertTrue($user->hasRole(RoleName::Requester));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_refuses_a_duplicate_email(): void
    {
        $existing = User::factory()->create();

        Livewire::test(Register::class)
            ->set('name', 'Quelqu’un')
            ->set('email', $existing->email)
            ->set('password', 'motdepasse1')
            ->set('password_confirmation', 'motdepasse1')
            ->call('register')
            ->assertHasErrors('email');
    }

    public function test_registration_refuses_a_mismatched_confirmation(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Maxime Blanco')
            ->set('email', 'autre@example.test')
            ->set('password', 'motdepasse1')
            ->set('password_confirmation', 'autrechose2')
            ->call('register')
            ->assertHasErrors('password');
    }

    public function test_a_user_signs_out(): void
    {
        $this->actingAs($this->requester())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
