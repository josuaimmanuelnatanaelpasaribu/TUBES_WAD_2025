<h3>Hasil untuk: {{ $keyword }}</h3>

@foreach ($results as $result)
    <div>
        <strong>{{ $result['surah'] }}</strong> - Ayat {{ $result['ayat'] }}
    </div>
@endforeach
