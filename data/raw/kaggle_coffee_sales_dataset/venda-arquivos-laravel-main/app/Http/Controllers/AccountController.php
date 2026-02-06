<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $orders = Order::query()
            ->where('customer_id', $user->id)
            ->orderByDesc('created_at')
            ->with('items.ad')
            ->limit(5)
            ->get();
        return view('account.dashboard', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    public function orders()
    {
        $user = Auth::user();
        $orders = Order::query()
            ->where('customer_id', $user->id)
            ->orderByDesc('created_at')
            ->with('items.ad')
            ->get();
        return view('account.orders', [
            'orders' => $orders,
        ]);
    }

    public function orderDetail(int $id)
    {
        $user = Auth::user();
        $order = Order::query()
            ->where('id', $id)
            ->where('customer_id', $user->id)
            ->with('items.ad')
            ->firstOrFail();
        return view('account.order-detail', [
            'order' => $order,
        ]);
    }

    public function receipt(int $id)
    {
        $user = Auth::user();
        $order = Order::query()->where('id', $id)->where('customer_id', $user->id)->firstOrFail();

        return view('account.receipt', [
            'order' => $order,
            'user' => $user,
        ]);
    }

    public function profile()
    {
        $user = Auth::user();
        return view('account.profile', ['user' => $user]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'cpf' => 'nullable|string|max:32',
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:32',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:80',
            'state' => 'nullable|string|max:16',
            'zip' => 'nullable|string|max:20',
        ]);

        $user = Auth::user();
        $user->update($data);

        return redirect()->route('account.profile')->with('status', 'Dados atualizados.');
    }
}
