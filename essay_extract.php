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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Vocabulary Extractor | Vocab AI Pro</title>

    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary:#1e293b;
            --accent:#10b981;
            --danger:#ef4444;
            --warning:#f59e0b;
            --bg:#f8fafc;
            --border:#e2e8f0;
            --text-muted:#64748b;
            --spring:cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body {
            font-family:'Be Vietnam Pro', sans-serif;
            background:var(--bg);
            margin:0;
            padding:40px 20px;
            color:var(--primary);
            overflow-x:hidden;
        }

        @keyframes fadeInDown {
            from { opacity:0; transform:translateY(-20px); }
            to { opacity:1; transform:translateY(0); }
        }

        @keyframes slideUp {
            from { opacity:0; transform:translateY(20px); }
            to { opacity:1; transform:translateY(0); }
        }

        @keyframes popIn {
            from { opacity:0; transform:scale(0.95) translateY(10px); }
            to { opacity:1; transform:scale(1) translateY(0); }
        }

        @keyframes pulseDot {
            0% { box-shadow:0 0 0 0 rgba(16,185,129,0.7); }
            70% { box-shadow:0 0 0 6px rgba(16,185,129,0); }
            100% { box-shadow:0 0 0 0 rgba(16,185,129,0); }
        }

        @keyframes pulseLoading {
            0% { opacity:0.6; transform:scale(0.98); }
            50% { opacity:1; transform:scale(1); }
            100% { opacity:0.6; transform:scale(0.98); }
        }

        .container {
            max-width:900px;
            margin:0 auto;
            animation:fadeInDown 0.8s var(--spring) forwards;
        }

        .status-badge {
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:6px 14px;
            border-radius:20px;
            font-size:0.75rem;
            font-weight:700;
            margin-bottom:20px;
            border:1px solid var(--border);
            background:white;
        }

        .status-dot {
            width:8px;
            height:8px;
            border-radius:50%;
            background:#94a3b8;
        }

        .status-online {
            background:var(--accent);
            animation:pulseDot 2s infinite;
        }

        .status-offline {
            background:var(--danger);
        }

        .card {
            background:white;
            padding:30px;
            border-radius:25px;
            border:1px solid var(--border);
            box-shadow:0 10px 30px rgba(0,0,0,0.03);
            margin-bottom:20px;
        }

        .title {
            font-size:1.8rem;
            font-weight:800;
            margin-bottom:10px;
            color:var(--primary);
            letter-spacing:-0.5px;
        }

        textarea {
            width:100%;
            height:220px;
            padding:20px;
            border-radius:15px;
            border:2px solid var(--border);
            font-family:inherit;
            font-size:1rem;
            resize:vertical;
            box-sizing:border-box;
            background:#f8fafc;
        }

        textarea:focus {
            outline:none;
            border-color:var(--accent);
            background:white;
            box-shadow:0 10px 25px rgba(16,185,129,0.1);
        }

        input.req-input {
            width:100%;
            padding:15px;
            border-radius:15px;
            border:2px solid var(--border);
            font-family:inherit;
            font-size:1rem;
            margin:20px 0;
            box-sizing:border-box;
        }

        input.req-input:focus {
            outline:none;
            border-color:var(--accent);
        }

        .btn-main {
            background:var(--primary);
            color:white;
            padding:18px 30px;
            border:none;
            border-radius:15px;
            font-weight:800;
            font-size:1.1rem;
            cursor:pointer;
            width:100%;
            transition:all 0.3s var(--spring);
        }

        .btn-main:hover:not(:disabled) {
            transform:translateY(-3px);
            box-shadow:0 12px 25px rgba(30,41,59,0.2);
            background:#0f172a;
        }

        .btn-main:disabled {
            background:var(--text-muted);
            cursor:not-allowed;
        }

        #loader {
            display:none;
            text-align:center;
            padding:40px;
            font-weight:800;
            color:var(--accent);
            font-size:1.1rem;
            animation:pulseLoading 1.5s infinite ease-in-out;
        }

        #resultArea {
            display:none;
            animation:slideUp 0.6s var(--spring) forwards;
        }

        .section-title {
            font-weight:800;
            color:var(--accent);
            border-bottom:2px dashed var(--border);
            padding-bottom:10px;
            margin-top:10px;
            margin-bottom:20px;
            font-size:1.2rem;
        }

        .category-block {
            margin-bottom:35px;
            animation:popIn 0.5s var(--spring) forwards;
            opacity:0;
        }

        .category-title {
            font-weight:800;
            color:var(--primary);
            margin-bottom:15px;
            font-size:1.2rem;
        }

        .vocab-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(250px, 1fr));
            gap:20px;
        }

        .word-card {
            background:white;
            border:1px solid var(--border);
            padding:24px;
            border-radius:20px;
            box-shadow:0 4px 6px rgba(0,0,0,0.02);
            transition:all 0.3s var(--spring);
            display:flex;
            flex-direction:column;
        }

        .word-card:hover {
            transform:translateY(-5px);
            box-shadow:0 15px 30px rgba(0,0,0,0.06);
        }

        .word-card h3 {
            margin:0 0 5px 0;
            color:var(--accent);
            font-size:1.4rem;
            font-weight:800;
        }

        .word-ipa {
            color:var(--text-muted);
            font-size:0.9rem;
            margin-bottom:10px;
            display:block;
            background:#f1f5f9;
            padding:4px 8px;
            border-radius:8px;
            width:fit-content;
            font-family:monospace;
        }

        .word-form {
            font-size:0.82rem;
            color:#64748b;
            font-style:italic;
            margin-bottom:10px;
            font-weight:700;
        }

        .word-def {
            font-weight:700;
            margin-bottom:12px;
            color:var(--primary);
            line-height:1.4;
            flex-grow:1;
        }

        .word-ex {
            font-style:italic;
            color:#475569;
            font-size:0.95rem;
            line-height:1.5;
            margin-bottom:12px;
            border-left:3px solid var(--border);
            padding-left:10px;
        }

        .word-extra {
            margin-top:8px;
            font-size:0.82rem;
            color:#475569;
            line-height:1.5;
        }

        .btn-save-word {
            width:100%;
            padding:14px 16px;
            border-radius:14px;
            border:none;
            background:var(--primary);
            color:white;
            font-weight:800;
            font-size:0.95rem;
            cursor:pointer;
            transition:all 0.3s var(--spring);
            margin-top:18px;
        }

        .btn-save-word:disabled {
            background:var(--text-muted);
            cursor:not-allowed;
        }

        #toast {
            position:fixed;
            bottom:24px;
            right:24px;
            z-index:3000;
            background:white;
            border:1px solid var(--border);
            padding:14px 20px;
            border-radius:16px;
            box-shadow:0 15px 40px rgba(0,0,0,0.15);
            display:none;
            align-items:center;
            gap:12px;
            font-weight:700;
            font-size:0.95rem;
        }

        #toast.success { border-color:#86efac; }
        #toast.error { border-color:#fda4af; }

        #toast .dot {
            width:12px;
            height:12px;
            border-radius:50%;
        }

        #toast.success .dot { background:#10b981; }
        #toast.error .dot { background:#ef4444; }

        #debuggerArea {
            display:none;
            background:#0f172a;
            color:#e2e8f0;
            padding:20px;
            border-radius:18px;
            margin-top:20px;
            font-family:monospace;
            border:2px solid #1e293b;
        }

        .debug-title {
            color:var(--warning);
            font-weight:bold;
            margin-bottom:10px;
            font-size:1.1rem;
        }
    </style>
