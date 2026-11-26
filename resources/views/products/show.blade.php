@extends('layouts.app')

@section('title', $product->sku)

@section('content')
    <h1>{{ $product->sku }} {{ $product->name }}</h1>

    <p>
        在庫 <strong>{{ $product->stock }}</strong> ／ 発注点 {{ $product->reorder_point }}
        @if ($product->isLowStock())
            <span class="badge low">要発注</span>
        @endif
    </p>

    <h2>在庫を調整する</h2>
    <form method="POST" action="{{ route('products.adjust', $product) }}" class="filters">
        @csrf
        <input type="number" name="change" value="{{ old('change') }}" placeholder="増減（例：-2）" required>
        <input type="text" name="note" value="{{ old('note') }}" placeholder="理由（棚卸しの差異など）" maxlength="200" required>
        <button type="submit">調整する</button>
    </form>

    <h2>在庫の動き（新しい順）</h2>
    <table>
        <thead>
        <tr>
            <th>日時</th>
            <th>種別</th>
            <th class="num">増減</th>
            <th class="num">変更後</th>
            <th>受注</th>
            <th>メモ</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($movements as $movement)
            <tr>
                <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $movement->reasonLabel() }}</td>
                <td class="num">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td>
                <td class="num">{{ $movement->stock_after }}</td>
                <td>
                    @if ($movement->order)
                        <a href="{{ route('orders.show', $movement->order) }}">{{ $movement->order->order_number }}</a>
                    @endif
                </td>
                <td>{{ $movement->note }}</td>
            </tr>
        @empty
            <tr><td colspan="6">まだ動きはありません</td></tr>
        @endforelse
        </tbody>
    </table>

    <p><a href="{{ route('products.index') }}">在庫一覧へ戻る</a></p>
@endsection
