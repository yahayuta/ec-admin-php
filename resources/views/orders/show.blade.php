@extends('layouts.app')

@section('title', '受注 '.$order->order_number)

@section('content')
    <h1>受注 {{ $order->order_number }}</h1>

    <p>
        <span class="badge">{{ $order->status->label() }}</span>
        {{ $order->channelLabel() }} ／ {{ $order->customer_name }} ／
        受注 {{ $order->ordered_at?->format('Y-m-d H:i') }}
        @if ($order->shipped_at)
            ／ 出荷 {{ $order->shipped_at->format('Y-m-d H:i') }}
        @endif
    </p>

    <div class="actions">
        @foreach ($order->status->allowedNext() as $next)
            @php
                [$route, $label] = match ($next) {
                    \App\Enums\OrderStatus::Allocated => ['orders.allocate', '在庫を引き当てる'],
                    \App\Enums\OrderStatus::Shipped => ['orders.ship', '出荷済みにする'],
                    \App\Enums\OrderStatus::Cancelled => ['orders.cancel', 'キャンセルする'],
                };
            @endphp
            <form method="POST" action="{{ route($route, $order) }}" class="inline"
                  @if ($next === \App\Enums\OrderStatus::Cancelled) onsubmit="return confirm('キャンセルしますか？')" @endif>
                @csrf
                <button type="submit">{{ $label }}</button>
            </form>
        @endforeach
    </div>

    <table>
        <thead>
        <tr>
            <th>SKU</th>
            <th>商品名</th>
            <th class="num">数量</th>
            <th class="num">単価</th>
            <th class="num">小計</th>
            <th class="num">現在の在庫</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td><a href="{{ route('products.show', $item->product) }}">{{ $item->product->sku }}</a></td>
                <td>{{ $item->product->name }}</td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ number_format($item->unit_price) }}円</td>
                <td class="num">{{ number_format($item->subtotal()) }}円</td>
                <td class="num">{{ $item->product->stock }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <th colspan="4">合計</th>
            <th class="num">{{ number_format($order->total_amount) }}円</th>
            <th></th>
        </tr>
        </tfoot>
    </table>

    <p><a href="{{ route('orders.index') }}">受注一覧へ戻る</a></p>
@endsection
