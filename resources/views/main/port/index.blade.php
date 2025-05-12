@extends('layouts.app')

@section('title', 'Ports')

@section('content')
    <div class="container">
        <h1>Ports</h1>

        <x-andach-table>
            <x-andach-thead>
                <tr>
                    <x-andach-th>Name</x-andach-th>
                    <x-andach-th>URL</x-andach-th>
                    <x-andach-th>Slug</x-andach-th>
                </tr>
            </x-andach-thead>
            <x-andach-tbody>
                @foreach ($ports as $port)
                    <tr>
                        <x-andach-td>{{ $port->name }}</x-andach-td>
                        <x-andach-td>{{ $port->github_url }}</x-andach-td>
                        <x-andach-td>{{ $port->slug }}</x-andach-td>
                    </tr>
                @endforeach
            </x-andach-tbody>
        </x-andach-table>
    </div>
@endsection
