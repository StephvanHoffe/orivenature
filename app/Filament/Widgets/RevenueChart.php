<?php

namespace App\Filament\Widgets;

use App\Services\Analytics;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Omzet per dag';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 3];

    protected ?string $maxHeight = '260px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => '7 dagen', '30' => '30 dagen', '90' => '90 dagen'];
    }

    protected function getData(): array
    {
        $to = CarbonImmutable::today();
        $days = collect(Analytics::daily($to->subDays(((int) $this->filter ?: 30) - 1), $to));

        return [
            'datasets' => [[
                'label' => 'Omzet (€)',
                'data' => $days->map(fn ($d) => round($d['revenue'] / 100, 2))->values()->all(),
                'borderColor' => '#52572e',
                'backgroundColor' => 'rgba(82,87,46,.12)',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'labels' => $days->keys()->map(fn ($d) => CarbonImmutable::parse($d)->format('j-n'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
