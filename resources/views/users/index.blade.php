<x-app-layout>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-semibold">User Management</h1>
            <a href="{{ route('users.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-white text-sm">Add User</a>
        </div>
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-700">{{ session('status') }}</div>
        @endif
        <div class="bg-white border rounded-xl overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Role</th>
                        <th class="px-4 py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-2">{{ $user->name }}</td>
                            <td class="px-4 py-2">{{ $user->email }}</td>
                            <td class="px-4 py-2 uppercase">{{ $user->role }}</td>
                            <td class="px-4 py-2 text-right">
                                <a class="text-indigo-600 mr-3" href="{{ route('users.edit', $user) }}">Edit</a>
                                <form class="inline" method="POST" action="{{ route('users.destroy', $user) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 border-t">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
