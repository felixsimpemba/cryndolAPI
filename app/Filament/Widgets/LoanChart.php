<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class LoanChart extends ChartWidget
{
    protected static ?string $heading = 'Loans per Month';

    protected function getData(): array
    {
        $data = \App\Models\Loan::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Loans Created',
                    'data' => $data->pluck('count')->toArray(),
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
