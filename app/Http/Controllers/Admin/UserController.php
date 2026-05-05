<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $query = User::query()
            ->with('customerProfile')
            ->where('role', 'customer')
            ->orderBy('name', 'asc');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('customerProfile', function ($profileQuery) use ($search) {
                        $profileQuery->where('phone_number', 'like', '%' . $search . '%')
                            ->orWhere('gender', 'like', '%' . $search . '%')
                            ->orWhere('status', 'like', '%' . $search . '%');
                    });
            });
        }

        $users = $query->paginate($perPage)->withQueryString();

        return view('admin.users.index', compact(
            'users',
            'search',
            'perPage'
        ));
    }

    public function show(User $user)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        if ($user->role !== 'customer') {
            abort(404);
        }

        $user->load('customerProfile');

        return view('admin.users.show', [
            'customer' => $user,
        ]);
    }

    public function toggleStatus(User $user)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        if ($user->role !== 'customer') {
            abort(404);
        }

        $profile = CustomerProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'phone_number' => null,
                'gender' => null,
                'date_of_birth' => null,
                'avatar' => null,
                'address_line_1' => null,
                'address_line_2' => null,
                'city' => null,
                'state' => null,
                'country' => null,
                'status' => 'active',
            ]
        );

        $profile->update([
            'status' => $profile->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Status user berhasil diubah.');
    }

    public function destroy(User $user)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        if ($user->role !== 'customer') {
            abort(404);
        }

        $user->customerProfile()?->delete();
        $user->delete();

        return back()->with('success', 'User berhasil dihapus.');
    }
}