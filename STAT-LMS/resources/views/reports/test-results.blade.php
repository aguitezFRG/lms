<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Test Results</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        .header h1 {
            margin: 0 0 5px 0;
            color: #111827;
        }
        .header p {
            margin: 0;
            color: #6b7280;
        }

        /* Summary Box */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .summary-table td {
            padding: 15px;
            border: 1px solid #e5e7eb;
            text-align: center;
            background-color: #f9fafb;
            width: 25%;
        }
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-gray { color: #4b5563; }

        /* Test Suites */
        .suite-header {
            background-color: #e5e7eb;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            border: 1px solid #d1d5db;
            border-bottom: none;
            margin-top: 20px;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .results-table th, .results-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }
        .results-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }

        /* Badges */
        .badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        .badge-passed { background-color: #16a34a; }
        .badge-failed { background-color: #dc2626; }
        .badge-error { background-color: #ea580c; }

        /* Error Output */
        .error-box {
            background-color: #fef2f2;
            border-left: 3px solid #dc2626;
            padding: 8px;
            margin-top: 5px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            color: #991b1b;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .test-name {
            font-weight: bold;
            color: #1f2937;
        }
        .test-class {
            font-size: 10px;
            color: #6b7280;
            display: block;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Application Test Results</h1>
        <p>Generated on {{ $date }}</p>
    </div>

    <!-- Overall Summary -->
    <table class="summary-table">
        <tr>
            <td>
                <span class="summary-value text-gray">{{ $total_tests }}</span>
                Total Tests
            </td>
            <td>
                <span class="summary-value text-green">{{ $total_tests - $failures - $errors }}</span>
                Passed
            </td>
            <td>
                <span class="summary-value text-red">{{ $failures + $errors }}</span>
                Failed / Errors
            </td>
            <td>
                <span class="summary-value text-gray">{{ number_format($time, 2) }}s</span>
                Duration
            </td>
        </tr>
    </table>

    <!-- Detailed Suite Results -->
    @foreach ($suites as $suite)
        @if (count($suite['cases']) > 0)
            <div class="suite-header">
                {{ $suite['name'] }}
                <span style="font-weight: normal; font-size: 12px; float: right;">
                    (Tests: {{ $suite['tests'] }} | Failures: {{ $suite['failures'] }})
                </span>
            </div>

            <table class="results-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 80%;">Test Case</th>
                        <th style="width: 10%;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($suite['cases'] as $case)
                        <tr>
                            <td style="text-align: center;">
                                <span class="badge badge-{{ $case['status'] }}">
                                    {{ $case['status'] }}
                                </span>
                            </td>
                            <td>
                                <span class="test-name">{{ preg_replace('/(?<!^)([A-Z])/', ' $1', $case['name']) }}</span>
                                <span class="test-class">{{ $case['class'] }}</span>

                                @if ($case['status'] !== 'passed' && !empty($case['message']))
                                    <div class="error-box">{{ $case['message'] }}</div>
                                @endif
                            </td>
                            <td>{{ number_format($case['time'], 3) }}s</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

</body>
</html>