</head>

<body>

<?php include 'nav.php'; ?>

<div class="container">

    <div class="status-badge" id="aiStatus">
        <div class="status-dot" id="statusDot"></div>
        <span id="statusText">AI Engine...</span>
    </div>

    <div class="card">
        <div class="title">✨ AI Vocabulary Extractor</div>

        <div id="usageInfo" style="margin-top:10px;margin-bottom:20px;color:#64748b;font-size:0.9rem;font-weight:600;">
            Daily limit: 15 requests
        </div>

        <p style="color:var(--text-muted); margin-bottom:25px; font-size:0.95rem;">
            Dán bài viết của bạn vào đây. AI sẽ tự động trích xuất từ vựng nâng cao, word form, nuance và collocations để lưu vào kho từ vựng.
        </p>

        <textarea id="essayInput" placeholder="Dán bài luận hoặc đoạn văn tiếng Anh vào đây..."></textarea>

        <input
            type="text"
            id="reqInput"
            class="req-input"
            value="Từ vựng cấp độ B1"
            placeholder="Yêu cầu: Từ vựng IELTS, C1, Academic, Collocations..."
        >

        <button id="extractBtn" class="btn-main" onclick="extractWords()" type="button">
            Trích xuất từ vựng
        </button>
    </div>

    <div id="loader">
        AI đang trích xuất từ vựng nâng cao... ⏳
    </div>

    <div id="resultArea" class="card">
        <div class="section-title">💎 Từ vựng nâng cao theo Category</div>
        <div id="vocabList"></div>
    </div>

    <div id="debuggerArea">
        <div class="debug-title">⚠️ DEBUGGER</div>
        <div id="debugMsg" style="margin-bottom:15px;color:#ef4444;"></div>
        <pre id="debugRaw" style="background:#1e293b;padding:15px;border-radius:10px;overflow-x:auto;font-size:0.85rem;color:#94a3b8;"></pre>
    </div>

