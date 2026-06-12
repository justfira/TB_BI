<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px; }
        th { background-color: #efefef; }
    </style>
</head>
<body>
    <h2>Laporan Work Order</h2>
    <table>
        <thead>
            <tr>
                <th>WO ID</th>
                <th>SC ID</th>
                <th>Tanggal</th>
                <th>STO</th>
                <th>Teknisi</th>
                <th>Status</th>
                <th>Kendala</th>
                <th>Durasi Jam</th>
                <th>SLA %</th>
                <th>Workfail</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
