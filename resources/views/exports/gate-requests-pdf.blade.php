<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan General Affairs (GA)</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>Laporan Riwayat Validasi General Affairs</h2>
    <p>Tanggal Cetak: {{ \Carbon\Carbon::now()->format('d M Y H:i') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Update</th>
                <th>Perusahaan</th>
                <th>No. Kendaraan</th>
                <th>Supir</th>
                <th>Status</th>
                <th>Tujuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $index => $request)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $request->updated_at->format('d M Y H:i') }}</td>
                    <td>{{ $request->company_name }}</td>
                    <td>{{ $request->vehicle_number }}</td>
                    <td>{{ $request->driver_name }}</td>
                    <td>{{ str_replace('_', ' ', $request->status) }}</td>
                    <td>{{ $request->warehouse_type ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
