@php($months = $heatmap['months'] ?? [])
@php($rows = $heatmap['rows'] ?? [])
@php($weeks = $heatmap['weeks'] ?? [])
@php($weekCount = max(1, count($weeks)))

<div class="heatmap-shell">
    <div class="heatmap-scroll">
        <div class="heatmap-calendar" style="--heatmap-weeks: {{ $weekCount }}">
            <div class="heatmap-months" aria-hidden="true">
                <span></span>
                @foreach ($months as $month)
                    <span title="{{ $month['date'] }}">{{ $month['label'] }}</span>
                @endforeach
            </div>

            <div class="heatmap-body">
                <div class="heatmap-rows" aria-hidden="true">
                    @foreach ($rows as $row)
                        <span>{{ $row }}</span>
                    @endforeach
                </div>

                <div class="heatmap-grid" aria-label="Mapa de atividade dos últimos 365 dias">
                    @foreach ($weeks as $week)
                        <div class="heatmap-week">
                            @foreach ($week['days'] as $day)
                                @if ($day['is_empty'])
                                    <span class="heat-cell heat-cell-empty" aria-hidden="true"></span>
                                @else
                                    <button
                                        type="button"
                                        class="heat-cell heat-level-{{ $day['level'] }}"
                                        title="{{ $day['label'] }}"
                                        aria-label="{{ $day['label'] }}"
                                    ></button>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
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