</div>

<div id="toast">
    <div class="dot"></div>
    <span id="toastMsg"></span>
</div>

<script>
const API_URL = 'essay_extract_proxy.php';
let EXTRACTED = [];

// =========================================================
// Chuyển word form EN -> VI
// =========================================================

function translateWordForm(wordForm) {

    const map = {

        'noun': 'Danh từ',

        'verb': 'Động từ',

        'adjective': 'Tính từ',

        'adverb': 'Trạng từ',

        'phrasal verb': 'Cụm động từ',

        'idiom': 'Thành ngữ',

        'collocation': 'Cụm từ',

        'unknown': 'Không rõ'
    };

    return map[
        String(wordForm || '')
            .toLowerCase()
            .trim()
    ] || wordForm;
}

// =========================================================
// Escape HTML để tránh lỗi layout hoặc XSS nhẹ từ AI response
// =========================================================
function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

// =========================================================
// Kiểm tra AI proxy sống không
// =========================================================
async function checkAI() {
    const dot = document.getElementById('statusDot');
    const text = document.getElementById('statusText');

    try {
        const res = await fetch('essay_extract_proxy.php', {
            method: 'GET',
            cache: 'no-store'
        });

        // GET trả Method not allowed nghĩa là proxy PHP đang sống
        if (res.ok || res.status === 200 || res.status === 405) {
            dot.className = "status-dot status-online";
            text.innerText = "Sẵn sàng";
        } else {
            dot.className = "status-dot status-offline";
            text.innerText = "Mất kết nối";
        }
    } catch (e) {
        dot.className = "status-dot status-offline";
        text.innerText = "Mất kết nối";
    }
}

checkAI();

// =========================================================
// Toast thông báo
// =========================================================
function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    const text = document.getElementById('toastMsg');

    toast.className = type;
    text.innerText = msg;
    toast.style.display = "flex";

    setTimeout(() => {
        toast.style.display = "none";
    }, 3000);
}

