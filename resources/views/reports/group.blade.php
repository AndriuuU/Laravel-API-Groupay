<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Grupo - {{ $group->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        h1 {
            color: #333;
            font-size: 24px;
        }
        h2 {
            color: #555;
            font-size: 18px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #777;
            text-align: center;
        }
        .positive {
            color: green;
        }
        .negative {
            color: red;
        }
    </style>
</head>
<body>
    <h1>Reporte del Grupo: {{ $group->name }}</h1>
    <p><strong>Descripción:</strong> {{ $group->description }}</p>
    <p><strong>Categoría:</strong> {{ $group->category }}</p>
    <p><strong>Fecha de creación:</strong> {{ $group->created_at->format('d/m/Y') }}</p>
    <p><strong>Total de gastos:</strong> {{ number_format($totalExpenses, 2) }} €</p>
    
    <h2>Balances</h2>
    <table>
        <thead>
            <tr>
                <th>Miembro</th>
                <th>Pagado</th>
                <th>Debe</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($balances as $balance)
            <tr>
                <td>{{ $balance['userName'] }}</td>
                <td>{{ number_format($balance['paid'], 2) }} €</td>
                <td>{{ number_format($balance['owes'], 2) }} €</td>
                <td class="{{ $balance['balance'] >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($balance['balance'], 2) }} €
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <h2>Sugerencias de Pago</h2>
    @if(count($settlements) > 0)
    <table>
        <thead>
            <tr>
                <th>De</th>
                <th>Para</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($settlements as $settlement)
            <tr>
                <td>{{ $settlement['from'] }}</td>
                <td>{{ $settlement['to'] }}</td>
                <td>{{ number_format($settlement['amount'], 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No hay pagos pendientes entre los miembros.</p>
    @endif
    
    <h2>Gastos</h2>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Categoría</th>
                <th>Pagado por</th>
                <th>Participantes</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
            <tr>
                <td>{{ $expense->date->format('d/m/Y') }}</td>
                <td>{{ $expense->description }}</td>
                <td>{{ $expense->category }}</td>
                <td>{{ $expense->paidBy->name }}</td>
                <td>{{ $expense->participants->pluck('name')->implode(', ') }}</td>
                <td>{{ number_format($expense->amount, 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Reporte generado el {{ $generatedAt->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
