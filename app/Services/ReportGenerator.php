<?php

namespace App\Services;

use App\Models\Group;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportGenerator
{
    public function generatePdfReport(Group $group)
    {
        $expenses = $group->expenses()->with(['paidBy', 'participants'])->orderBy('date', 'desc')->get();
        
        $balanceCalculator = new BalanceCalculator();
        $balances = $balanceCalculator->calculateGroupBalances($group);
        $settlements = $balanceCalculator->calculateSettlements($balances);
        
        $data = [
            'group' => $group,
            'expenses' => $expenses,
            'balances' => $balances,
            'settlements' => $settlements,
            'totalExpenses' => $expenses->sum('amount'),
            'generatedAt' => now()
        ];
        
        $pdf = PDF::loadView('reports.group', $data);
        return $pdf->download('group-report-' . $group->id . '.pdf');
    }
    
    public function generateCsvReport(Group $group)
    {
        $expenses = $group->expenses()->with(['paidBy', 'participants'])->orderBy('date', 'desc')->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="group-expenses-' . $group->id . '.csv"',
        ];
        
        $callback = function() use ($expenses) {
            $file = fopen('php://output', 'w');
            
            // Encabezados
            fputcsv($file, ['ID', 'Descripción', 'Cantidad', 'Fecha', 'Categoría', 'Pagado por', 'Participantes']);
            
            // Datos
            foreach ($expenses as $expense) {
                $participants = $expense->participants->pluck('name')->implode(', ');
                
                fputcsv($file, [
                    $expense->id,
                    $expense->description,
                    $expense->amount,
                    $expense->date->format('Y-m-d'),
                    $expense->category,
                    $expense->paidBy->name,
                    $participants
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
