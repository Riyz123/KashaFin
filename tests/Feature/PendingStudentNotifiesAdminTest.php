<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use App\Notifications\NewPendingStudentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PendingStudentNotifiesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function registerAndVerify(Faculty $faculty, string $email = 'ana.torres@upn.pe'): void
    {
        Mail::fake();

        $this->post(route('register'), ['name' => 'Ana Torres', 'email' => $email, 'faculty_id' => $faculty->id]);

        $code = Cache::get('registration_code:'.$email)['code'];

        $this->post(route('register.verify.submit'), ['code' => $code]);
    }

    public function test_the_faculty_dean_is_notified_when_a_student_self_registers(): void
    {
        Notification::fake();

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);

        $this->registerAndVerify($faculty);

        $student = User::where('email', 'ana.torres@upn.pe')->first();

        Notification::assertSentTo(
            $dean,
            NewPendingStudentNotification::class,
            fn ($notification, $channels) => $notification->toArray($dean)['pending_student_id'] === $student->id
        );
    }

    public function test_the_master_admin_is_notified_even_without_a_dean_assigned(): void
    {
        Notification::fake();

        $faculty = Faculty::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->registerAndVerify($faculty);

        Notification::assertSentTo($admin, NewPendingStudentNotification::class);
    }

    public function test_the_notification_links_to_the_pending_students_list(): void
    {
        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);

        $this->registerAndVerify($faculty);

        $response = $this->actingAs($dean)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee(route('admin.users.index', ['status' => 'pending']), false);
    }
}
