<x-app-layout>
    <div class="max-w-xl">
        <h1 class="text-xl font-semibold mb-4">Create User</h1>
        <form method="POST" action="{{ route('users.store') }}" class="bg-white border rounded-xl p-4 space-y-4">
            @csrf
            <input name="name" placeholder="Name" class="w-full rounded-lg border-slate-300" required>
            <input name="email" type="email" placeholder="Email" class="w-full rounded-lg border-slate-300" required>
            <input name="password" type="password" placeholder="Password" class="w-full rounded-lg border-slate-300" required>
            <select name="role" class="w-full rounded-lg border-slate-300">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Save</button>
        </form>
    </div>
</x-app-layout>
