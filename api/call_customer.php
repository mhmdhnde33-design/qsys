<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'الطريقة غير مسموحة']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('تنسيق JSON غير صالح');
    }
    
    $customerId = $data['customer_id'] ?? 0;

    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرّف العميل غير صالح']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get the customer
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'العميل غير موجود']);
        exit;
    }

    if ($customer['status'] === 'serving') {
        echo json_encode(['success' => false, 'message' => 'العميل قيد الخدمة بالفعل']);
        exit;
    }

    // Find a free online counter that can serve this service type
    // (الديوان = service_type حسب طلبك)
    $serviceType = $customer['service_type'] ?? '';
    if (empty($serviceType)) {
        echo json_encode(['success' => false, 'message' => 'نوع خدمة العميل غير صالح']);
        exit;
    }

    // الربط المطلوب: customers.service_type == counters.primary_diwan_code
    $stmt = $conn->prepare("SELECT id, name, primary_diwan_code FROM counters
                            WHERE is_online = 1
                              AND current_customer_id IS NULL
                            ORDER BY id ASC");
    $stmt->execute();
    $availableCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matchedCounter = null;
    $serviceTypeNormalized = is_string($serviceType) ? trim($serviceType) : $serviceType;

    foreach ($availableCounters as $counter) {
        if (!empty($counter['primary_diwan_code']) && (string)$counter['primary_diwan_code'] === (string)$serviceTypeNormalized) {
            $matchedCounter = $counter;
            break;
        }
    }

    // Fallback: إذا لم يوجد تطابق، اختر أول كونتر متاح (حتى لا يتوقف زر الاستدعاء)
    if (!$matchedCounter) {
        $matchedCounter = $availableCounters[0] ?? null;
    }

    if (!$matchedCounter) {
        echo json_encode(['success' => false, 'message' => 'لا يوجد كاونتر متاح حالياً']);
        exit;
    }

    $conn->beginTransaction();

    // Customer must be waiting (for safety)
    $stmt = $conn->prepare("UPDATE customers SET status = 'serving', called_at = NOW() WHERE id = ? AND status = 'waiting'");
    $stmt->execute([$customerId]);

    if ($stmt->rowCount() <= 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'لا يمكن استدعاء العميل (قد يكون غير في الانتظار أو قيد الخدمة)']);
        exit;
    }

    // Assign to counter with extra safety to prevent race conditions
    $stmt = $conn->prepare(
        "UPDATE counters 
         SET current_customer_id = ? 
         WHERE id = ? 
           AND current_customer_id IS NULL 
           AND is_online = 1"
    );
    $stmt->execute([$customerId, $matchedCounter['id']]);

    if ($stmt->rowCount() <= 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'فشل التعيين (الكونتر لم يعد متاحاً)']);
        exit;
    }

    $conn->commit();


    echo json_encode([
        'success' => true,
        'message' => 'تم استدعاء العميل بنجاح',
        'counter' => $matchedCounter['name'],

        'customer' => [
            'id' => (int)$customer['id'],
            'name' => $customer['name'] ?? '',
            'queue_number' => $customer['queue_number'] ?? '',
            'service_type' => $customer['service_type'] ?? ''
        ],
        'service_type_label' => $customer['service_type'] ?? ''
    ]);
    
} catch (Exception $e) {

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>