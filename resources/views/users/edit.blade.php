<x-app-layout>
    <div class="max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Edit User</h1>
        <form method="POST" action="{{ route('users.update', $user) }}" class="bg-white border rounded-xl p-4 space-y-4">
            @csrf
            @method('PUT')
            <input name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-lg border-slate-300" required>
            <input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full rounded-lg border-slate-300" required>
            <input name="password" type="password" placeholder="New password (optional)" class="w-full rounded-lg border-slate-300">
            <select name="role" class="w-full rounded-lg border-slate-300">
                <option value="user" @selected($user->role === 'user')>User</option>
                <option value="admin" @selected($user->role === 'admin')>Admin</option>
            </select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Update</button>
        </form>
    </div>
</x-app-layout>
