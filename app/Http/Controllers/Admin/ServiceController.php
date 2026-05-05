<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $search = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = Service::query()
            ->leftJoin('users as providers', 'providers.id', '=', 'services.provider_id')
            ->leftJoin('provider_profiles as provider_profiles', 'provider_profiles.user_id', '=', 'services.provider_id')
            ->select([
                'services.*',
                DB::raw('providers.name as provider_name'),
                DB::raw('providers.email as provider_email'),
                DB::raw('provider_profiles.document_status as provider_document_status'),
            ])
            ->orderByDesc('services.created_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('services.title', 'like', '%' . $search . '%')
                    ->orWhere('services.slug', 'like', '%' . $search . '%')
                    ->orWhere('services.category', 'like', '%' . $search . '%')
                    ->orWhere('services.sub_category', 'like', '%' . $search . '%')
                    ->orWhere('services.code', 'like', '%' . $search . '%')
                    ->orWhere('services.status', 'like', '%' . $search . '%')
                    ->orWhere('providers.name', 'like', '%' . $search . '%')
                    ->orWhere('providers.email', 'like', '%' . $search . '%')
                    ->orWhere('provider_profiles.document_status', 'like', '%' . $search . '%');
            });
        }

        $services = $query->paginate($perPage)->withQueryString();

        return view('admin.services.index', compact(
            'services',
            'search',
            'perPage'
        ));
    }

    public function toggleStatus(Service $service)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $service->update([
            'status' => $service->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Status service berhasil diubah.');
    }
}