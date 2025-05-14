@extends('layouts.app')

@section('title', 'Ports')

@section('content')
    <div class="container">
        <h1>Wad - {{ $wad->name }}</h1>

        <div class="grid grid-cols-2">
            <div>Filename</div>
            <div>{{ $wad->filename }}</div>

            <div>Download Date</div>
            <div>{{ $wad->downloaded_at }}</div>

            <div>Extract Date</div>
            <div>
                @if ($wad->extracted_at)
                    {{ $wad->extracted_at }}
                @else
                    <a href="{{ route('wad.download-and-extract', $wad->id) }}">DOWNLOAD AND EXTRACT NOW</a>
                @endif
            </div>
        </div>
    </div>
@endsection
