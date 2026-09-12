<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $recentOrders = Order::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $upcomingRegistrations = EventRegistration::query()
            ->with('event')
            ->where('user_id', $user->id)
            ->whereHas('event', fn ($query) => $query->where('start_date', '>=', now()))
            ->latest()
            ->limit(5)
            ->get();

        return view('portal.dashboard', [
            'recentOrders' => $recentOrders,
            'upcomingRegistrations' => $upcomingRegistrations,
        ]);
    }
}
