<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Gate System - PT Bio Farma</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0056A0;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #0056A0;
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            color: #0056A0;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #999;
            text-align: right;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Gate System - PT Bio Farma (Persero)</h1>
        <p>Dicetak pada: {{ date('d F Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Waktu Eksekusi</th>
                <th>Petugas</th>
                <th>Tipe</th>
                <th>Kendaraan / PIC</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $index => $log)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $log->user->name }}</td>
                <td>{{ $log->gateRequest->type ?? '-' }}</td>
                <td>
                    {{ $log->gateRequest->vehicle_number ?? '-' }}<br>
                    <small>{{ $log->gateRequest->company_name ?? '' }}</small>
                </td>
                <td>
                    {{ str_replace('_', ' ', $log->action) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generate by GateSystem Management Dashboard
    </div>
</body>
</html>
