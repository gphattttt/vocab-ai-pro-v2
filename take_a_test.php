<?php
session_start();
include 'db.php'; // Gọi kết nối database

// 1. BẢO MẬT: Chống truy cập trực tiếp
if (!isset($_SESSION['user_id']) || !isset($_SESSION['allow_test']) || $_SESSION['allow_test'] !== true) {
    header("Location: index.php");
    exit();
}

$test_type = $_SESSION['test_type'] ?? 'general';

// 2. PHP PROXY: API Sinh đề
if (isset($_GET['action']) && $_GET['action'] == 'generate') {
    header('Content-Type: application/json');
    $ch = curl_init('http://127.0.0.1:5001/api/generate-test');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['type' => $test_type]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
    
    $result = curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo json_encode(["error" => "Lỗi kết nối tới Python Server: " . curl_error($ch)]);
    } else if (empty($result)) {
        echo json_encode(["error" => "Python Server trả về kết quả rỗng. Kiểm tra lại PM2 logs."]);
    } else {
        echo $result;
    }
    curl_close($ch);
    exit();
}

// 3. PHP PROXY: API Chấm điểm & Lưu Database
if (isset($_GET['action']) && $_GET['action'] == 'evaluate') {
    header('Content-Type: application/json');
    $input_data = file_get_contents('php://input'); // Nhận payload JSON từ Javascript

    // Gửi sang Python
    $ch = curl_init('http://127.0.0.1:5001/api/evaluate-test');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input_data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90); // Chấm điểm bài Writing có thể hơi lâu
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(["status" => "error", "message" => "Lỗi kết nối: " . curl_error($ch)]);
        exit();
    }
    curl_close($ch);
    
    $data = json_decode($result, true);

    // Nếu AI trả về hợp lệ, tiến hành lưu Database
    if (isset($data['score']) && isset($data['level'])) {
        $user_id = $_SESSION['user_id'];
        $level = $data['level'];
        $path = $test_type;
        
        // Tạo chuỗi HTML đẹp mắt để lưu thẳng vào DB (cho nhanh khi hiển thị ở Profile)
        $roadmap_html = '<div class="ai-feedback"><h4>Phân tích từ AI (Điểm: '.$data['score'].')</h4><p>' . htmlspecialchars($data['feedback']) . '</p></div>';
        $roadmap_html .= '<div class="ai-roadmap"><h4>Lộ trình khuyến nghị:</h4>' . $data['roadmap'] . '</div>';

        // Cập nhật CSDL
        $stmt = $conn->prepare("UPDATE users SET has_taken_test = 1, study_path = ?, current_level = ?, roadmap_text = ? WHERE id = ?");
        $stmt->bind_param("sssi", $path, $level, $roadmap_html, $user_id);
        if($stmt->execute()){
            $_SESSION['allow_test'] = false; // Tước quyền truy cập phòng thi
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Lỗi lưu Database"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "AI trả về định dạng không đúng.", "raw" => $result]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Test | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #1e293b; --accent: #10b981; --danger: #ef4444; --bg: #f1f5f9; --border: #e2e8f0; --text-muted: #64748b; }
        body { font-family: 'Be Vietnam Pro', sans-serif; background: var(--bg); margin: 0; color: var(--primary); }
        .exam-header { background: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border); position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
        .exam-title { font-weight: 800; font-size: 1.2rem; color: var(--primary); }
        .timer-box { background: #fef2f2; color: var(--danger); padding: 8px 16px; border-radius: 12px; font-weight: 800; font-size: 1.2rem; border: 2px solid #fee2e2; display: none; }
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        #loadingScreen, #evaluatingScreen { text-align: center; padding: 100px 20px; }
        .spinner { width: 50px; height: 50px; border: 5px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px auto; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .q-card { background: white; padding: 30px; border-radius: 20px; border: 1px solid var(--border); margin-bottom: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.02); }
        .q-number { font-size: 0.9rem; font-weight: 800; color: var(--accent); text-transform: uppercase; margin-bottom: 10px; }
        .q-text { font-size: 1.15rem; font-weight: 600; line-height: 1.6; margin-bottom: 20px; }
        .options-list { display: flex; flex-direction: column; gap: 10px; }
        .opt-label { display: flex; align-items: center; padding: 15px 20px; border: 2px solid var(--border); border-radius: 14px; cursor: pointer; transition: 0.2s; font-weight: 500; }
        .opt-label:hover { background: #f8fafc; border-color: #cbd5e1; }
        .opt-label input { margin-right: 15px; transform: scale(1.3); accent-color: var(--accent); }
        
        /* Chỉnh sửa lại form cho điện thoại gọn hơn */
        .wf-root { display: inline-block; background: #e2e8f0; color: var(--primary); font-weight: 800; padding: 4px 12px; border-radius: 8px; margin-bottom: 10px; font-size: 0.95rem; }
        .wf-input { width: 100%; padding: 15px 20px; border: 2px solid var(--border); border-radius: 14px; font-size: 1.1rem; font-family: inherit; outline: none; transition: 0.3s; box-sizing: border-box; }
        .wf-input:focus { border-color: var(--accent); background: #f0fdf4; }

        .ielts-topic { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 15px; font-size: 1.1rem; line-height: 1.6; margin-bottom: 25px; font-weight: 600; }
        .ielts-textarea { width: 100%; height: 400px; padding: 20px; border-radius: 15px; border: 2px solid var(--border); font-size: 1.1rem; font-family: inherit; resize: vertical; box-sizing: border-box; outline: none; transition: 0.3s; line-height: 1.6; }
        .ielts-textarea:focus { border-color: var(--accent); }
        .word-count { text-align: right; color: var(--text-muted); font-weight: 600; margin-top: 10px; }
        .btn-submit { background: var(--primary); color: white; border: none; width: 100%; padding: 20px; border-radius: 15px; font-size: 1.2rem; font-weight: 800; cursor: pointer; transition: 0.3s; margin-top: 20px; margin-bottom: 50px; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(30,41,59,0.2); }
    </style>
</head>
<body>

    <div class="exam-header">
        <div class="exam-title">
            <?php echo $test_type === 'ielts' ? '📝 IELTS Writing Task 2' : '🎓 General Placement Test'; ?>
        </div>
        <div class="timer-box" id="timerBox">30:00</div>
    </div>

    <div class="container">
        <div id="loadingScreen">
            <div class="spinner"></div>
            <h2 style="font-weight: 800;">Giám khảo AI đang soạn đề...</h2>
            <p style="color: var(--text-muted);">Quá trình này có thể mất từ 5-15 giây. Vui lòng không tải lại trang!</p>
        </div>

        <div id="testScreen" style="display: none;">
            <form id="examForm" onsubmit="event.preventDefault(); submitExam();">
                <div id="examContent"></div>
                <button type="submit" class="btn-submit" id="submitBtn">NỘP BÀI</button>
            </form>
        </div>

        <div id="evaluatingScreen" style="display: none;">
            <div class="spinner"></div>
            <h2 style="font-weight: 800;">Đang phân tích bài làm của bạn...</h2>
            <p style="color: var(--text-muted);">AI đang chấm điểm và lên lộ trình học tập cá nhân hóa. Quá trình này có thể kéo dài 10-30 giây.</p>
        </div>
    </div>

<script>
    const testType = "<?php echo $test_type; ?>";
    let timerInterval;

    document.addEventListener("DOMContentLoaded", fetchTest);

    async function fetchTest() {
        try {
            const res = await fetch('take_a_test.php?action=generate');
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            renderTest(data);
            document.getElementById('loadingScreen').style.display = 'none';
            document.getElementById('testScreen').style.display = 'block';
        } catch (error) {
            alert("Lỗi khi tải đề thi: " + error.message);
        }
    }

    function renderTest(data) {
        const container = document.getElementById('examContent');
        let html = '';

        if (testType === 'ielts') {
            html += `
                <div class="ielts-topic"><strong>Topic:</strong><br>${data.topic}</div>
                <p style="font-style: italic; color: var(--text-muted);">${data.instructions}</p>
                <textarea id="ieltsAnswer" class="ielts-textarea" placeholder="Bắt đầu viết bài của bạn tại đây..." oninput="countWords(this.value)"></textarea>
                <div class="word-count">Số từ: <span id="wordCount">0</span></div>
            `;
            startTimer(30 * 60); 
        } 
        else {
            data.questions.forEach((q, index) => {
                html += `<div class="q-card" data-id="${q.id}">`;
                html += `<div class="q-number">Câu ${index + 1} • <span style="color:var(--text-muted); font-weight:600;">${q.level}</span></div>`;
                html += `<div class="q-text">${q.question.replace('_____', '<b>_____</b>')}</div>`;
                
                if (q.type === 'mcq') {
                    html += `<div class="options-list">`;
                    q.options.forEach(opt => {
                        const optId = `q_${q.id}_${opt}`;
                        html += `<label class="opt-label" for="${optId}"><input type="radio" name="ans_${q.id}" id="${optId}" value="${opt}" required>${opt}</label>`;
                    });
                    html += `</div>`;
                } else if (q.type === 'word_form') {
                    // FIX LỖI UI MOBILE: Tách Root word ra thành 1 label riêng biệt
                    html += `
                        <div><span class="wf-root">Từ gốc: ${q.root_word}</span></div>
                        <input type="text" name="ans_${q.id}" class="wf-input" placeholder="Nhập từ đã được chia..." autocomplete="off" required>
                    `;
                }
                html += `</div>`;
            });
        }
        container.innerHTML = html;
    }

    function countWords(str) {
        const count = str.trim().split(/\s+/).filter(word => word.length > 0).length;
        document.getElementById('wordCount').innerText = count;
    }

    function startTimer(seconds) {
        const timerBox = document.getElementById('timerBox');
        timerBox.style.display = 'block';
        timerInterval = setInterval(() => {
            seconds--;
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            timerBox.innerText = `${m}:${s}`;
            if (seconds <= 300) { timerBox.style.background = '#ef4444'; timerBox.style.color = 'white'; }
            if (seconds <= 0) { clearInterval(timerInterval); alert("Hết giờ! Hệ thống tự động nộp bài."); submitExam(); }
        }, 1000);
    }

    async function submitExam() {
        if(timerInterval) clearInterval(timerInterval);
        document.getElementById('testScreen').style.display = 'none';
        document.getElementById('evaluatingScreen').style.display = 'block';

        let payload = { type: testType, answers: {} };

        if (testType === 'ielts') {
            payload.answers.essay = document.getElementById('ieltsAnswer').value.trim();
        } else {
            const formData = new FormData(document.getElementById('examForm'));
            for (let [key, value] of formData.entries()) {
                payload.answers[key.replace('ans_', '')] = value.trim();
            }
        }

        // Gửi API thật để chấm điểm
        try {
            const res = await fetch('take_a_test.php?action=evaluate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            
            if(result.status === 'success') {
                window.location.href = 'profile.php'; // Thành công, văng về Profile xem kết quả
            } else {
                throw new Error(result.message || "Lỗi không xác định");
            }
        } catch(e) {
            alert("Lỗi chấm điểm: " + e.message);
            document.getElementById('evaluatingScreen').style.display = 'none';
            document.getElementById('testScreen').style.display = 'block'; // Trả lại UI để user nộp lại
        }
    }
</script>
</body>
</html>