// =========================================================
// Gọi proxy để extract vocabulary
// =========================================================
async function extractWords() {
    console.log("DEBUG: extractWords() started");

    const essay = document.getElementById('essayInput').value.trim();
    const req = document.getElementById('reqInput').value.trim();
    const btn = document.getElementById('extractBtn');

    if (!essay) {
        showToast("Vui lòng nhập bài viết!", "error");
        return;
    }

    btn.disabled = true;
    btn.innerText = "Đang trích xuất...";

    document.getElementById('loader').style.display = "block";
    document.getElementById('resultArea').style.display = "none";
    document.getElementById('debuggerArea').style.display = "none";
    document.getElementById('vocabList').innerHTML = "";

    try {
        console.log("DEBUG: calling proxy", API_URL);

        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            cache: 'no-store',
            body: JSON.stringify({
                essay: essay,
                requirement: req
            })
        });

        const rawText = await response.text();

        console.log("DEBUG response status:", response.status);
        console.log("DEBUG raw response:", rawText);

        let data;

        try {
            data = JSON.parse(rawText);
        } catch (e) {
            throw new Error("Proxy trả về JSON không hợp lệ: " + rawText);
        }

        if (!response.ok || data.error) {
            throw new Error(data.message || data.error || "Lỗi không xác định");
        }

        const wordsArray = data.words || [];
        EXTRACTED = Array.isArray(wordsArray) ? wordsArray : [];

        if (typeof data.requests_remaining !== 'undefined') {
            document.getElementById('usageInfo').innerHTML =
                `Remaining today: <strong>${data.requests_remaining}/15</strong>`;
        }

        renderWords(EXTRACTED);

    } catch (error) {
        console.error("DEBUG extract error:", error);

        document.getElementById('debuggerArea').style.display = "block";
        document.getElementById('debugMsg').innerText = "Lỗi: " + error.message;
        document.getElementById('debugRaw').innerText = error.stack || '';
    } finally {
        document.getElementById('loader').style.display = "none";
        btn.disabled = false;
        btn.innerText = "Trích xuất từ vựng";
    }
}

