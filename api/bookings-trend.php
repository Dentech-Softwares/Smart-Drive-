<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => 'Unauthorized',
    'labels' => [],
    'totalBookings' => [],
    'completedBookings' => [],
    'revenue' => [],
    'period' => ['start' => null, 'end' => null]
];

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode($response);
    exit();
}

try {
    // Resolve the requested period. Default to the last 6 months (current month inclusive).
    $start = isset($_GET['start_date']) && $_GET['start_date'] !== ''
        ? $_GET['start_date']
        : (new DateTime('first day of -5 months'))->format('Y-m-01');

    $end = isset($_GET['end_date']) && $_GET['end_date'] !== ''
        ? $_GET['end_date']
        : date('Y-m-d');

    // Build the ordered, gap-free list of month buckets for the range.
    $months = [];
    $cursor = new DateTime($start);
    $cursor->modify('first day of');
    $endCursor = new DateTime($end);
    while ($cursor <= $endCursor) {
        $key = $cursor->format('Y-m');
        $months[$key] = $cursor->format('M Y');
        $cursor->modify('+1 month');
    }

    // Aggregate real bookings per month (total, completed, revenue from completed).
    $data = [];
    foreach ($months as $key => $label) {
        $data[$key] = ['total' => 0, 'completed' => 0, 'revenue' => 0.0];
    }

    $sql = "SELECT
                DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'Completed' THEN total_amount ELSE 0 END) AS revenue
            FROM bookings
            WHERE created_at >= :start AND created_at <= :end
            GROUP BY month_key
            ORDER BY month_key";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start' => $start . ' 00:00:00',
        ':end'   => $end . ' 23:59:59'
    ]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $key = $row['month_key'];
        if (isset($data[$key])) {
            $data[$key] = [
                'total' => (int) $row['total'],
                'completed' => (int) $row['completed'],
                'revenue' => (float) $row['revenue']
            ];
        }
    }

    // Fallback to realistic dynamic dummy data when the period is sparse/empty
    // so the visualization always renders beautifully.
    $totalAll = array_sum(array_column($data, 'total'));
    if ($totalAll === 0) {
        // Plausible seasonal demand pattern (varies by month index).
        $pattern = [14, 22, 28, 36, 30, 42];
        $idx = 0;
        foreach ($months as $key => $label) {
            $total = $pattern[$idx % count($pattern)];
            $completed = (int) round($total * 0.70);
            $data[$key] = [
                'total' => $total,
                'completed' => $completed,
                'revenue' => round($completed * (750 + ($idx % 3) * 130), 2)
            ];
            $idx++;
        }
    }

    $response = [
        'success' => true,
        'message' => $totalAll === 0 ? 'Showing sample data; no bookings recorded for this period.' : 'Data loaded.',
        'currencySymbol' => getSetting('currency_symbol', 'KSh '),
        'labels' => array_values($months),
        'totalBookings' => array_map('intval', array_column($data, 'total')),
        'completedBookings' => array_map('intval', array_column($data, 'completed')),
        'revenue' => array_map('floatval', array_column($data, 'revenue')),
        'period' => ['start' => $start, 'end' => $end]
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error loading trend data',
        'currencySymbol' => getSetting('currency_symbol', 'KSh '),
        'labels' => [],
        'totalBookings' => [],
        'completedBookings' => [],
        'revenue' => [],
        'period' => ['start' => null, 'end' => null]
    ];
}

echo json_encode($response);
