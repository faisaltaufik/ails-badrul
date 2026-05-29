<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('admin can open user management page from dashboard menu', function () {
    $admin = User::factory()->create([
        'nama' => 'Admin AILS',
        'username' => 'admin-kelola-user',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $managedUser = User::factory()->create([
        'nama' => 'Dosen Pemrograman',
        'username' => 'dosen-kelola-user',
        'role' => 'dosen',
        'prodi' => 'Informatika',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Kelola User');

    $this->actingAs($admin)
        ->get(route('dashboard.users'))
        ->assertOk()
        ->assertSee('Kelola User')
        ->assertSee('Tambah User Baru')
        ->assertSee('class="user-management-table"', false)
        ->assertSee("Apakah yakin ingin menghapus data user {$managedUser->nama}?", false)
        ->assertSee($managedUser->nama)
        ->assertSee($managedUser->username);
});

test('non admin cannot access user management page or menu', function () {
    $user = User::factory()->create([
        'nama' => 'Dosen AILS',
        'username' => 'dosen-biasa',
        'password' => 'dosen',
        'role' => 'dosen',
        'prodi' => 'Pemrograman Visual',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Kelola User');

    $this->actingAs($user)
        ->get(route('dashboard.users'))
        ->assertForbidden();
});

test('admin can create update and delete users', function () {
    $admin = User::factory()->create([
        'nama' => 'Admin CRUD',
        'username' => 'admin-crud-user',
        'password' => 'admin',
        'role' => 'admin',
        'prodi' => 'Pemrograman Visual',
    ]);

    $managedUser = User::factory()->create([
        'nama' => 'Mahasiswa Lama',
        'username' => 'mahasiswa-lama',
        'password' => 'lama12345',
        'role' => 'mahasiswa',
        'prodi' => 'Sistem Informasi',
    ]);

    $this->actingAs($admin)
        ->post(route('dashboard.users.store'), [
            'nama' => 'Mahasiswa Baru',
            'username' => 'mahasiswa-baru',
            'password' => 'baru12345',
            'role' => 'mahasiswa',
            'prodi' => 'Informatika',
        ])
        ->assertRedirect(route('dashboard.users'))
        ->assertSessionHasNoErrors();

    $createdUser = User::where('username', 'mahasiswa-baru')->first();

    expect($createdUser)->not->toBeNull();
    expect(Hash::check('baru12345', $createdUser->password))->toBeTrue();

    $this->actingAs($admin)
        ->put(route('dashboard.users.update', $managedUser->id_user), [
            'nama' => 'Mahasiswa Diperbarui',
            'username' => 'mahasiswa-diperbarui',
            'password' => 'update12345',
            'role' => 'dosen',
            'prodi' => 'Teknik Komputer',
        ])
        ->assertRedirect(route('dashboard.users'))
        ->assertSessionHasNoErrors();

    $managedUser->refresh();

    expect($managedUser->nama)->toBe('Mahasiswa Diperbarui');
    expect($managedUser->username)->toBe('mahasiswa-diperbarui');
    expect($managedUser->role)->toBe('dosen');
    expect($managedUser->prodi)->toBe('Teknik Komputer');
    expect(Hash::check('update12345', $managedUser->password))->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('dashboard.users.destroy', $managedUser->id_user))
        ->assertRedirect(route('dashboard.users'));

    $this->assertDatabaseMissing('users', [
        'id_user' => $managedUser->id_user,
    ]);
});