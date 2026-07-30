<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: Arial, sans-serif; }
        body { margin: 16px; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #4b2c64; padding: 6px; font-size: 12px; }
        th { background: #4b2c64; color: #fff; text-align: center; }
        .header-row th { background: #4b2c64; color: #fff; }
        .sub-header th { background: #6b3a86; color: #fff; }
        .number { width: 50px; text-align: center; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>SUMMARY OF RATINGS</h2>
    <table>
        <thead>
            <tr class="header-row">
                <th class="number">N o.</th>
                <th>Name of Employee</th>
                <th>Position Title</th>
                <th>Section</th>
                <th>Performance Rating<br>Average</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $row)
                <tr>
                    <td class="text-center">{{ $row['no'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['position'] }}</td>
                    <td>{{ $row['section'] }}</td>
                    <td class="text-center">{{ $row['performance'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

