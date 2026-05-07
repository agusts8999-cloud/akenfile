<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedTrashControlCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_soft_delete_and_restore_file_from_trash(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $file = File::query()->create([
            'name' => 'demo.pdf',
            'path' => 'uploads/'.$user->id.'/root/demo.pdf',
            'size' => 1000,
            'mime' => 'application/pdf',
            'user_id' => $user->id,
            'folder_id' => null,
        ]);

        $this->actingAs($user)->delete(route('files.destroy', $file))->assertRedirect();
        $this->assertSoftDeleted('files', ['id' => $file->id]);

        $this->actingAs($user)->post(route('trash.files.restore', $file->id))->assertRedirect();
        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
    }

    public function test_user_can_share_file_to_another_user(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $target = User::factory()->create(['role' => 'user']);
        $file = File::query()->create([
            'name' => 'report.pdf',
            'path' => 'uploads/'.$owner->id.'/root/report.pdf',
            'size' => 2048,
            'mime' => 'application/pdf',
            'user_id' => $owner->id,
            'folder_id' => null,
        ]);

        $this->actingAs($owner)->post(route('shared.user.store'), [
            'shared_item' => 'file:'.$file->id,
            'target_user_id' => $target->id,
            'permission' => 'viewer',
        ])->assertRedirect();

        $this->assertDatabaseHas('file_shares', [
            'file_id' => $file->id,
            'target_user_id' => $target->id,
            'permission' => 'viewer',
        ]);
    }

    public function test_admin_can_update_control_center_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('control-center.settings.update'), [
            'theme' => 'dark',
            'allowed_extensions' => 'jpg,png,pdf',
            'max_upload_size_mb' => 15,
            'storage_limit_gb' => 25,
        ])->assertRedirect();

        $this->assertDatabaseHas('system_settings', ['key' => 'theme', 'value' => 'dark']);
        $this->assertDatabaseHas('system_settings', ['key' => 'max_upload_size_mb', 'value' => '15']);
        $this->assertDatabaseHas('system_settings', ['key' => 'storage_limit_gb', 'value' => '25']);
    }
}
