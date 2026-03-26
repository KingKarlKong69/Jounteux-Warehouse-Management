<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * DashboardController — Renders the Enterprise Analytics Dashboard.
 *
 * Serves both admin and staff dashboard routes.
 * The Vue page adapts layout based on user role.
 * All chart data is fetched client-side via the Reports API.
 */
class DashboardController extends Controller
{
    /**
     * Render the analytics dashboard.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return Inertia::render('Admin/Dashboard/Index', [
            'userRole' => $user->role,
        ]);
    }
}
