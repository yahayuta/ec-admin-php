<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Closure;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom((string) $request->query('status', ''));
        $channel = array_key_exists((string) $request->query('channel'), Order::CHANNELS)
            ? (string) $request->query('channel')
            : null;
        $keyword = trim((string) $request->query('q', ''));

        $orders = Order::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($channel, fn ($query) => $query->where('channel', $channel))
            ->when($keyword !== '', fn ($query) => $query->where('order_number', 'like', '%'.$keyword.'%'))
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'channels' => Order::CHANNELS,
            'status' => $status,
            'channel' => $channel,
            'keyword' => $keyword,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load('items.product');

        return view('orders.show', ['order' => $order]);
    }

    public function allocate(Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        return $this->run($order, fn () => $workflow->allocate($order), '在庫を引き当てました');
    }

    public function ship(Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        return $this->run($order, fn () => $workflow->ship($order), '出荷済みにしました');
    }

    public function cancel(Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        return $this->run($order, fn () => $workflow->cancel($order), 'キャンセルしました');
    }

    /**
     * Business rule violations go back to the screen as a message, not a 500.
     */
    private function run(Order $order, Closure $action, string $done): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return redirect()->route('orders.show', $order)->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', $done);
    }
}
