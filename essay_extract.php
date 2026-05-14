<?php
session_start();

// --- NEW FIX: Kick logged-out users back to the login page ---
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
    <title>Trích xuất từ vựng AI | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b; --danger: #ef4444;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Be Vietnam Pro', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); box-sizing: border-box; }
        .container { max-width: 900px; margin: 20px auto; width: 100%; }

        /* --- AI STATUS BADGE --- */
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 700; margin-bottom: 25px; border: 1px solid var(--border);
            background: white; transition: 0.3s;
        }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #94a3b8; }
        .status-online { background: var(--accent); box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
        .status-offline { background: var(--danger); box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); }

        /* --- UI ELEMENTS --- */
        .card { background: white; padding: 40px; border-radius: 30px; border: 1px solid var(--border); box-shadow: 0 10px 40px rgba(0,0,0,0.02); box-sizing: border-box; }
        
        .page-title { margin: 0 0 10px 0; font-weight: 800; font-size: 2.2rem; letter-spacing: -0.5px; }
        .page-subtitle { color: var(--text-muted); font-weight: 500; margin-bottom: 30px; line-height: 1.5; font-size: 1.05rem; }

        textarea {
            width: 100%; border-radius: 20px; border: 2px solid var(--border);
            padding: 25px; font-family: inherit; font-size: 1.05rem; box-sizing: border-box;
            outline: none; transition: 0.3s; resize: vertical; min-height: 250px;
        }
        textarea:focus { border-color: var(--accent); background: #f0fdf4; }

        .controls { display: flex; gap: 15px; align-items: center; margin-top: 25px; flex-wrap: wrap; }
        
        /* Custom Input for dynamic prompts */
        .input-group { flex: 1; display: flex; align-items: center; gap: 15px; width: 100%; }
        .input-label { font-weight: 800; font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); white-space: nowrap; }
        .custom-input { 
            width: 100%; padding: 14px 20px; border-radius: 14px; 
            border: 1px solid var(--border); font-family: inherit; 
            font-weight: 600; font-size: 1rem; outline: none; transition: 0.3s; box-sizing: border-box;
        }
        .custom-input:focus { border-color: var(--accent); }

        .btn-extract {
            background: var(--primary); color: white; border: none; padding: 15px 35px;
            border-radius: 14px; cursor: pointer; font-weight: 700; font-size: 1.05rem; transition: 0.3s var(--spring);
            display: flex; align-items: center; justify-content: center; gap: 10px; white-space: nowrap;
        }
        .btn-extract:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-extract:disabled { opacity: 0.6; cursor: not-allowed; }

        /* --- RESULTS --- */
        .result-item {
            background: white; padding: 20px 25px; border-radius: 20px; border: 1px solid var(--border);
            margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;
            animation: slideIn 0.4s var(--spring) forwards; flex-wrap: wrap; gap: 15px;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        .word-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; flex-wrap: wrap; }
        .word-text { font-size: 1.3rem; font-weight: 800; color: var(--primary); }
        .tag { font-size: 0.7rem; background: #f1f5f9; padding: 5px 12px; border-radius: 8px; font-weight: 800; text-transform: uppercase; color: var(--primary); }
        .folder-tag { background: #ecfdf5; color: #059669; border: 1px solid #dcfce7; white-space: nowrap; }
        .def-vi-text { font-size: 0.95rem; color: #1e40af; font-weight: 600; }

        .error-box {
            display: none; background: #fef2f2; color: #ef4444; padding: 16px 25px;
            border-radius: 16px; margin-top: 20px; font-weight: 600; border: 1px solid #fee2e2; line-height: 1.5;
        }

        /* --- MOBILE RESPONSIVENESS --- */
        @media (max-width: 768px) {
            body { padding: 20px; }
            .card { padding: 25px; border-radius: 24px; }
            .page-title { font-size: 1.8rem; }
            
            textarea { padding: 20px; font-size: 1rem; min-height: 200px; }
            
            .controls { flex-direction: column; align-items: stretch; gap: 20px; }
            .input-group { flex-direction: column; align-items: flex-start; gap: 8px; }
            .btn-extract { width: 100%; padding: 18px; }
            
            .result-item { flex-direction: column; align-items: flex-start; }
            .folder-tag { align-self: flex-start; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="status-badge" id="aiStatus">
            <div class="status-dot" id="statusDot"></div>
            <span id="statusText">Đang kiểm tra kết nối AI...</span>
        </div>

        <div class="card">
            <h1 class="page-title">Trích xuất từ vựng</h1>
            <p class="page-subtitle">Tự động phân tích và phân loại từ vựng nâng cao từ bài viết của bạn chỉ trong vài giây.</p>

            <textarea id="essayInput" placeholder="Dán bài tiểu luận, bài báo hoặc đoạn văn của bạn vào đây..."></textarea>
            
            <div id="errorMessage" class="error-box"></div>

            <div class="controls">
                <div class="input-group">
                    <label class="input-label">Mục tiêu:</label>
                    <input type="text" id="customReq" class="custom-input" 
                           placeholder="VD: Từ vựng IELTS 8.0, Y khoa, Cụm động từ..." 
                           value="Từ vựng cấp độ C1">
                </div>
                <button class="btn-extract" onclick="extractWords()" id="extractBtn">
                    <span id="btnIcon">⚡</span> <span id="btnText">Phân tích văn bản</span>
                </button>
            </div>
        </div>

        <div id="resultsArea" style="margin-top: 50px; display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
                <h3 id="resultCount" style="margin:0; font-weight: 800; font-size: 1.4rem;">0 từ được tìm thấy</h3>
                <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-muted);">Kiểm tra và lưu trữ</span>
            </div>
            
            <div id="wordsList"></div>
            
            <button class="btn-extract" id="saveAllBtn" 
                    style="background: var(--accent); width: 100%; margin-top: 25px; padding: 20px; font-size: 1.1rem;" 
                    onclick="saveAllWords()">
                Xác nhận & Lưu vào kho ✓
            </button>
        </div>
    </div>

<script>
    const API_URL = '/api';
    let extractedWords = [];

    // --- 1. HEARTBEAT: CHECK IF BACKEND IS ALIVE ---
    async function checkBackend() {
        const dot = document.getElementById('statusDot');
        const text = document.getElementById('statusText');
        
        try {
            const res = await fetch(`${API_URL}/quiz-sentence?word=test`); 
            if (res.ok) {
                dot.className = "status-dot status-online";
                text.innerText = "AI Engine: Hoạt động bình thường";
            }
        } catch (e) {
            dot.className = "status-dot status-offline";
            text.innerText = "AI Engine: Mất kết nối (Kiểm tra PM2)";
        }
    }

    checkBackend();
    setInterval(checkBackend, 10000);

    // --- 2. ROBUST EXTRACTION LOGIC ---
    async function extractWords() {
        const essay = document.getElementById('essayInput').value.trim();
        const req = document.getElementById('customReq').value.trim();
        const btn = document.getElementById('extractBtn');
        const btnText = document.getElementById('btnText');
        const errorBox = document.getElementById('errorMessage');

        if (!essay) {
            showError("Vui lòng dán văn bản cần phân tích trước.");
            return;
        }

        errorBox.style.display = "none";
        btn.disabled = true;
        btnText.innerText = "Đang xử lý...";
        document.getElementById('resultsArea').style.display = "none";

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 35000); // Increased timeout for larger essays

            const res = await fetch(`${API_URL}/extract-vocab`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ essay, requirement: req || "Từ vựng cấp độ C1" }),
                signal: controller.signal
            });

            if (!res.ok) throw new Error("AI Server returned an error.");

            extractedWords = await res.json();
            
            if (extractedWords && extractedWords.length > 0) {
                renderResults();
            } else {
                showError("AI không tìm thấy từ vựng nào phù hợp với yêu cầu của bạn.");
                btn.disabled = false;
                btnText.innerText = "Phân tích văn bản";
            }
            
        } catch (e) {
            if (e.name === 'AbortError') {
                showError("Yêu cầu quá hạn. Văn bản của bạn có thể quá dài, hãy thử chia nhỏ ra nhé.");
            } else {
                showError("Không thể kết nối đến AI. Vui lòng đảm bảo PM2 đang chạy 'vocab-ai'.");
            }
            btn.disabled = false;
            btnText.innerText = "Phân tích văn bản";
        }
    }

    function showError(msg) {
        const errorBox = document.getElementById('errorMessage');
        errorBox.innerText = `⚠️ ${msg}`;
        errorBox.style.display = "block";
    }

    function renderResults() {
        const list = document.getElementById('wordsList');
        const btn = document.getElementById('extractBtn');
        const btnText = document.getElementById('btnText');
        
        list.innerHTML = "";
        document.getElementById('resultCount').innerText = `Đã tìm thấy ${extractedWords.length} từ vựng`;

        extractedWords.forEach((w, index) => {
            list.innerHTML += `
                <div class="result-item" style="animation-delay: ${index * 0.05}s">
                    <div style="flex: 1;">
                        <div class="word-header">
                            <span class="word-text">${w.word}</span>
                            <span class="tag">${w.level}</span>
                        </div>
                        <div class="def-vi-text">${w.definition_vi}</div>
                    </div>
                    <div class="tag folder-tag">Thư mục: ${w.suggested_category}</div>
                </div>
            `;
        });

        document.getElementById('resultsArea').style.display = "block";
        btn.disabled = false;
        btnText.innerText = "Phân tích văn bản";
    }

    async function saveAllWords() {
        const btn = document.getElementById('saveAllBtn');
        btn.innerText = "Đang lưu trữ...";
        btn.disabled = true;
        
        try {
            const res = await fetch('process_essay.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ words: extractedWords })
            });

            const result = await res.json();
            if (result.success) {
                btn.innerText = `Đã thêm ${result.count} từ vào kho ✓`;
                setTimeout(() => window.location.href = 'board.php', 1500);
            } else {
                throw new Error("Lỗi lưu trữ.");
            }
        } catch (e) {
            alert("Đã xảy ra lỗi khi lưu vào cơ sở dữ liệu.");
            btn.innerText = "Xác nhận & Lưu vào kho ✓";
            btn.disabled = false;
        }
    }
</script>
</body>
</html>
