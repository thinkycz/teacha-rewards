<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Web;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Thinkycz\LaravelCore\Support\Typer;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = Typer::assertInstance(UserFactory::new()->createOne(), User::class);

        $response = $this->be($user, 'users')->get('/dashboard', $this->inertiaHeaders());

        $response->assertOk();
        $response->assertJsonPath('component', 'Dashboard');
        $response->assertJsonPath('props.auth.user.email', $user->getEmail());
    }
}
