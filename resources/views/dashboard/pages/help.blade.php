<section class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">Panduan Penggunaan AILS BADRUL</h2>
            <p class="panel-subtitle">Ringkasan cara kerja prototipe berdasarkan model PjBL BADRUL dan penggunaan AI sebagai pendamping belajar.</p>
        </div>
    </div>

    <div class="help-grid">
        @foreach ($helpCards as $helpCard)
            <article class="help-card">
                <strong>{{ $helpCard['title'] }}</strong>
                <p>{{ $helpCard['description'] }}</p>
            </article>
        @endforeach
    </div>
</section>