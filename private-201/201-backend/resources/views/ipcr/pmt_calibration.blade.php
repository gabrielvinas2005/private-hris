<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: Arial, sans-serif; }
        body { margin: 16px; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #4b2c64; padding: 6px; font-size: 11px; }
        th { background: #4b2c64; color: #fff; text-align: center; }
        .header-row th { background: #4b2c64; color: #fff; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>PMT Calibration Results {{ $header->year ?? '' }}</h2>
    <table>
        <thead>
            <tr class="header-row">
                <th>Name of employee</th>
                <th>Section</th>
                <th>Self-Rating</th>
                <th>Supervisor-Validated Self-Rating</th>
                <th>HRD-Reviewed Rating</th>
                <th>PMT Calibrated Rating</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['section'] }}</td>
                    <td class="text-center">{{ $row['self'] }}</td>
                    <td class="text-center">{{ $row['supervisor'] }}</td>
                    <td class="text-center">{{ $row['hr'] }}</td>
                    <td class="text-center">{{ $row['pmt'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

