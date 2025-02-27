<?php

namespace App\Services;

use App\Models\Group;

class BalanceCalculator
{
    public function calculateGroupBalances(Group $group)
    {
        $balances = [];
        $members = $group->members;
        
        // Calcular cuánto ha pagado y debe cada miembro
        $memberBalances = [];
        foreach ($members as $member) {
            $memberBalances[$member->id] = [
                'userId' => $member->id,
                'userName' => $member->name,
                'paid' => 0,
                'owes' => 0,
                'balance' => 0
            ];
        }
        
        // Calcular pagos y deudas
        foreach ($group->expenses as $expense) {
            $paidBy = $expense->paid_by;
            $amount = $expense->amount;
            $participants = $expense->participants;
            $participantCount = $participants->count();
            
            if ($participantCount > 0) {
                $sharePerPerson = $amount / $participantCount;
                
                // Registrar lo que pagó
                $memberBalances[$paidBy]['paid'] += $amount;
                
                // Registrar lo que debe cada participante
                foreach ($participants as $participant) {
                    $memberBalances[$participant->id]['owes'] += $sharePerPerson;
                }
            }
        }
        
        // Calcular balance final
        foreach ($memberBalances as &$balance) {
            $balance['balance'] = $balance['paid'] - $balance['owes'];
            $balance['amount'] = round($balance['balance'], 2);
        }
        
        return array_values($memberBalances);
    }
    
    public function calculateSettlements(array $balances)
    {
        $settlements = [];
        $debtors = [];
        $creditors = [];
        
        // Separar deudores y acreedores
        foreach ($balances as $balance) {
            if ($balance['balance'] < 0) {
                $debtors[] = [
                    'userId' => $balance['userId'],
                    'userName' => $balance['userName'],
                    'amount' => abs($balance['balance'])
                ];
            } elseif ($balance['balance'] > 0) {
                $creditors[] = [
                    'userId' => $balance['userId'],
                    'userName' => $balance['userName'],
                    'amount' => $balance['balance']
                ];
            }
        }
        
        // Ordenar de mayor a menor
        usort($debtors, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        usort($creditors, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        // Calcular pagos
        while (count($debtors) > 0 && count($creditors) > 0) {
            $debtor = $debtors[0];
            $creditor = $creditors[0];
            
            $amount = min($debtor['amount'], $creditor['amount']);
            
            if ($amount > 0) {
                $settlements[] = [
                    'from' => $debtor['userName'],
                    'to' => $creditor['userName'],
                    'amount' => round($amount, 2)
                ];
            }
            
            if ($debtor['amount'] > $creditor['amount']) {
                $debtors[0]['amount'] -= $creditor['amount'];
                array_shift($creditors);
            } elseif ($debtor['amount'] < $creditor['amount']) {
                $creditors[0]['amount'] -= $debtor['amount'];
                array_shift($debtors);
            } else {
                array_shift($debtors);
                array_shift($creditors);
            }
        }
        
        return $settlements;
    }
}
