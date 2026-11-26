@extends('layouts.app')

@section('title', '受注一覧')

@section('content')
    <h1>受注一覧</h1>

    <form method="GET" action="{{ route('orders.index') }}" class="filters">
        <select name="status">
            <option value="">すべてのステータス</option>
            @foreach ($statuses as $option)
                <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
            @endforeach
        </select>
        <select name="channel">
            <option value="">すべての販路</option>
            @foreach ($channels as $value => $label)
                <option value="{{ $value }}" @selected($channel === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="search" name="q" value="{{ $keyword }}" placeholder="受注番号">
        <button type="submit">絞り込む</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>受注番号</th>
            <th>販路</th>
            <th>ステータス</th>
            <th>受注日時</th>
            <th class="num">合計</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                <td>{{ $order->channelLabel() }}</td>
                <td><span class="badge">{{ $order->status->label() }}</span></td>
                <td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
                <td class="num">{{ number_format($order->total_amount) }}円</td>
            </tr>
        @empty
            <tr><td colspan="5">該当する受注はありません</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $orders->links() }}
@endsection
