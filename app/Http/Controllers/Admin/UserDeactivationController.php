<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * A user's deactivation as a resource: store deactivates, destroy reactivates.
 */
class UserDeactivationController extends Controller
{
    public function store(User $user): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $user->deactivate();

        return back()->with('status', __('users.messages.deactivated', ['name' => $user->name]));
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('reactivate', $user);

        $user->reactivate();

        return back()->with('status', __('users.messages.reactivated', ['name' => $user->name]));
    }
}
