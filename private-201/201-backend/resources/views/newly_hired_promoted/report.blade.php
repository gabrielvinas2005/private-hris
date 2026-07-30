<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Newly Hired and Promoted</title>
    <style>
        @page {
            margin: 1in;
            size: A4 landscape;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }
        .title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: left;
        }
        th {
            text-align: center;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="title">{{ strtoupper($orgCompanyName) }}</div>
    <div class="title">LIST OF NEWLY HIRED/PROMOTED FOR CY {{ $cyStart }}-{{ $cyEnd }}</div>

    <table>
        <thead>
            <tr>
                <th>DEPARTMENT</th>
                <th>NAME</th>
                <th>POSITION</th>
                <th>DATE</th>
                <th>REMARKS</th>
                <th>PLANTILLA ITEM NO.</th>
                <th>EMPLOYMENT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $row)
                <tr>
                    <td>{{ $row->department ?? '' }}</td>
                    <td>{{ strtoupper($row->name ?? '') }}</td>
                    <td>{{ $row->position ?? '' }}</td>
                    <td class="text-center">{{ $row->date_formatted ?? '' }}</td>
                    <td class="text-center">{{ strtoupper($row->remarks ?? '') }}</td>
                    <td>{{ $row->plantilla ?? '' }}</td>
                    <td class="text-center">{{ strtoupper($row->employment_type ?? '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

