<div class="contribution-card">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Histórico</p>
            <h2>Contribuições</h2>
        </div>
        <p class="muted">Últimos 42 dias de estudo</p>
    </div>

    <div class="contribution-grid" aria-label="Mapa de contribuições">
        @foreach ($contributions as $day)
            <div
                class="contribution contribution-level-{{ $day['level'] }}"
                title="{{ $day['date'] }} · {{ $day['minutes'] }} min"
            ></div>
        @endforeach
    </div>

    <div class="legend">
        <span>Menos</span>
        <span class="contribution contribution-level-0"></span>
        <span class="contribution contribution-level-1"></span>
        <span class="contribution contribution-level-2"></span>
        <span class="contribution contribution-level-3"></span>
        <span class="contribution contribution-level-4"></span>
        <span>Mais</span>
    </div>
</div>
