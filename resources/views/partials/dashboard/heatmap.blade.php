@php($months = $heatmap['months'] ?? [])
@php($rows = $heatmap['rows'] ?? [])
@php($cells = $heatmap['cells'] ?? [])

<div class="heatmap-shell">
    <div class="heatmap-months">
        <span></span>
        @foreach ($months as $month)
            <span>{{ $month['label'] }}</span>
        @endforeach
    </div>

    <div class="heatmap-body">
        <div class="heatmap-rows">
            @foreach ($rows as $row)
                <span>{{ $row }}</span>
            @endforeach
        </div>

        <div class="heatmap-grid" aria-label="Mapa de atividade">
            @foreach ($cells as $cell)
                <button
                    type="button"
                    class="heat-cell heat-level-{{ $cell['level'] }}"
                    title="{{ $cell['date'] }} - {{ $cell['minutes'] }} min"
                    aria-label="{{ $cell['date'] }} - {{ $cell['minutes'] }} minutos estudados"
                ></button>
            @endforeach
        </div>
    </div>

    <div class="heatmap-legend">
        <span>Menos</span>
        <span class="contribution"></span>
        <span class="contribution heat-level-1"></span>
        <span class="contribution heat-level-2"></span>
        <span class="contribution heat-level-3"></span>
        <span class="contribution heat-level-4"></span>
        <span>Mais</span>
    </div>
</div>