// =========================================================
// Render danh sách từ theo category
// =========================================================
function renderWords(words) {
    const resultArea = document.getElementById('resultArea');
    const vocabList = document.getElementById('vocabList');

    if (!Array.isArray(words) || words.length === 0) {
        vocabList.innerHTML =
            "<p style='color:#64748b;font-style:italic;'>Không tìm thấy từ vựng nào phù hợp.</p>";

        resultArea.style.display = "block";
        return;
    }

    const grouped = {};

    words.forEach((item, idx) => {
        if (typeof item !== 'object') {
            item = { word: String(item) };
        }

        item.__idx = idx;

        const cat = String(
            item.category || item.group || item.topic || "Uncategorized"
        ).trim();

        if (!grouped[cat]) {
            grouped[cat] = [];
        }

        grouped[cat].push(item);
    });

    let html = '';
    let blockDelay = 0;

    Object.keys(grouped).forEach(cat => {
        html += `
            <div class="category-block" style="animation-delay:${blockDelay}s;">
                <div class="category-title">📁 ${escapeHtml(cat)}</div>
                <div class="vocab-grid">
        `;

        grouped[cat].forEach(item => {
            html += `
                <div class="word-card">
                    <h3>${escapeHtml(item.word || 'N/A')}</h3>

                    <span class="word-ipa">
                        ${escapeHtml(item.ipa || '')}
                    </span>

                    <div class="word-form">
                        ${escapeHtml(translateWordForm(item.word_form || item.form || 'unknown'))}
                    </div>

                    <div class="word-def">
                        ${escapeHtml(item.definition_vi || item.definition_en || '')}
                    </div>

                    <div class="word-ex">
                        "${escapeHtml(item.example_sentence || item.example || '')}"
                    </div>

                    ${
                        item.nuance
                        ? `
                            <div class="word-extra">
                                <strong>Nuance:</strong> ${escapeHtml(item.nuance)}
                            </div>
                        `
                        : ''
                    }

                    ${
                        item.collocations
                        ? `
                            <div class="word-extra">
                                <strong>Collocations:</strong> ${escapeHtml(item.collocations)}
                            </div>
                        `
                        : ''
                    }

                    <button
                        class="btn-save-word"
                        data-idx="${item.__idx}"
                        data-cat="${escapeHtml(cat)}"
                        onclick="saveWordFromCard(event)"
                        type="button"
                    >
                        Lưu vào kho +
                    </button>
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;

        blockDelay += 0.15;
    });

    vocabList.innerHTML = html;
    resultArea.style.display = "block";
}

// =========================================================
// Đảm bảo category tồn tại, nếu chưa có thì tạo mới
// =========================================================
async function ensureCategoryId(categoryName) {
    if (!categoryName || categoryName.toLowerCase() === 'uncategorized') {
        return 'null';
    }

    try {
        let res = await fetch('category_actions.php', {
            cache: 'no-store'
        });

        let cats = await res.json();

        let found = cats.find(
            c => String(c.name).toLowerCase() === categoryName.toLowerCase()
        );

        if (found) {
            return found.id;
        }

        const fd = new FormData();
        fd.append('action', 'create');
        fd.append('name', categoryName);

        const createRes = await fetch('category_actions.php', {
            method: 'POST',
            body: fd
        });

        const text = (await createRes.text()).trim();

        if (text === 'Success') {
            res = await fetch('category_actions.php', {
                cache: 'no-store'
            });

            cats = await res.json();

            found = cats.find(
                c => String(c.name).toLowerCase() === categoryName.toLowerCase()
            );

            if (found) {
                return found.id;
            }
        }
    } catch (e) {
        console.error("DEBUG category error:", e);
    }

    return 'null';
}

// =========================================================
// Lưu 1 từ vào kho thông qua save.php
// =========================================================
async function saveWordFromCard(event) {
    const btn = event.currentTarget;
    const idx = parseInt(btn.dataset.idx, 10);
    const categoryName = btn.dataset.cat || "Uncategorized";

    const data = EXTRACTED[idx];

    if (!data) {
        showToast('Không tìm thấy dữ liệu!', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerText = "Đang lưu...";

    const category_id = await ensureCategoryId(categoryName);
    const payload = (typeof data === 'string') ? { word: data } : data;

    const formData = new FormData();

    formData.append('word', String(payload.word || '').trim());
    formData.append('ipa', String(payload.ipa || '').trim());
    formData.append('word_form', String(payload.word_form || payload.form || '').trim());
    formData.append('level', String(payload.level || '').trim());
    formData.append('definition_en', String(payload.definition_en || '').trim());
    formData.append('definition_vi', String(payload.definition_vi || '').trim());
    formData.append('example_sentence', String(payload.example_sentence || payload.example || '').trim());
    formData.append('synonyms', String(payload.synonyms || '').trim());
    formData.append('antonyms', String(payload.antonyms || '').trim());

    // Lưu thêm nuance vào board.php/export/docx
    formData.append('nuance', String(payload.nuance || '').trim());

    // Lưu thêm collocations vào board.php/export/docx
    formData.append('collocations', String(payload.collocations || '').trim());

    formData.append('category_id', category_id);

    try {
        const res = await fetch('save.php', {
            method: 'POST',
            body: formData
        });

        const text = (await res.text()).trim();

        if (text === 'Success') {
            btn.innerText = "Đã lưu ✓";
            btn.style.background = "var(--accent)";
            showToast("✅ Đã lưu từ!", "success");
        } else if (text === 'Duplicate') {
            btn.innerText = "Đã có trong kho";
            btn.style.background = "#64748b";
            showToast("⚠️ Từ đã tồn tại", "error");
        } else {
            btn.innerText = "Lỗi!";
            btn.style.background = "#ef4444";
            showToast("❌ Lỗi lưu: " + text, "error");
        }
    } catch (e) {
        btn.innerText = "Lỗi!";
        btn.style.background = "#ef4444";
        showToast("❌ Lỗi kết nối", "error");
    }
}
</script>

</body>
</html>
