@extends('layouts.app')

@section('title', '在庫一覧')

@section('content')
    <h1>在庫一覧</h1>

    <div class="filters">
        @if ($lowOnly)
            <a href="{{ route('products.index') }}">すべて表示</a>
        @else
            <a href="{{ route('products.index', ['low' => 1]) }}">発注点以下だけ表示</a>
        @endif
    </div>

    <table>
        <thead>
        <tr>
            <th>SKU</th>
            <th>商品名</th>
            <th class="num">在庫</th>
            <th class="num">発注点</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($products as $product)
            <tr>
                <td><a href="{{ route('products.show', $product) }}">{{ $product->sku }}</a></td>
                <td>{{ $product->name }}</td>
                <td class="num">{{ $product->stock }}</td>
                <td class="num">{{ $product->reorder_point }}</td>
                <td>
                    @if ($product->isLowStock())
                        <span class="badge low">要発注</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5">該当する商品はありません</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $products->links() }}
@endsection
