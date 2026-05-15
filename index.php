<?php
session_start();
include 'db.php'; // Chú ý: Cần kết nối CSDL ở đây

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- LOGIC LÀM LẠI BÀI KIỂM TRA (RETAKE) GIỚI HẠN 2 LẦN/TUẦN ---
if (isset($_GET['retake'])) {
    // 1. Lấy thông tin làm bài của user
    $stmt = $conn->prepare("SELECT tests_this_week, last_test_time FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $test_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $tests_this_week = $test_info['tests_this_week'] ?? 0;
    $last_test_time = $test_info['last_test_time'];

    // 2. Nếu chưa từng làm lại hoặc đã qua 7 ngày -> Reset bộ đếm về 0
    if (!$last_test_time || strtotime($last_test_time) <= strtotime('-7 days')) {
        $tests_this_week = 0; 
        // Thời gian sẽ được set mới khi user thực sự bắt đầu lượt làm đầu tiên của tuần
    }

    // 3. Kiểm tra giới hạn (Tối đa 2 lần/tuần)
    if ($tests_this_week < 2) {
        $tests_this_week++; // Tăng số lượt đã sử dụng
        
        // Nếu đây là lượt làm lại đầu tiên trong chu kỳ 7 ngày, lưu lại mốc thời gian
        if ($tests_this_week == 1) {
            $last_test_time = date('Y-m-d H:i:s');
            $conn->query("UPDATE users SET has_taken_test = 0, tests_this_week = 1, last_test_time = '$last_test_time' WHERE id = $user_id");
        } else {
            // Lượt thứ 2, chỉ tăng biến đếm, giữ nguyên mốc thời gian bắt đầu
            $conn->query("UPDATE users SET has_taken_test = 0, tests_this_week = $tests_this_week WHERE id = $user_id");
        }
        
        header("Location: index.php");
        exit();
    } else {
        // 4. Nếu đã hết lượt, bật cảnh báo và đẩy về lại Profile
        echo "<script>
                alert('Bạn đã dùng hết lượt làm bài kiểm tra trong tuần này (Tối đa 2 lần/tuần để tránh quá tải dữ liệu). Vui lòng thử lại vào tuần sau nhé!'); 
                window.location.href='profile.php';
              </script>";
        exit();
    }
}

// Xử lý AJAX yêu cầu làm bài test (Gán session bảo mật)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'init_test') {
    $type = $_POST['test_type'] ?? 'general';
    $_SESSION['allow_test'] = true;
    $_SESSION['test_type'] = $type;
    echo json_encode(["status" => "success"]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Khám phá Từ vựng | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Be Vietnam Pro', sans-serif; background: var(--bg); margin: 0; color: var(--primary); overflow-x: hidden; }

        @keyframes fadeInDown { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes popIn { 0% { opacity: 0; transform: scale(0.95) translateY(10px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
        @keyframes pulseDot { 0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
        @keyframes slideUpToast { from { opacity: 0; transform: translate(-50%, 20px); } to { opacity: 1; transform: translate(-50%, 0); } }

        .main-content { padding: 80px 20px 40px 20px; max-width: 800px; margin: 0 auto; min-height: 100vh; box-sizing: border-box; animation: fadeInDown 0.8s var(--spring) forwards; }
        .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; margin-bottom: 20px; border: 1px solid var(--border); background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: all 0.3s ease; }
        .status-badge:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.05); }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
        .status-online { background: var(--accent); animation: pulseDot 2s infinite; }
        .status-offline { background: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); }

        .search-container { position: relative; margin-bottom: 30px; display: flex; flex-direction: column; gap: 12px; animation: fadeInDown 1s var(--spring) forwards; animation-delay: 0.1s; opacity: 0; }
        input#wordSearch { width: 100%; padding: 20px 25px; border-radius: 20px; border: 2px solid var(--border); font-size: 1.1rem; font-family: inherit; box-sizing: border-box; outline: none; transition: all 0.4s var(--spring); box-shadow: 0 10px 25px rgba(0,0,0,0.03); }
        input#wordSearch:focus { border-color: var(--accent); box-shadow: 0 15px 35px rgba(16, 185, 129, 0.15); transform: translateY(-2px); }
        .btn-search { background: var(--primary); color: white; border: none; padding: 18px; border-radius: 18px; font-weight: 800; cursor: pointer; transition: all 0.3s var(--spring); font-size: 1rem; width: 100%; position: relative; overflow: hidden; }
        .btn-search:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(30, 41, 59, 0.2); background: #0f172a; }
        .btn-search:active { transform: translateY(1px); }

        @media (min-width: 600px) { .search-container { flex-direction: row; gap: 0; } .btn-search { position: absolute; right: 8px; top: 8px; bottom: 8px; width: auto; padding: 0 25px; } input#wordSearch { padding-right: 120px; } }

        .result-card { background: white; padding: 30px; border-radius: 30px; border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.04); display: none; opacity: 0; animation: popIn 0.6s var(--spring) forwards; transition: all 0.3s ease; }
        .result-card:hover { box-shadow: 0 25px 60px rgba(0,0,0,0.08); }
        .word-header { display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px; }
        @media (min-width: 600px) { .word-header { flex-direction: row; justify-content: space-between; align-items: center; } }
        .word-main { font-size: 2.5rem; font-weight: 800; letter-spacing: -1.5px; margin: 0; line-height: 1.1; color: var(--primary); }
        .ipa { font-size: 1.1rem; color: var(--text-muted); font-weight: 500; display: block; margin-top: 4px; }
        .tag { background: #f1f5f9; padding: 6px 12px; border-radius: 10px; font-weight: 800; font-size: 0.75rem; display: inline-block; width: fit-content; color: var(--accent); transition: background 0.3s, color 0.3s; }
        .tag:hover { background: var(--accent); color: white; }

        .def-box { margin-bottom: 25px; line-height: 1.5; }
        .def-vi { font-size: 1.25rem; font-weight: 700; color: #1e40af; margin-bottom: 8px; }
        .def-en { font-size: 0.95rem; color: #475569; font-weight: 500; }
        .example-box { background: #f8fafc; padding: 20px; border-radius: 18px; border-left: 4px solid var(--accent); font-style: italic; color: #334155; margin-bottom: 25px; font-size: 0.95rem; line-height: 1.6; transition: all 0.3s ease; }
        .example-box:hover { background: #f1f5f9; transform: translateX(5px); }

        .pair-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 20px; }
        @media (min-width: 600px) { .pair-grid { grid-template-columns: 1fr 1fr; } }
        .pair-card { background: #f8fafc; border: 1px solid var(--border); border-radius: 16px; padding: 14px 16px; transition: all 0.3s var(--spring); }
        .pair-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.03); border-color: #cbd5e1; }
        .pair-title { font-weight: 800; font-size: 0.85rem; color: var(--accent); margin-bottom: 6px; }
        .pair-content { font-weight: 600; color: var(--primary); line-height: 1.4; font-size: 0.95rem; }

        .btn-save { width: 100%; padding: 18px; border-radius: 16px; border: none; background: var(--primary); color: white; font-weight: 800; font-size: 1rem; cursor: pointer; transition: all 0.3s var(--spring); margin-top: 10px; }
        .btn-save:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(30,41,59,0.25); }
        .btn-save:active:not(:disabled) { transform: translateY(1px); }

        #debuggerArea { display: none; background: #0f172a; color: #38bdf8; padding: 20px; border-radius: 18px; margin-top: 30px; font-family: 'Courier New', monospace; font-size: 0.75rem; line-height: 1.4; border: 2px solid #1e293b; overflow-x: auto; animation: popIn 0.4s ease; }
        #saveToast { position: fixed; bottom: 20px; left: 20px; right: 20px; background: white; padding: 12px 20px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: none; align-items: center; gap: 10px; font-weight: 700; z-index: 2000; border: 1px solid var(--border); font-size: 0.9rem; }
        @media (min-width: 600px) { #saveToast { width: fit-content; left: 50%; transform: translateX(-50%); } }

        h1.page-title { font-weight: 800; font-size: 1.8rem; letter-spacing: -0.5px; margin-bottom: 8px; margin-top: 0; }
        p.page-desc { color: var(--text-muted); font-weight: 500; margin-bottom: 30px; font-size: 0.95rem; }

        /* --- POPUP ONBOARDING MODAL --- */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(10px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; visibility: hidden; transition: 0.4s ease; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .onboarding-modal { background: white; width: 90%; max-width: 650px; border-radius: 30px; padding: 40px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); transform: scale(0.9) translateY(20px); transition: 0.4s var(--spring); position: relative; }
        .modal-overlay.active .onboarding-modal { transform: scale(1) translateY(0); }
        .modal-close { position: absolute; top: 20px; right: 25px; background: none; border: none; font-size: 1.5rem; color: var(--text-muted); cursor: pointer; transition: 0.2s; }
        .modal-close:hover { color: #ef4444; transform: rotate(90deg); }
        .onboarding-modal h2 { font-size: 1.8rem; font-weight: 800; margin-top: 0; color: var(--primary); letter-spacing: -0.5px; }
        .onboarding-modal p { color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin-bottom: 30px; }
        
        .path-cards { display: grid; grid-template-columns: 1fr; gap: 15px; }
        @media (min-width: 500px) { .path-cards { grid-template-columns: 1fr 1fr; } }
        
        .path-card { border: 2px solid var(--border); border-radius: 20px; padding: 25px 20px; text-align: center; cursor: pointer; transition: 0.3s var(--spring); background: #f8fafc; }
        .path-card:hover { border-color: var(--accent); background: white; transform: translateY(-5px); box-shadow: 0 15px 30px rgba(16,185,129,0.1); }
        .path-icon { font-size: 2.5rem; margin-bottom: 15px; display: block; }
        .path-card h3 { margin: 0 0 10px 0; font-weight: 800; color: var(--primary); }
        .path-card p { font-size: 0.85rem; margin: 0; color: #475569; line-height: 1.4; }
        .skip-btn { text-align: center; margin-top: 25px; }
        .skip-btn button { background: none; border: none; color: var(--text-muted); font-weight: 600; cursor: pointer; text-decoration: underline; font-family: inherit; font-size: 0.95rem; }
        .skip-btn button:hover { color: var(--primary); }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="main-content">
        <div class="status-badge" id="aiStatus">
            <div class="status-dot" id="statusDot"></div>
            <span id="statusText">AI Engine...</span>
        </div>

        <h1 class="page-title">Khám phá từ vựng</h1>
        <p class="page-desc">Nhập một từ tiếng Anh để AI phân tích chuyên sâu.</p>

        <div class="search-container">
            <input type="text" id="wordSearch" placeholder="Ví dụ: Resilient, Serendipity..." onkeypress="if(event.key === 'Enter') searchWord()">
            <button class="btn-search" onclick="searchWord()" id="searchBtn">Tìm kiếm</button>
        </div>

        <div id="resultCard" class="result-card">
            <div class="word-header">
                <div>
                    <h2 id="displayWord" class="word-main">Word</h2>
                    <span id="displayIpa" class="ipa">/ipa/</span>
                </div>
                <div id="displayLevel" class="tag">LEVEL</div>
            </div>

            <div class="def-box">
                <div id="displayVi" class="def-vi">Định nghĩa tiếng Việt</div>
                <div id="displayEn" class="def-en">English definition...</div>
            </div>

            <div id="displayEx" class="example-box">"Example sentence..."</div>

            <div class="pair-grid">
                <div class="pair-card">
                    <div class="pair-title">Synonyms</div>
                    <div id="displaySyn" class="pair-content">...</div>
                </div>
                <div class="pair-card">
                    <div class="pair-title">Antonyms</div>
                    <div id="displayAnt" class="pair-content">...</div>
                </div>
            </div>

            <button id="saveBtn" class="btn-save" onclick="saveToVault()">Lưu vào kho từ vựng +</button>
        </div>

        <div id="debuggerArea">
            <strong style="color: #ef4444; display: block; margin-bottom: 8px;">⚠️ DEBUGGER:</strong>
            <div id="debugContent"></div>
            <pre id="debugRaw" style="background:#1e293b; padding:12px; border-radius:10px; margin-top:10px; white-space: pre-wrap; color: #94a3b8;"></pre>
        </div>
    </div>

    <div id="saveToast">
        <div style="background: var(--accent); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">✓</div>
        <span>Đã lưu! +10 XP ⚡</span>
    </div>

    <!-- POPUP ONBOARDING (Hiển thị nếu has_taken_test = 0) -->
    <?php if ($has_taken_test == 0): ?>
    <div class="modal-overlay" id="onboardingModal">
        <div class="onboarding-modal">
            <button class="modal-close" onclick="closeOnboarding()">✕</button>
            <h2>Xin chào! 👋</h2>
            <p>Để tối ưu hóa hành trình học thuật, AI của chúng tôi cần đánh giá trình độ hiện tại của bạn. Bạn muốn tập trung vào lộ trình nào?</p>
            
            <div class="path-cards">
                <div class="path-card" onclick="startTest('general')">
                    <span class="path-icon">🎓</span>
                    <h3>Tiếng Anh Tổng Quát</h3>
                    <p>20 câu hỏi (Từ vựng, Ngữ pháp, Word Form). Kiểm tra toàn diện năng lực.</p>
                </div>
                <div class="path-card" onclick="startTest('ielts')">
                    <span class="path-icon">📝</span>
                    <h3>IELTS Focus</h3>
                    <p>Writing Task 2 trong 30 phút. Chấm điểm theo 4 tiêu chí chuẩn IELTS.</p>
                </div>
            </div>

            <div class="skip-btn">
                <button onclick="closeOnboarding()">Bỏ qua, tôi chỉ muốn tra từ điển lúc này.</button>
            </div>
        </div>
    </div>
    <script>
        setTimeout(() => { document.getElementById('onboardingModal').classList.add('active'); }, 500);
        function closeOnboarding() { document.getElementById('onboardingModal').classList.remove('active'); }
        function startTest(type) {
            // Gửi request AJAX gán Session bảo mật
            fetch('index.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=init_test&test_type=${type}` })
            .then(res => res.json())
            .then(data => { if(data.status === 'success') { window.location.href = 'take_a_test.php'; } });
        }
    </script>
    <?php endif; ?>

<script>
    const API_URL = '/api';
    let activeWordData = null;

    async function checkAI() {
        const dot = document.getElementById('statusDot');
        const text = document.getElementById('statusText');
        try {
            const res = await fetch(`${API_URL}/health`);
            if (res.ok) { dot.className = "status-dot status-online"; text.innerText = "Sẵn sàng"; }
        } catch (e) { dot.className = "status-dot status-offline"; text.innerText = "Mất kết nối"; }
    }
    checkAI();

    async function searchWord() {
        const word = document.getElementById('wordSearch').value.trim();
        const btn = document.getElementById('searchBtn');
        const card = document.getElementById('resultCard');
        const debugArea = document.getElementById('debuggerArea');
        if (!word) return;

        card.style.animation = 'none'; card.offsetHeight; 
        btn.disabled = true; btn.innerText = "Đang quét..."; card.style.display = "none"; debugArea.style.display = "none";

        try {
            const res = await fetch(`${API_URL}/get-vocab?word=${encodeURIComponent(word)}`);
            const rawText = await res.text();
            let data;
            try { data = JSON.parse(rawText); } catch (e) { throw { message: "Phản hồi AI không phải JSON.", raw: rawText }; }
            if (!res.ok) throw { message: data.error || "Lỗi API", raw: data };

            activeWordData = data; renderWord();
            card.style.animation = 'popIn 0.6s var(--spring) forwards';
        } catch (e) { showDebugger(e); } finally { btn.disabled = false; btn.innerText = "Tìm kiếm"; }
    }

    function renderWord() {
        document.getElementById('displayWord').innerText = activeWordData.word;
        document.getElementById('displayIpa').innerText = activeWordData.ipa;
        document.getElementById('displayLevel').innerText = `${activeWordData.level} • ${activeWordData.word_form}`;
        document.getElementById('displayVi').innerText = activeWordData.definition_vi;
        document.getElementById('displayEn').innerText = activeWordData.definition_en;
        document.getElementById('displayEx').innerText = `"${activeWordData.example_sentence}"`;
        document.getElementById('displaySyn').innerText = activeWordData.synonyms || "Không có";
        document.getElementById('displayAnt').innerText = activeWordData.antonyms || "Không có";
        document.getElementById('resultCard').style.display = "block";
        const saveBtn = document.getElementById('saveBtn'); saveBtn.innerText = "Lưu vào kho từ vựng +"; saveBtn.style.background = "var(--primary)"; saveBtn.disabled = false;
    }

    async function saveToVault() {
        if (!activeWordData) return;
        const saveBtn = document.getElementById('saveBtn'); const toast = document.getElementById('saveToast');
        saveBtn.disabled = true; saveBtn.innerText = "Đang lưu...";
        const formData = new FormData();
        Object.keys(activeWordData).forEach(key => formData.append(key, activeWordData[key]));
        formData.append('synonyms', activeWordData.synonyms || ''); formData.append('antonyms', activeWordData.antonyms || '');
        
        try {
            const res = await fetch('save.php', { method: 'POST', body: formData });
            const resultText = (await res.text()).trim();
            if (resultText === "Success") {
                saveBtn.innerText = "Đã lưu ✓"; saveBtn.style.background = "var(--accent)"; saveBtn.style.transform = "scale(0.98)"; setTimeout(()=> saveBtn.style.transform = "scale(1)", 200);
                toast.style.display = "flex"; toast.style.animation = "slideUpToast 0.5s var(--spring) forwards";
                setTimeout(() => { toast.style.opacity = "0"; setTimeout(()=> { toast.style.display = "none"; toast.style.opacity = "1"; }, 300); }, 3000);
            } else if (resultText === "Duplicate") { saveBtn.innerText = "Đã có trong kho"; saveBtn.style.background = "#64748b"; } 
            else { throw { message: "Lỗi lưu trữ PHP", raw: resultText }; }
        } catch (e) { showDebugger(e); saveBtn.disabled = false; saveBtn.innerText = "Lỗi!"; }
    }

    function showDebugger(err) {
        document.getElementById('debuggerArea').style.display = "block"; document.getElementById('debugContent').innerText = err.message;
        document.getElementById('debugRaw').innerText = typeof err.raw === 'object' ? JSON.stringify(err.raw, null, 2) : err.raw;
    }
</script>
</body>
</html>
