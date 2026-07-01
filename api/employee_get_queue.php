<?php
header('Content-Type: application/json');
include '../config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid method']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON');
    }

    $counterId = (int)($data['counter_id'] ?? 0);
    if ($counterId <= 0) {
        echo json_encode(['success' => false, 'message' => 'counter_id is required']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Load counter + its service types
    $stmt = $conn->prepare("SELECT id, name, service_types, current_customer_id FROM counters WHERE id = ? LIMIT 1");
    $stmt->execute([$counterId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$counter) {
        echo json_encode(['success' => false, 'message' => 'Counter not found']);
        exit;
    }

    $serviceTypes = [];
    if (!empty($counter['service_types'])) {
        $serviceTypes = json_decode($counter['service_types'], true);
        if (!is_array($serviceTypes)) {
            $serviceTypes = [];
        }
    }

    // إذا لم يتم قراءة service_types أو لم تكن مطابقة، نلجأ للعرض على مستوى الديوان/الكونتر بدون فلترة service_type
    // هذا الهدف منه فقط حل مشكلة عدم ظهور قائمة الانتظار.
    $useServiceTypesFilter = !empty($serviceTypes);

    // Ensure waiting list uses counter's diwan/service context automatically (diwan_code stored on customers)



    // Current serving customer for this counter (if any)
    $currentCustomer = null;
    if (!empty($counter['current_customer_id'])) {
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$counter['current_customer_id']]);
        $currentCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Waiting queue: أظهر فقط العملاء المطابقين لخدمات هذا الديوان/الكونتر
    // (service_type عند customers يجب أن يكون ضمن counters.service_types)
    if (empty($serviceTypes)) {
        $waiting = [];
    } else {
        $placeholders2 = implode(',', array_fill(0, count($serviceTypes), '?'));
        $sql = "
            SELECT *
            FROM customers
            WHERE status = 'waiting'
              AND service_type IN ($placeholders2)
            ORDER BY 
              CAST(SUBSTRING(queue_number, 2) AS UNSIGNED) ASC,
              created_at ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($serviceTypes));
        $waiting = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // (تم تعطيل fallback شامل مؤقتًا)
    if (false && !empty($serviceTypes)) {
        $placeholders = implode(',', array_fill(0, count($serviceTypes), '?'));
        // NOTE:
        // في بعض نسخ/تهيئات المشروع قد لا يكون diwan_code/primary_diwan_code مضبوطًا.
        // لذلك نرشّح الانتظار حسب (service_type) المسموح للكونتر نفسه بدلًا من diwan_code.
        $placeholders2 = implode(',', array_fill(0, count($serviceTypes), '?'));
        $sql = "
            SELECT *
            FROM customers
            WHERE status = 'waiting'
              /* TEMP: omit date filter for debugging */
              /* AND DATE(created_at) = CURDATE() */
              AND service_type IN ($placeholders2)
            ORDER BY 
              CAST(SUBSTRING(queue_number, 2) AS UNSIGNED) ASC,
              created_at ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($serviceTypes));
        $waiting = $stmt->fetchAll(PDO::FETCH_ASSOC);

    }


    // Debug counters to identify why waiting is empty
    $debug = [];
    try {
        $stmtDbg = $conn->query("SELECT COUNT(*) AS c_waiting, COUNT(DISTINCT service_type) AS distinct_service_types, MIN(created_at) AS min_created_at, MAX(created_at) AS max_created_at FROM customers WHERE status='waiting'");
        $debug['global_waiting'] = $stmtDbg ? $stmtDbg->fetch(PDO::FETCH_ASSOC) : null;

        if (!empty($serviceTypes)) {
            $ph = implode(',', array_fill(0, count($serviceTypes), '?'));
            $stmtDbg2 = $conn->prepare("SELECT COUNT(*) AS c_waiting_for_service FROM customers WHERE status='waiting' AND service_type IN ($ph)");
            $stmtDbg2->execute(array_values($serviceTypes));
            $debug['waiting_for_service_types'] = $stmtDbg2->fetch(PDO::FETCH_ASSOC);
        } else {
            $debug['waiting_for_service_types'] = ['c_waiting_for_service' => 0];
        }

        $debug['counter_service_types_raw'] = $counter['service_types'] ?? null;
    } catch (Exception $e) {
        $debug['error'] = $e->getMessage();
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'counter' => [
                'id' => (int)$counter['id'],
                'name' => $counter['name'] ?? '',
                'service_types' => $serviceTypes,
            ],
            'current' => $currentCustomer,
            'waiting' => $waiting,
            'debug' => $debug
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

