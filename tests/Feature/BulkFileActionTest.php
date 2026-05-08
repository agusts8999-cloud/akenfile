<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkFileActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_bulk_delete_files_and_folders(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $folder = Folder::query()->create([
            'name' => 'Docs',
            'parent_id' => null,
            'user_id' => $user->id,
        ]);
        $file = File::query()->create([
            'name' => 'a.txt',
            'path' => 'uploads/'.$user->id.'/root/a.txt',
            'size' => 123,
            'mime' => 'text/plain',
            'user_id' => $user->id,
            'folder_id' => null,
        ]);

        $this->actingAs($user)
            ->postJson('/files/bulk/delete', [
                'file_ids' => [$file->id],
                'folder_ids' => [$folder->id],
            ])
            ->assertOk();

        $this->assertSoftDeleted('files', ['id' => $file->id]);
        $this->assertSoftDeleted('folders', ['id' => $folder->id]);
    }

    public function test_user_can_bulk_move_and_copy_files(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $targetFolder = Folder::query()->create([
            'name' => 'Target',
            'parent_id' => null,
            'user_id' => $user->id,
        ]);

        $file = File::query()->create([
            'name' => 'report.pdf',
            'path' => 'uploads/'.$user->id.'/root/report.pdf',
            'size' => 777,
            'mime' => 'application/pdf',
            'user_id' => $user->id,
            'folder_id' => null,
        ]);

        $this->actingAs($user)
            ->postJson('/files/bulk/move', [
                'file_ids' => [$file->id],
                'folder_ids' => [],
                'target_folder_id' => $targetFolder->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'folder_id' => $targetFolder->id,
        ]);

        $this->actingAs($user)
            ->postJson('/files/bulk/copy', [
                'file_ids' => [$file->id],
                'folder_ids' => [],
                'target_folder_id' => null,
            ])
            ->assertOk();

        $this->assertDatabaseCount('files', 2);
        $this->assertDatabaseHas('files', [
            'name' => 'report (copy).pdf',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_bulk_delete_other_user_items(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $attacker = User::factory()->create(['role' => 'user']);

        $file = File::query()->create([
            'name' => 'secret.txt',
            'path' => 'uploads/'.$owner->id.'/root/secret.txt',
            'size' => 321,
            'mime' => 'text/plain',
            'user_id' => $owner->id,
            'folder_id' => null,
        ]);

        $this->actingAs($attacker)
            ->postJson('/files/bulk/delete', [
                'file_ids' => [$file->id],
                'folder_ids' => [],
            ])
            ->assertForbidden();
    }

}
