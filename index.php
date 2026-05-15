<?php
session_start();
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
    <title>Khám phá Từ vựng | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; 
            --accent: #10b981; 
            --bg: #f8fafc;
            --border: #e2e8f0; 
            --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            font-family: 'Be Vietnam Pro', sans-serif;
            background: var(--bg); 
            margin: 0; 
            color: var(--primary);
            overflow-x: hidden; 
        }

        .main-content { 
            padding: 80px 20px 40px 20px;
            max-width: 800px; 
            margin: 0 auto; 
            min-height: 100vh; 
            box-sizing: border-box;
        }

        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px; border-radius: 20px; font-size: 0.75rem;
            font-weight: 700; margin-bottom: 20px; border: 1px solid var(--border);
            background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
        .status-online { background: var(--accent); box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
        .status-offline { background: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); }

        .search-container { 
            position: relative; 
            margin-bottom: 30px; 
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        input#wordSearch {
            width: 100%; 
            padding: 20px 25px; 
            border-radius: 20px;
            border: 2px solid var(--border); 
            font-size: 1.1rem; 
            font-family: inherit;
            box-sizing: border-box; 
            outline: none; 
            transition: 0.3s var(--spring);
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        }

        .btn-search {
            background: var(--primary); 
            color: white; 
            border: none;
            padding: 18px; 
            border-radius: 18px; 
            font-weight: 800;
            cursor: pointer; 
            transition: 0.3s;
            font-size: 1rem;
            width: 100%;
        }

        @media (min-width: 600px) {
            .search-container { flex-direction: row; gap: 0; }
            .btn-search { 
                position: absolute; right: 8px; top: 8px; bottom: 8px; 
                width: auto; padding: 0 25px; 
            }
            input#wordSearch { padding-right: 120px; }
        }

        .result-card {
            background: white; 
            padding: 30px; 
            border-radius: 30px;
            border: 1px solid var(--border); 
            box-shadow: 0 20px 50px rgba(0,0,0,0.04);
            display: none; 
            animation: slideUp 0.6s var(--spring) forwards;
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .word-header { 
            display: flex; 
            flex-direction: column; 
            gap: 10px; 
            margin-bottom: 25px; 
        }
        
        @media (min-width: 600px) {
            .word-header { flex-direction: row; justify-content: space-between; align-items: center; }
        }

        .word-main { font-size: 2.5rem; font-weight: 800; letter-spacing: -1.5px; margin: 0; line-height: 1.1; }
        .ipa { font-size: 1.1rem; color: var(--text-muted); font-weight: 500; display: block; margin-top: 4px; }
        .tag { 
            background: #f1f5f9; 
            padding: 6px 12px; 
            border-radius: 10px; 
            font-weight: 800; 
            font-size: 0.75rem; 
            display: inline-block;
            width: fit-content;
        }

        .def-box { margin-bottom: 25px; line-height: 1.5; }
        .def-vi { font-size: 1.25rem; font-weight: 700; color: #1e40af; margin-bottom: 8px; }
        .def-en { font-size: 0.95rem; color: #475569; font-weight: 500; }

        .example-box { 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 18px; 
            border-left: 4px solid var(--accent); 
            font-style: italic; 
            color: #334155; 
            margin-bottom: 25px; 
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Synonyms & Antonyms UI */
        .pair-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        @media (min-width: 600px) {
            .pair-grid { grid-template-columns: 1fr 1fr; }
        }
        .pair-card {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px 16px;
        }
        .pair-title {
            font-weight: 800;
            font-size: 0.85rem;
            color: var(--accent);
            margin-bottom: 6px;
        }
        .pair-content {
            font-weight: 600;
            color: var(--primary);
            line-height: 1.4;
            font-size: 0.95rem;
        }

        .btn-save {
            width: 100%; padding: 18px; border-radius: 16px; border: none;
            background: var(--primary); color: white; font-weight: 800; font-size: 1rem;
            cursor: pointer; transition: 0.3s var(--spring);
        }

        #debuggerArea {
            display: none; background: #0f172a; color: #38bdf8; padding: 20px;
            border-radius: 18px; margin-top: 30px; font-family: 'Courier New', monospace;
            font-size: 0.75rem; line-height: 1.4; border: 2px solid #1e293b; overflow-x: auto;
        }

        #saveToast {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: white; padding: 12px 20px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: none;
            align-items: center; gap: 10px; font-weight: 700; z-index: 2000;
            border: 1px solid var(--border); font-size: 0.9rem;
        }
        @media (min-width: 600px) {
            #saveToast { width: fit-content; left: 50%; transform: translateX(-50%); }
        }

        h1.page-title { font-weight: 800; font-size: 1.8rem; letter-spacing: -0.5px; margin-bottom: 8px; margin-top: 0; }
        p.page-desc { color: var(--text-muted); font-weight: 500; margin-bottom: 30px; font-size: 0.95rem; }
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

            <!-- Synonyms / Antonyms -->
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

<script>
    const API_URL = '/api';
    let activeWordData = null;

    async function checkAI() {
        const dot = document.getElementById('statusDot');
        const text = document.getElementById('statusText');
        try {
            const res = await fetch(`${API_URL}/health`);
            if (res.ok) {
                dot.className = "status-dot status-online";
                text.innerText = "Sẵn sàng";
            }
        } catch (e) {
            dot.className = "status-dot status-offline";
            text.innerText = "Mất kết nối";
        }
    }
    checkAI();

    async function searchWord() {
        const word = document.getElementById('wordSearch').value.trim();
        const btn = document.getElementById('searchBtn');
        const card = document.getElementById('resultCard');
        const debugArea = document.getElementById('debuggerArea');

        if (!word) return;

        btn.disabled = true;
        btn.innerText = "...";
        card.style.display = "none";
        debugArea.style.display = "none";

        try {
            const res = await fetch(`${API_URL}/get-vocab?word=${encodeURIComponent(word)}`);
            const rawText = await res.text();
            let data;

            try { data = JSON.parse(rawText); } 
            catch (e) { throw { message: "Phản hồi AI không phải JSON.", raw: rawText }; }

            if (!res.ok) throw { message: data.error || "Lỗi API", raw: data };

            activeWordData = data;
            renderWord();

        } catch (e) {
            showDebugger(e);
        } finally {
            btn.disabled = false;
            btn.innerText = "Tìm kiếm";
        }
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
        
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.innerText = "Lưu vào kho từ vựng +";
        saveBtn.style.background = "var(--primary)";
        saveBtn.disabled = false;
    }

    async function saveToVault() {
        if (!activeWordData) return;
        const saveBtn = document.getElementById('saveBtn');
        const toast = document.getElementById('saveToast');
        saveBtn.disabled = true;
        saveBtn.innerText = "Đang lưu...";
        
        const formData = new FormData();

        Object.keys(activeWordData).forEach(key => formData.append(key, activeWordData[key]));
        formData.append('synonyms', activeWordData.synonyms || '');
        formData.append('antonyms', activeWordData.antonyms || '');
        
        try {
            const res = await fetch('save.php', { method: 'POST', body: formData });
            const resultText = (await res.text()).trim();
            
            if (resultText === "Success") {
                saveBtn.innerText = "Đã lưu ✓";
                saveBtn.style.background = "var(--accent)";
                toast.style.display = "flex";
                setTimeout(() => { toast.style.display = "none"; }, 3000);
            } else if (resultText === "Duplicate") {
                saveBtn.innerText = "Đã có trong kho";
                saveBtn.style.background = "#64748b";
            } else { throw { message: "Lỗi lưu trữ PHP", raw: resultText }; }
        } catch (e) {
            showDebugger(e);
            saveBtn.disabled = false;
            saveBtn.innerText = "Lỗi!";
        }
    }

    function showDebugger(err) {
        document.getElementById('debuggerArea').style.display = "block";
        document.getElementById('debugContent').innerText = err.message;
        document.getElementById('debugRaw').innerText = typeof err.raw === 'object' ? JSON.stringify(err.raw, null, 2) : err.raw;
    }
</script>
</body>
</html>
