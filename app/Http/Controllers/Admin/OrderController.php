<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{InvestmentOffer, Order};
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller {
    const FLOW = ['draft', 'confirmed', 'awaiting_financing', 'financing_secured', 'procurement', 'ready', 'delivered', 'financial_verification', 'settlement_pending', 'settled', 'cancelled', 'disputed'];
    // Order Manager may only move through operational states; settlement states are finance-controlled.
    const MANAGER_STATES = ['confirmed', 'awaiting_financing', 'procurement', 'ready', 'delivered', 'financial_verification', 'cancelled'];

    public function index() { return view('admin.orders', ['rows' => Order::with('offer')->latest()->paginate(20), 'offers' => InvestmentOffer::whereDoesntHave('investment')->get(), 'states' => self::MANAGER_STATES]); }

    public function store(Request $r) {
        $d = $r->validate(['code' => 'required|max:30|unique:orders', 'customer_ref' => 'nullable|max:100', 'supplier' => 'nullable|max:150', 'product_name' => 'required|max:200',
            'quantity' => 'required|integer|min:1', 'purchase_cost' => 'required|numeric|min:0', 'sale_price' => 'nullable|numeric|min:0', 'transport_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0', 'delivery_destination' => 'nullable|max:200', 'expected_delivery_date' => 'nullable|date', 'offer_id' => 'nullable|exists:investment_offers,id|unique:orders,offer_id']);
        $o = new Order($d); $o->created_by = $r->user()->id; $o->status = 'draft'; $o->offer_id = $d['offer_id'] ?? null; $o->save();
        Audit::log('order.created', $o);
        return back()->with('ok', 'Order created.');
    }

    public function status(Request $r, Order $order) {
        $to = $r->validate(['to' => ['required', Rule::in(self::MANAGER_STATES)]])['to'];
        if (in_array($order->status, ['settled', 'settlement_pending'], true)) return back()->withErrors(['action' => 'Locked by finance.']);
        if (in_array($to, ['procurement', 'ready', 'delivered'], true) && ! ($order->offer && $order->offer->status === 'funded') && $order->offer_id)
            return back()->withErrors(['action' => 'Financing not secured yet.']);
        $order->forceFill(['status' => $to, 'delivered_at' => $to === 'delivered' ? now() : $order->delivered_at])->save();
        Audit::log('order.status', $order, ['to' => $to]);
        return back()->with('ok', 'Updated.');
    }
}
