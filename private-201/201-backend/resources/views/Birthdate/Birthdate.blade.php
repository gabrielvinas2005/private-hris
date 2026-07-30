<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Birthday Summary Report</title>
    <style>
        @page { 
            margin: 12px 8px; 
            size: A4 portrait; 
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .main-container {
            width: 100%;
        }
        .columns-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .columns-table td {
            vertical-align: top;
            width: 50%;
            padding: 0 6px;
        }
        .month-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: avoid;
        }
        .month-header {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 9.5pt;
            text-align: center;
            text-transform: uppercase;
            padding: 3px;
            border: 1px solid #000;
        }
        .month-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 8pt;
            line-height: 1.2;
        }
        .name-cell {
            width: 60%;
        }
        .date-cell {
            width: 40%;
        }
    </style>
</head>
<body>
    <table class="columns-table">
        <tr>
            <!-- Left Column -->
            <td>
                @foreach($leftColumnMonths as $month => $employees)
                    <table class="month-table">
                        <tr>
                            <td colspan="2" class="month-header">{{ $month }}</td>
                        </tr>
                        @foreach($employees as $employee)
                            <tr>
                                <td class="name-cell">{{ $employee->name }}</td>
                                <td class="date-cell">{{ $employee->birthdate_formatted }}</td>
                            </tr>
                        @endforeach
                    </table>
                    @if(!$loop->last)
                        <div style="height: 4px;"></div>
                    @endif
                @endforeach
            </td>

            <!-- Right Column -->
            <td>
                @foreach($rightColumnMonths as $month => $employees)
                    <table class="month-table">
                        <tr>
                            <td colspan="2" class="month-header">{{ $month }}</td>
                        </tr>
                        @foreach($employees as $employee)
                            <tr>
                                <td class="name-cell">{{ $employee->name }}</td>
                                <td class="date-cell">{{ $employee->birthdate_formatted }}</td>
                            </tr>
                        @endforeach
                    </table>
                    @if(!$loop->last)
                        <div style="height: 4px;"></div>
                    @endif
                @endforeach
            </td>
        </tr>
    </table>
</body>
</html>
