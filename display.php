<?php 
include 'config.php';

// Get display settings
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $settings = ['company_name' => 'خدمة العملاء', 'welcome_message' => 'مرحباً بكم في مركز الخدمة لدينا'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام خدمة المراجعين في عدلية الرقة</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#16581a">
    <link rel="apple-touch-icon" href="assets/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <style>
        /* Compact display overrides so everything fits on a single screen */
        :root {
            --compact-scale: 0.88; /* small global scale for very large screens */
        }

        body {
            background: linear-gradient(135deg, #16581a 0%, #18571b 100%);
            font-family: 'Arial', sans-serif;
            overflow-x: hidden;
            padding-bottom: 3rem; /* reserve space for fixed bottom bar (reduced) */
            /* apply a subtle scale to reduce overall sizing on big displays */
            transform-origin: top center;
            transform: scale(var(--compact-scale));
        }

        /* prevent label overlap in dynamic cards */
        #countersGrid > div { position: relative; }

        /* Reduced font sizes so all elements fit on one screen */
        .display-time { color: #a5d6a7; }
        .display-time-large { font-size: 1.8rem; line-height: 1.05; }
        .display-date-large { font-size: 1rem; line-height: 1.1; }

        .marquee {
            animation: marquee 18s linear infinite;
            white-space: nowrap;
            display: inline-block;
            padding-left: 50%;
            font-size: 0.95rem;
        }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }

        .flip-in { animation: flipIn 0.45s ease-in-out; }
        @keyframes flipIn {
            from { transform: rotateX(90deg) scale(0.85); opacity: 0; }
            to { transform: rotateX(0deg) scale(1); opacity: 1; }
        }

        .pulse-glow { animation: pulseGlow 2.5s infinite; }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 10px rgba(255, 255, 255, 0.25); }
            50% { box-shadow: 0 0 18px rgba(255, 255, 255, 0.4); }
        }

        .queue-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .ticker-item { animation: tickerScroll 22s linear infinite; font-size: 0.95rem; }
        @keyframes tickerScroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* compact card sizing */
        .compact-card { padding: 1rem !important; }
        .compact-title { font-size: 1.25rem !important; }
        .compact-value { font-size: 2rem !important; }

        /* logo and header smaller */
        .brand-logo { width: 64px; height: 64px; }
        #companyName { font-size: 1.9rem; }

        /* reduce gaps and margins globally for the main container */
        .container { max-width: 1200px; }
        .compact-gap { gap: 0.75rem !important; }

        /* ensure ticker/queue wraps nicely */
        #recentNumbers > div { min-width: 120px; }

        /* When the screen is small we revert the scale to 1 to avoid tiny text */
        @media (max-width: 1024px) {
            body { transform: none; --compact-scale: 1; padding-bottom: 4rem; }
            #companyName { font-size: 1.6rem; }
            .display-time-large { font-size: 1.6rem; }
            .display-date-large { font-size: 0.95rem; }
        }
    </style>
