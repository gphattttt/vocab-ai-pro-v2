<?php 
session_start(); 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); } 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Writing Coach | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#1e293b; --accent:#10b981; --danger:#ef4444; --warning:#f59e0b; --bg:#f8fafc; --border:#e2e8f0; --text-muted:#64748b; }
        body { font-family:'Be Vietnam Pro',sans-serif; background:var(--bg); margin:0; padding:20px; color:var(--primary); }
        .container { max-width:900px; margin:0 auto; }
        .card { background:white; padding:30px; border-radius:25px; border:1px solid var(--border); box-shadow:0 10px 30px rgba(0,0,0,0.03); margin-bottom:20px; }
        .title { font-size:1.5rem; font-weight:800; margin-bottom:20px; color:var(--primary); }
        textarea { width:100%; height:200px; padding:20px; border-radius:15px; border:2px solid var(--border); font-family:inherit; font-size:1rem; resize:vertical; box-sizing:border-box; }
        textarea:focus { outline:none; border-color:var(--accent); }
        input.req-input { width:100%; padding:15px; border-radius:15px; border:2px solid var(--border); font-family:inherit; font-size:1rem; margin-bottom:20px; box-sizing:border-box; }
        .btn-main { background:var(--primary); color:white; padding:18px 30px; border:none; border-radius:15px; font-weight:700; font-size:1.1rem; cursor:pointer; width:100%; transition:0.3s; }
        .btn-main:hover:not(:disabled){ transform:translateY(-2px); box-shadow:0 10px 20px rgba(0,0,0,0.1); }
        .btn-main:disabled { background:var(--text-muted); cursor:not-allowed; }

        #loader { display:none; text-align:center; padding:40px; font-weight:700; color:var(--accent); }
        #resultArea { display:none; }
        .section-title { font-weight:800; color:var(--accent); border-bottom:2px dashed var(--border); padding-bottom:10px; margin-top:30px; margin-bottom:20px; }
        .coaching-box { background:#f0fdf4; padding:20px; border-radius:15px; line-height:1.6; border:1px solid #dcfce7; }

        .category-block { margin-bottom:28px; }
        .category-title { font-weight:800; color:var(--primary); margin-bottom:12px; font-size:1.05rem; }
        .vocab-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:20px; }
        .word-card { background:white; border:1px solid var(--border); padding:20px; border-radius:15px; box-shadow:0 4px 6px rgba(0,0,0,0.02); }
        .word-card h3 { margin:0 0 5px 0; color:var(--accent); font-size:1.3rem; }
        .word-ipa { color:var(--text-muted); font-size:0.9rem; margin-bottom:10px; display:block; }
        .word-def { font-weight:600; margin-bottom:10px; }
        .word-ex { font-style:italic; color:#475569; font-size:0.95rem; }

        .btn-save-word { width:100%; padding:12px 16px; border-radius:14px; border:none; background:var(--primary); color:white; font-weight:800; font-size:0.95rem; cursor:pointer; transition:0.3s; margin-top:12px; }
        .btn-save-word:hover:not(:disabled){ transform:translateY(-2px); box-shadow:0 8px 18px rgba(0,0,0,0.08); }
        .btn-save-word:disabled { background:var(--text-muted); cursor:not-allowed; }

        #toast { position:fixed; bottom:24px; right:24px; z-index:3000; background:white; border:1px solid var(--border); padding:12px 16px; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,0.12); display:none; align-items:center; gap:10px; font-weight:700; }
        #toast.success { border-color:#86efac; } #toast.error { border-color:#fda4af; }
        #toast .dot { width:10px; height:10px; border-radius:50%; }
        #toast.success .dot { background:#10b981; } #toast.error .dot { background:#ef4444; }

        #debuggerArea { display:none; background:#0f172a; color:#e2e8f0; padding:20px; border-radius:15px; margin-top:20px; font-family:monospace; }
        .debug-title { color:var(--warning); font-weight:bold; margin-bottom:10px; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
    <div class="card">
        <div class="title">✨ AI Writing Coach & Vocab Extractor</div>
        <p style="color: var(--text-muted); margin-bottom: 20px;">Dán bài viết của bạn vào đây. AI sẽ nhận xét và trích xuất từ vựng nâng cao giúp bạn.</p>
        <textarea id="essayInput" placeholder="Ví dụ: I think that learning English is very important because..."></textarea>
        <input type="text" id="reqInput" class="req-input" value="Từ vựng cấp độ B1" placeholder="Yêu cầu: Từ vựng IELTS, Từ vựng C1...">
        <button id="extractBtn" class="btn-main" onclick="extractWords()">Phân tích với Gemini</button>
    </div>

    <div id="loader">Đang phân tích bài viết. Quá trình này có thể mất 10-15 giây... ⏳</div>

    <div id="resultArea" class="card">
        <div class="section-title">📝 Nhận xét từ AI Coach</div>
        <div id="coachingContent" class="coaching-box"></div>
        <div class="section-title">💎 Từ vựng nâng cao (theo Category)</div>
        <div id="vocabList"></div>
    </div>

    <div id="debuggerArea">
        <div class="debug-title">⚠️ BẢNG ĐIỀU KHIỂN LỖI (DEBUGGER)</div>
        <div id="debugMsg" style="margin-bottom: 15px; color: #ef4444;"></div>
        <pre id="debugRaw" style="background: #1e293b; padding: 15px; border-radius: 10px; overflow-x: auto; font-size: 0.8rem;"></pre>
    </div>
</div>

<div id="toast"><div class="dot"></div><span id="toastMsg"></span></div>

<script>
const API_URL = '/api/extract-vocab';
let EXTRACTED = [];

function showToast(msg, type='success'){
    const toast=document.getElementById('toast');
    const text=document.getElementById('toastMsg');
    toast.className=type; text.innerText=msg; toast.style.display="flex";
    setTimeout(()=>toast.style.display="none", 2500);
}

async function extractWords() {
    const essay = document.getElementById('essayInput').value.trim();
    const req = document.getElementById('reqInput').value.trim();
    const btn = document.getElementById('extractBtn');
    if (!essay) { alert("Vui lòng nhập bài viết!"); return; }

    btn.disabled = true;
    document.getElementById('loader').style.display = "block";
    document.getElementById('resultArea').style.display = "none";
    document.getElementById('debuggerArea').style.display = "none";
    document.getElementById('vocabList').innerHTML = "";
    document.getElementById('coachingContent').innerHTML = "";

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ essay: essay, requirement: req })
        });

        const textRaw = await response.text();
        let data;
        try { data = JSON.parse(textRaw); } 
        catch (e) { throw new Error("Lỗi JSON: " + textRaw); }

        if (!response.ok || data.error) throw new Error(data.error || `HTTP Error ${response.status}`);

        const coachingText = data.coaching || data.feedback || data.summary || "AI không tạo nhận xét.";
        document.getElementById('coachingContent').innerHTML = coachingText.replace(/\n/g, '<br>');

        const wordsArray = data.words || data.vocabulary || data.vocabList || [];
        EXTRACTED = Array.isArray(wordsArray) ? wordsArray : [];

        if (EXTRACTED.length === 0) {
            document.getElementById('vocabList').innerHTML = "<p>Không tìm thấy từ vựng nào phù hợp.</p>";
        } else {
            const grouped = {};
            EXTRACTED.forEach((item, idx) => {
                if (typeof item !== 'object') item = { word: String(item) };
                item.__idx = idx;
                const cat = (item.category || item.group || item.topic || "Uncategorized").trim();
                if (!grouped[cat]) grouped[cat] = [];
                grouped[cat].push(item);
            });

            let html = '';
            Object.keys(grouped).forEach(cat => {
                html += `<div class="category-block">
                            <div class="category-title">📁 ${cat}</div>
                            <div class="vocab-grid">`;
                grouped[cat].forEach(item => {
                    html += `
                    <div class="word-card">
                        <h3>${item.word || 'N/A'}</h3>
                        <span class="word-ipa">${item.ipa || ''}</span>
                        <div class="word-def">${item.definition_vi || item.definition_en || item.definition || item.meaning || ''}</div>
                        <div class="word-ex">${item.example_sentence || item.example || ''}</div>
                        <button class="btn-save-word" data-idx="${item.__idx}" data-cat="${cat.replace(/"/g,'&quot;')}" onclick="saveWordFromCard(event)">Lưu vào kho +</button>
                    </div>`;
                });
                html += `</div></div>`;
            });

            document.getElementById('vocabList').innerHTML = html;
        }

        document.getElementById('loader').style.display = "none";
        document.getElementById('resultArea').style.display = "block";

    } catch (error) {
        document.getElementById('loader').style.display = "none";
        document.getElementById('debuggerArea').style.display = "block";
        document.getElementById('debugMsg').innerText = "Lỗi: " + error.message;
        document.getElementById('debugRaw').innerText = error.stack || '';
    } finally {
        btn.disabled = false;
    }
}

