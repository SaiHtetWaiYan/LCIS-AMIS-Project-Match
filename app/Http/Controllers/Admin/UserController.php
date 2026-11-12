<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserSide;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();
        $side = UserSide::tryFrom($request->string('side')->toString());
        $status = $request->string('status')->toString();

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%")
            ))
            ->when($side, fn ($query) => $query->where('side', $side))
            ->when($status === 'active', fn ($query) => $query->whereNull('deactivated_at'))
            ->when($status === 'deactivated', fn ($query) => $query->whereNotNull('deactivated_at'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => ['search' => $search, 'side' => $side?->value, 'status' => $status],
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()->route('admin.users.index')
            ->with('status', __('users.messages.created', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $attributes = $request->safe()->only(['name', 'email', 'side']);

        if ($request->filled('password')) {
            $attributes['password'] = $request->validated('password');
        }

        $user->update($attributes);

        return redirect()->route('admin.users.index')
            ->with('status', __('users.messages.updated', ['name' => $user->name]));
    }
}
