<?php
session_start();

// Bảo mật: Đảm bảo người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>AI Writing Coach | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b;
            --accent: #10b981; 
            --bg: #f8fafc;
            --border: #e2e8f0; 
            --text-muted: #64748b; 
            --danger: #ef4444;
            --warning: #f59e0b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            font-family: 'Be Vietnam Pro', sans-serif; 
            background: var(--bg); 
            margin: 0; 
            padding: 40px; 
            color: var(--primary); 
            box-sizing: border-box; 
        }

        .container { max-width: 900px; margin: 20px auto; width: 100%; }

        @keyframes springUp {
            0% { opacity: 0; transform: scale(0.9) translateY(40px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-spring { animation: springUp 0.7s var(--spring) forwards; }

        /* --- AI STATUS BADGE --- */
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 700; margin-bottom: 25px; border: 1px solid var(--border);
            background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #94a3b8; transition: 0.3s; }
        .status-online { background: var(--accent); box-shadow: 0 0 12px rgba(16, 185, 129, 0.4); }
        .status-offline { background: var(--danger); box-shadow: 0 0 12px rgba(239, 68, 68, 0.4); }

        .card { 
            background: white; padding: 45px; border-radius: 35px; 
            border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.04); 
            box-sizing: border-box; margin-bottom: 30px;
        }
        
        .page-title { margin: 0 0 10px 0; font-weight: 800; font-size: 2.5rem; letter-spacing: -1px; }
        .page-subtitle { color: var(--text-muted); font-weight: 500; margin-bottom: 35px; font-size: 1.1rem; }

        textarea {
            width: 100%; border-radius: 24px; border: 2px solid var(--border);
            padding: 25px; font-family: inherit; font-size: 1.05rem; box-sizing: border-box;
            outline: none; transition: 0.3s; resize: vertical; min-height: 280px;
        }
        textarea:focus { border-color: var(--accent); background: #f0fdf4; }

        .controls { display: flex; gap: 15px; align-items: center; margin-top: 25px; flex-wrap: wrap; }
        .custom-input { 
            flex: 1; padding: 16px 20px; border-radius: 16px; 
            border: 1px solid var(--border); font-family: inherit; 
            font-weight: 600; font-size: 1rem; outline: none; transition: 0.3s;
        }
        .custom-input:focus { border-color: var(--accent); }

        .btn-extract {
            background: var(--primary); color: white; border: none; padding: 18px 35px;
            border-radius: 18px; cursor: pointer; font-weight: 800; font-size: 1.05rem; 
            transition: 0.3s var(--spring); display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-extract:hover:not(:disabled) { transform: translateY(-4px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .btn-extract:disabled { opacity: 0.6; cursor: not-allowed; }

        /* --- AI COACH SECTION --- */
        .coach-container { display: none; margin-top: 40px; }
        .coach-card {
            background: #f1f5f9; border-radius: 28px; padding: 35px;
            border: 1px solid var(--border); margin-bottom: 30px;
        }
        .coach-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .coach-avatar { width: 45px; height: 45px; border-radius: 14px; background: var(--accent); color: white; 
                       display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .summary-text { font-size: 1.1rem; line-height: 1.7; font-weight: 500; color: #334155; margin-bottom: 25px; white-space: pre-wrap; }
        
        .pros-cons-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .pc-item { padding: 20px; border-radius: 20px; background: white; border: 1px solid var(--border); }
        .pc-title { font-weight: 800; font-size: 0.75rem; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px; }
        .pc-list { margin: 0; padding-left: 20px; font-size: 0.9rem; line-height: 1.6; color: #475569; }

        /* --- VOCABULARY RESULTS --- */
        .result-item {
            background: white; padding: 25px; border-radius: 24px; border: 1px solid var(--border);
            margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: 0.3s;
        }
        .result-item:hover { border-color: var(--accent); transform: translateX(5px); }

        /* --- DEBUGGER SYSTEM --- */
        #debuggerArea {
            display: none; background: #0f172a; color: #38bdf8; padding: 25px;
            border-radius: 20px; margin-top: 30px; font-family: 'Courier New', monospace;
            font-size: 0.8rem; line-height: 1.5; overflow-x: auto; border: 2px solid #1e293b;
        }
        .debug-label { color: #ef4444; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; display: block; }

        @media (max-width: 768px) {
            body { padding: 20px; padding-top: 60px; }
            .pros-cons-grid { grid-template-columns: 1fr; }
            .page-title { font-size: 2rem; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container animate-spring">
        <div class="status-badge">
            <div class="status-dot" id="statusDot"></div>
            <span id="statusText">Đang kiểm tra AI Engine...</span>
        </div>

        <div class="card">
            <h1 class="page-title">AI Writing Coach</h1>
            <p class="page-subtitle">Nhận xét chuyên sâu và trích xuất từ vựng học thuật từ bài viết của bạn.</p>

            <textarea id="essayInput" placeholder="Dán bài luận hoặc đoạn văn tiếng Anh của bạn tại đây..."></textarea>
            
            <div class="controls">
                <input type="text" id="customReq" class="custom-input" placeholder="Yêu cầu: IELTS 8.0, C1, Academic..." value="Từ vựng cấp độ C1">
                <button class="btn-extract" onclick="extractWords()" id="extractBtn">
                    <span id="btnText">Phân tích với Gemini</span>
                </button>
            </div>
        </div>

        <div id="resultsArea" class="coach-container">
            <div class="coach-card">
                <div class="coach-header">
                    <div class="coach-avatar">🤖</div>
                    <h3 style="margin:0; font-weight:800;">Nhận xét từ AI Coach</h3>
                </div>
                <div id="coachSummary" class="summary-text"></div>
                <div class="pros-cons-grid">
                    <div class="pc-item" style="border-left: 5px solid var(--accent);">
                        <div class="pc-title" style="color: var(--accent);">Điểm mạnh (Pros)</div>
                        <ul id="coachPros" class="pc-list"></ul>
                    </div>
                    <div class="pc-item" style="border-left: 5px solid var(--warning);">
                        <div class="pc-title" style="color: var(--warning);">Cần cải thiện (Cons)</div>
                        <ul id="coachCons" class="pc-list"></ul>
                    </div>
                </div>
            </div>

            <h3 id="resultCount" style="margin-bottom: 25px; font-weight: 800; font-size: 1.4rem;">0 từ vựng tiềm năng</h3>
            <div id="wordsList"></div>
            
            <button class="btn-extract" id="saveAllBtn" 
                    style="background: var(--accent); width: 100%; margin-top: 30px; padding: 22px;" 
                    onclick="saveAllWords()">
                Xác nhận lưu tất cả vào kho ✓
            </button>
        </div>

        <div id="debuggerArea">
            <span class="debug-label">⚠️ DEBUGGER - PHÁT HIỆN LỖI HỆ THỐNG:</span>
            <div id="debugContent"></div>
            <div style="margin-top:15px; opacity:0.6; font-size: 0.75rem;">Phản hồi thô từ AI (Raw JSON):</div>
            <pre id="debugRaw" style="background:#1e293b; padding:15px; border-radius:10px; margin-top:8px; white-space: pre-wrap; font-size: 0.75rem; color: #94a3b8;"></pre>
        </div>
    </div>

<script>
    const API_URL = '/api'; // Đường dẫn Proxy tới localhost:5000
    let extractedData = null;

    // --- 1. KIỂM TRẠ TRẠNG THÁI AI (Health Check) ---
    async function checkAIStatus() {
        const dot = document.getElementById('statusDot');
        const text = document.getElementById('statusText');
        try {
            const res = await fetch(`${API_URL}/health`);
            if (res.ok) {
                dot.className = "status-dot status-online";
                text.innerText = "AI Engine: Sẵn sàng";
            } else {
                throw new Error();
            }
        } catch (e) {
            dot.className = "status-dot status-offline";
            text.innerText = "AI Engine: Ngoại tuyến (Kiểm tra app.py)";
        }
    }
    checkAIStatus();

    // --- 2. XỬ LÝ TRÍCH XUẤT ---
    async function extractWords() {
        const essay = document.getElementById('essayInput').value.trim();
        const req = document.getElementById('customReq').value.trim();
        const btn = document.getElementById('extractBtn');
        const resultsArea = document.getElementById('resultsArea');
        const debuggerArea = document.getElementById('debuggerArea');

        if (!essay) { alert("Vui lòng nhập nội dung bài viết!"); return; }

        btn.disabled = true;
        btn.innerHTML = "AI đang đọc bài...";
        resultsArea.style.display = "none";
        debuggerArea.style.display = "none";

        try {
            const res = await fetch(`${API_URL}/extract-vocab`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ essay, requirement: req })
            });

            const data = await res.json();
            if (!res.ok) throw { message: data.error || "Lỗi API", raw: data };

            extractedData = data;
            renderResults();
            
        } catch (e) {
            showDebugger(e);
            btn.disabled = false;
            btn.innerHTML = "Phân tích với Gemini";
        }
    }

    // --- 3. HIỂN THỊ KẾT QUẢ ---
    function renderResults() {
        const { coaching, words } = extractedData;
        
        // Hiển thị AI Coach (Tiếng Việt)
        document.getElementById('coachSummary').innerText = coaching.summary;
        document.getElementById('coachPros').innerHTML = coaching.pros.map(p => `<li>${p}</li>`).join('');
        document.getElementById('coachCons').innerHTML = coaching.cons.map(c => `<li>${c}</li>`).join('');

        // Hiển thị danh sách từ
        const list = document.getElementById('wordsList');
        list.innerHTML = "";
        document.getElementById('resultCount').innerText = `${words.length} từ vựng tiềm năng được tìm thấy`;
        
        words.forEach((w, idx) => {
            list.innerHTML += `
                <div class="result-item" style="animation: springUp 0.6s var(--spring) forwards ${idx * 0.05}s; opacity:0;">
                    <div style="flex: 1;">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                            <span style="font-weight:800; font-size:1.2rem;">${w.word}</span>
                            <span style="font-size:0.7rem; background:#f1f5f9; padding:4px 10px; border-radius:8px; font-weight:800; color:var(--text-muted);">${w.level}</span>
                        </div>
                        <div style="color: #1e40af; font-weight:600; font-size:0.9rem;">${w.definition_vi}</div>
                    </div>
                    <div style="font-size:0.7rem; background:#ecfdf4; color:#059669; padding:6px 12px; border-radius:10px; font-weight:800; text-transform: uppercase;">
                        ${w.suggested_category}
                    </div>
                </div>
            `;
        });

        document.getElementById('resultsArea').style.display = "block";
        document.getElementById('extractBtn').disabled = false;
        document.getElementById('extractBtn').innerHTML = "Phân tích với Gemini";
        window.scrollTo({ top: document.getElementById('resultsArea').offsetTop - 50, behavior: 'smooth' });
    }

    // --- 4. DEBUGGER ---
    function showDebugger(err) {
        const area = document.getElementById('debuggerArea');
        area.style.display = "block";
        document.getElementById('debugContent').innerHTML = `
            <div style="color: #fb7185;">Mô tả lỗi: ${err.message}</div>
            <div style="margin-top:5px; color: #94a3b8;">Có thể do: OpenRouter Key hết hạn, lỗi cấu hình Proxy, hoặc AI trả về sai format.</div>
        `;
        document.getElementById('debugRaw').innerText = JSON.stringify(err.raw || "Không có dữ liệu phản hồi", null, 2);
    }

    // --- 5. LƯU VÀO DATABASE ---
    async function saveAllWords() {
        const btn = document.getElementById('saveAllBtn');
        btn.innerText = "Đang lưu trữ...";
        btn.disabled = true;
        
        try {
            const res = await fetch('process_essay.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ words: extractedData.words })
            });
            const result = await res.json();
            if (result.success) {
                btn.innerText = `Đã lưu thành công ${result.count} từ! ✓`;
                setTimeout(() => window.location.href = 'board.php', 1200);
            }
        } catch (e) {
            alert("Lỗi lưu trữ dữ liệu.");
            btn.disabled = false;
            btn.innerText = "Xác nhận lưu tất cả vào kho ✓";
        }
    }
</script>
</body>
</html>