async function ensureCategoryId(categoryName) {
    if (!categoryName || categoryName.toLowerCase() === 'uncategorized') return 'null';
    try {
        let res = await fetch('category_actions.php');
        let cats = await res.json();
        let found = cats.find(c => c.name.toLowerCase() === categoryName.toLowerCase());
        if (found) return found.id;

        const fd = new FormData();
        fd.append('action', 'create');
        fd.append('name', categoryName);
        const createRes = await fetch('category_actions.php', { method: 'POST', body: fd });
        const text = (await createRes.text()).trim();

        if (text === 'Success') {
            res = await fetch('category_actions.php');
            cats = await res.json();
            found = cats.find(c => c.name.toLowerCase() === categoryName.toLowerCase());
            if (found) return found.id;
        }
    } catch (e) {}
    return 'null';
}

async function saveWordFromCard(event) {
    const btn = event.currentTarget;
    const idx = parseInt(btn.dataset.idx, 10);
    const categoryName = btn.dataset.cat || "Uncategorized";
    const data = EXTRACTED[idx];
    if (!data) { showToast('Không tìm thấy dữ liệu!', 'error'); return; }

    btn.disabled = true; btn.innerText = "Đang lưu...";

    const category_id = await ensureCategoryId(categoryName);
    const payload = (typeof data === 'string') ? { word: data } : data;

    const formData = new FormData();
    formData.append('word', (payload.word || '').trim());
    formData.append('ipa', (payload.ipa || '').trim());
    formData.append('word_form', (payload.word_form || payload.form || '').trim());
    formData.append('level', (payload.level || '').trim());
    formData.append('definition_en', (payload.definition_en || '').trim());
    formData.append('definition_vi', (payload.definition_vi || '').trim());
    formData.append('example_sentence', (payload.example_sentence || payload.example || '').trim());
    formData.append('synonyms', (payload.synonyms || '').trim());
    formData.append('antonyms', (payload.antonyms || '').trim());
    formData.append('category_id', category_id);

    try {
        const res = await fetch('save.php', { method: 'POST', body: formData });
        const text = (await res.text()).trim();

        if (text === 'Success') {
            btn.innerText = "Đã lưu ✓"; btn.style.background = "var(--accent)";
            showToast("✅ Đã lưu từ!", "success");
        } else if (text === 'Duplicate') {
            btn.innerText = "Đã có trong kho"; btn.style.background = "#64748b";
            showToast("⚠️ Từ đã tồn tại", "error");
        } else {
            btn.innerText = "Lỗi!"; btn.style.background = "#ef4444";
            showToast("❌ Lỗi lưu: " + text, "error");
        }
    } catch (e) {
        btn.innerText = "Lỗi!"; btn.style.background = "#ef4444";
        showToast("❌ Lỗi kết nối", "error");
    }
}
</script>
</body>
</html>