</head>
<body class="text-white min-h-screen">
    <?php include 'nav.php'; ?>
    <!-- Header with Marquee -->
    <div class="bg-black bg-opacity-30 py-2 mb-6">
        <div class="container mx-auto px-4">
            <div class="overflow-hidden">
                <div class="marquee text-lg font-semibold">
                    <i class="fas fa-info-circle ml-3"></i>
                    <?php echo htmlspecialchars($settings['welcome_message'] ?? 'مرحباً بكم في مركز الخدمة لدينا'); ?>
                    • يرجى تجهيز رقم الدور • شكراً لصبركم • نحن بخدمتكم•
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-4">
        <!-- Company Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center gap-3 mb-4">
                <span class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white bg-opacity-20 overflow-hidden">
                    <img src="assets/logo.png.webp" alt="شعار النسر الذهبي" class="object-cover brand-logo">
                </span>
                <!-- Reduced font size to fit one screen -->
                <h1 class="font-bold" id="companyName">
                    <?php echo htmlspecialchars($settings['company_name'] ?? 'نظام خدمة المراجعين في عدلية الرقة'); ?>
                </h1>
            </div>
            <div class="text-xl color:white font-semibold display-time" id="welcomeMessage">
                نظام خدمة المراجعين في عدلية الرقة
            </div>
        </div>

        <!-- Current Counters Section -->
        <div class="bg-white bg-opacity-20 rounded-3xl p-6 mb-6 text-center border-4 border-white border-opacity-20 pulse-glow compact-card">
            <h2 class="text-2xl font-bold mb-4 text-yellow-300">حالة الدواوين الآن</h2>
            <div id="countersGrid" class="grid grid-cols-1 md:grid-cols-3 gap-4 compact-gap"></div>
        </div>

        <!-- Status Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 compact-gap">
            <!-- Next in Line -->
            <div class="bg-white bg-opacity-20 rounded-2xl p-4 text-center backdrop-blur-sm compact-card">
                <h3 class="font-bold mb-3 text-green-300 compact-title">
                    <i class="fas fa-arrow-right ml-3"></i>التالي في الدور
                </h3>
                <div id="nextInLine" class="font-bold queue-number text-green-300 mb-2 compact-value">---</div>
                <div class="text-sm opacity-90" id="nextCustomerName">في انتظار العميل التالي</div>
            </div>

            <!-- Waiting Count -->
            <div class="bg-white bg-opacity-20 rounded-2xl p-4 text-center backdrop-blur-sm compact-card">
                <h3 class="font-bold mb-3 text-blue-300 compact-title">
                    <i class="fas fa-users ml-3"></i>في الانتظار
                </h3>
                <div id="waitingCount" class="font-bold text-blue-300 mb-2 compact-value">0</div>
                <div class="text-sm opacity-90">عملاء في الانتظار</div>
            </div>

            <!-- Average Wait Time -->
            <div class="bg-white bg-opacity-20 rounded-2xl p-4 text-center backdrop-blur-sm compact-card">
                <h3 class="font-bold mb-3 text-purple-300 compact-title">
                    <i class="fas fa-clock ml-3"></i>الوقت المقدر
                </h3>
                <div id="averageWait" class="font-bold text-purple-300 mb-2 compact-value">5 دقيقة</div>
                <div class="text-sm opacity-90">الوقت المقدر</div>
            </div>
        </div>

        <!-- Recently Called Section -->
        <div class="bg-opacity-15 rounded-2xl p-4 mb-6 text-center backdrop-blur-sm compact-card">
            <h3 class="text-xl font-bold mb-3 text-orange-300">
                <i class="fas fa-history ml-3"></i>المكالمات الأخيرة
            </h3>
            <div id="recentNumbers" class="flex justify-center space-x-3 flex-wrap gap-2">
                <div class="text-sm opacity-70">لا توجد مكالمات حديثة</div>
            </div>
        </div>

        <!-- Waiting Queue Ticker -->
        <div class="bg-black bg-opacity-40 rounded-xl p-3 mb-6">
            <div class="flex items-center mb-1">
                <i class="fas fa-list-ol text-xl mr-3 text-yellow-400"></i>
                <h4 class="text-lg font-bold text-yellow-400">قائمة الانتظار</h4>
            </div>
            <div class="overflow-hidden">
                <div id="waitingQueueTicker" class="ticker-item text-sm font-semibold">قائمة الانتظار فارغة</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 mb-2">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
                <div class="display-date-large font-semibold display-time" id="currentDate"></div>
                <div id="currentTime" class="display-time display-time-large font-mono font-bold"></div>
                <div class="text-base font-semibold">
                    <i class="fas fa-heart text-red-400 ml-2"></i>
                    شكراً لانتظاركم
                </div>
            </div>
        </div>

        <footer class="bg-gray-800 text-white py-4 mt-3">
            <div class="container mx-auto px-4 text-center">
                <p>&copy; 2026 نظام خدمة المراجعين في عدلية الرقة. دائرة المعلوماتية.</p>
            </div>
        </footer>

        <!-- Audio for notifications -->
        <audio id="notificationSound" preload="auto">
            <source src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" type="audio/mpeg">
        </audio>

        <script src="js/display.js"></script>


        <script>
            window.addEventListener('offline', () => {
                alert("تحذير: أنت تعمل الآن بدون اتصال بالإنترنت. البيانات لن يتم تحديثها.");
            });
        </script>
</body>
</html>
