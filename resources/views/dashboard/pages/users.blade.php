<section class="user-management-grid">
    <article class="panel user-management-panel">
        <div class="panel-header user-management-card-header">
            <div>
                <h2 class="panel-title">Tambah User Baru</h2>
                <p class="panel-subtitle">Buat akun admin, dosen, atau mahasiswa langsung dari dashboard.</p>
            </div>
            <span class="user-management-count">{{ $managedUsers->count() }} akun</span>
        </div>

        <form class="project-form" method="POST" action="{{ route('dashboard.users.store') }}">
            @csrf

            <div class="field">
                <label for="create-user-nama">Nama Lengkap</label>
                <input id="create-user-nama" type="text" name="nama" value="{{ old('nama') }}" maxlength="100" required>
            </div>

            <div class="field">
                <label for="create-user-username">Username</label>
                <input id="create-user-username" type="text" name="username" value="{{ old('username') }}" maxlength="50" autocomplete="username" required>
            </div>

            <div class="field">
                <label for="create-user-password">Password</label>
                <input id="create-user-password" type="password" name="password" minlength="8" maxlength="100" autocomplete="new-password" required>
            </div>

            <div class="field">
                <label for="create-user-role">Role</label>
                <select id="create-user-role" name="role" required>
                    @foreach ($roleOptions as $roleOption)
                        <option value="{{ $roleOption }}" @selected(old('role', 'mahasiswa') === $roleOption)>{{ ucfirst($roleOption) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field span-2">
                <label for="create-user-prodi">Program Studi</label>
                <input id="create-user-prodi" type="text" name="prodi" value="{{ old('prodi') }}" maxlength="100" required>
            </div>

            <div class="field span-2 user-management-actions">
                <span class="user-management-password-note">Password minimal 8 karakter.</span>
                <button class="button-secondary" type="submit">Simpan User</button>
            </div>
        </form>
    </article>

    <article class="panel user-management-table-panel" aria-label="Daftar user">
        <div class="panel-header user-management-card-header">
            <div>
                <h2 class="panel-title">Data User</h2>
                <p class="panel-subtitle">Kelola data user langsung dari tabel.</p>
            </div>
        </div>

        <div class="user-management-table-wrap">
            <table class="user-management-table">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Username</th>
                        <th scope="col">Role</th>
                        <th scope="col">Program Studi</th>
                        <th scope="col">Password Baru</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($managedUsers as $managedUser)
                        <tr>
                            <td class="user-management-table-number">{{ $loop->iteration }}</td>
                            <td>
                                <input
                                    id="edit-user-nama-{{ $managedUser->id_user }}"
                                    type="text"
                                    name="nama"
                                    value="{{ $managedUser->nama }}"
                                    maxlength="100"
                                    form="update-user-{{ $managedUser->id_user }}"
                                    required
                                >
                            </td>
                            <td>
                                <input
                                    id="edit-user-username-{{ $managedUser->id_user }}"
                                    type="text"
                                    name="username"
                                    value="{{ $managedUser->username }}"
                                    maxlength="50"
                                    autocomplete="username"
                                    form="update-user-{{ $managedUser->id_user }}"
                                    required
                                >
                            </td>
                            <td>
                                <select id="edit-user-role-{{ $managedUser->id_user }}" name="role" form="update-user-{{ $managedUser->id_user }}" required>
                                    @foreach ($roleOptions as $roleOption)
                                        <option value="{{ $roleOption }}" @selected($managedUser->role === $roleOption)>{{ ucfirst($roleOption) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input
                                    id="edit-user-prodi-{{ $managedUser->id_user }}"
                                    type="text"
                                    name="prodi"
                                    value="{{ $managedUser->prodi }}"
                                    maxlength="100"
                                    form="update-user-{{ $managedUser->id_user }}"
                                    required
                                >
                            </td>
                            <td>
                                <input
                                    id="edit-user-password-{{ $managedUser->id_user }}"
                                    type="password"
                                    name="password"
                                    minlength="8"
                                    maxlength="100"
                                    autocomplete="new-password"
                                    placeholder="Opsional"
                                    form="update-user-{{ $managedUser->id_user }}"
                                >
                            </td>
                            <td class="user-management-table-actions">
                                <form id="update-user-{{ $managedUser->id_user }}" class="user-management-hidden-form" method="POST" action="{{ route('dashboard.users.update', $managedUser) }}">
                                    @csrf
                                    @method('PUT')
                                </form>

                                <div class="user-management-action-stack">
                                    <button class="button-secondary user-management-table-button" type="submit" form="update-user-{{ $managedUser->id_user }}">Simpan</button>
                                    <form class="user-management-action-form" method="POST" action="{{ route('dashboard.users.destroy', $managedUser) }}">
                                        @csrf
                                        @method('DELETE')

                                        <button class="button-outline user-management-table-button" type="button" data-delete-confirm-button data-confirm-message="Apakah yakin ingin menghapus data user {{ $managedUser->nama }}?" @disabled($managedUser->id_user === $user->id_user)>Hapus</button>
                                    </form>

                                    @if ($managedUser->id_user === $user->id_user)
                                        <span class="user-management-row-note">Akun aktif tidak bisa dihapus.</span>
                                    @else
                                        <span class="user-management-row-note">Kosongkan password jika tidak diubah.</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="user-management-table-empty" colspan="7">
                                Belum ada user terdaftar. Tambahkan akun pertama melalui formulir di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</section>