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
        :root { --primary:#1e293b; --accent:#10b981; --danger:#ef4444; --warning:#f59e0b; --bg:#f8fafc; --border:#e2e8f0; --text-muted:#64748b; --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        body { font-family:'Be Vietnam Pro',sans-serif; background:var(--bg); margin:0; padding:40px 20px; color:var(--primary); overflow-x:hidden; }
        
        /* --- ANIMATIONS --- */
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes popIn { 0% { opacity: 0; transform: scale(0.95) translateY(10px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
        @keyframes pulseDot { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
        @keyframes pulseLoading { 0% { opacity: 0.6; transform: scale(0.98); } 50% { opacity: 1; transform: scale(1); } 100% { opacity: 0.6; transform: scale(0.98); } }
        @keyframes slideUpToast { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .container { max-width:900px; margin:0 auto; animation: fadeInDown 0.8s var(--spring) forwards; }
        
        /* Status Badge */
        .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; margin-bottom: 20px; border: 1px solid var(--border); background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: all 0.3s ease; }
        .status-badge:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.05); }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
        .status-online { background: var(--accent); animation: pulseDot 2s infinite; }
        .status-offline { background: var(--danger); box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); }

        .card { background:white; padding:30px; border-radius:25px; border:1px solid var(--border); box-shadow:0 10px 30px rgba(0,0,0,0.03); margin-bottom:20px; transition: all 0.3s ease; }
        .card:hover { box-shadow: 0 15px 40px rgba(0,0,0,0.05); }
        
        .title { font-size:1.8rem; font-weight:800; margin-bottom:10px; color:var(--primary); letter-spacing: -0.5px; }
        
        textarea { width:100%; height:200px; padding:20px; border-radius:15px; border:2px solid var(--border); font-family:inherit; font-size:1rem; resize:vertical; box-sizing:border-box; transition: 0.3s var(--spring); background: #f8fafc; }
        textarea:focus { outline:none; border-color:var(--accent); background: white; box-shadow: 0 10px 25px rgba(16,185,129,0.1); transform: translateY(-2px); }
        
        input.req-input { width:100%; padding:15px; border-radius:15px; border:2px solid var(--border); font-family:inherit; font-size:1rem; margin-bottom:20px; box-sizing:border-box; transition: 0.3s var(--spring); }
        input.req-input:focus { outline:none; border-color:var(--accent); box-shadow: 0 5px 15px rgba(16,185,129,0.1); }
        
        .btn-main { background:var(--primary); color:white; padding:18px 30px; border:none; border-radius:15px; font-weight:800; font-size:1.1rem; cursor:pointer; width:100%; transition:all 0.3s var(--spring); }
        .btn-main:hover:not(:disabled){ transform:translateY(-3px); box-shadow:0 12px 25px rgba(30,41,59,0.2); background: #0f172a; }
        .btn-main:active:not(:disabled){ transform:translateY(1px); }
        .btn-main:disabled { background:var(--text-muted); cursor:not-allowed; opacity: 0.7; }

        #loader { display:none; text-align:center; padding:40px; font-weight:800; color:var(--accent); font-size: 1.1rem; animation: pulseLoading 1.5s infinite ease-in-out; }
        
        #resultArea { display:none; animation: slideUp 0.6s var(--spring) forwards; }
        .section-title { font-weight:800; color:var(--accent); border-bottom:2px dashed var(--border); padding-bottom:10px; margin-top:30px; margin-bottom:20px; font-size: 1.2rem; }
        
        .coaching-box { background:#f0fdf4; padding:25px; border-radius:18px; line-height:1.6; border:1px solid #dcfce7; color: #166534; font-size: 1.05rem; transition: 0.3s; }
        .coaching-box:hover { transform: translateX(5px); box-shadow: 0 10px 20px rgba(22, 101, 52, 0.05); }

        .category-block { margin-bottom:35px; animation: popIn 0.5s var(--spring) forwards; opacity: 0; }
        .category-title { font-weight:800; color:var(--primary); margin-bottom:15px; font-size:1.2rem; display: flex; align-items: center; gap: 8px; }
        
        .vocab-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:20px; }
        .word-card { background:white; border:1px solid var(--border); padding:24px; border-radius:20px; box-shadow:0 4px 6px rgba(0,0,0,0.02); transition: all 0.3s var(--spring); display: flex; flex-direction: column; }
        .word-card:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 15px 30px rgba(0,0,0,0.06); border-color: #cbd5e1; }
        
        .word-card h3 { margin:0 0 5px 0; color:var(--accent); font-size:1.4rem; font-weight: 800; }
        .word-ipa { color:var(--text-muted); font-size:0.9rem; margin-bottom:12px; display:block; background: #f1f5f9; padding: 4px 8px; border-radius: 8px; width: fit-content; font-family: monospace; }
        .word-def { font-weight:700; margin-bottom:12px; color: var(--primary); line-height: 1.4; flex-grow: 1; }
        .word-ex { font-style:italic; color:#475569; font-size:0.95rem; line-height: 1.5; margin-bottom: 20px; border-left: 3px solid var(--border); padding-left: 10px; }

        .btn-save-word { width:100%; padding:14px 16px; border-radius:14px; border:none; background:var(--primary); color:white; font-weight:800; font-size:0.95rem; cursor:pointer; transition:all 0.3s var(--spring); margin-top: auto; }
        .btn-save-word:hover:not(:disabled){ transform:translateY(-2px); box-shadow:0 8px 20px rgba(30,41,59,0.2); }
        .btn-save-word:active:not(:disabled){ transform:translateY(1px); }
        .btn-save-word:disabled { background:var(--text-muted); cursor:not-allowed; }

        #toast { position:fixed; bottom:24px; right:24px; z-index:3000; background:white; border:1px solid var(--border); padding:14px 20px; border-radius:16px; box-shadow:0 15px 40px rgba(0,0,0,0.15); display:none; align-items:center; gap:12px; font-weight:700; font-size:0.95rem; animation: slideUpToast 0.4s var(--spring); }
        #toast.success { border-color:#86efac; } #toast.error { border-color:#fda4af; }
        #toast .dot { width:12px; height:12px; border-radius:50%; }
        #toast.success .dot { background:#10b981; } #toast.error .dot { background:#ef4444; }

        #debuggerArea { display:none; background:#0f172a; color:#e2e8f0; padding:20px; border-radius:18px; margin-top:20px; font-family:monospace; animation: popIn 0.4s ease; border: 2px solid #1e293b; }
        .debug-title { color:var(--warning); font-weight:bold; margin-bottom:10px; font-size: 1.1rem; }
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
        <div class="title">✨ AI Writing Coach & Extractor</div>
        <p style="color: var(--text-muted); margin-bottom: 25px; font-size: 0.95rem;">Dán bài viết của bạn vào đây. AI sẽ nhận xét và tự động trích xuất các từ vựng nâng cao.</p>
        <textarea id="essayInput" placeholder="Ví dụ: I think that learning English is very important because..."></textarea>
        <input type="text" id="reqInput" class="req-input" value="Từ vựng cấp độ B1" placeholder="Yêu cầu: Từ vựng IELTS, Từ vựng C1, Collocations...">
        <button id="extractBtn" class="btn-main" onclick="extractWords()">Phân tích với AI</button>
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
        <pre id="debugRaw" style="background: #1e293b; padding: 15px; border-radius: 10px; overflow-x: auto; font-size: 0.85rem; color: #94a3b8;"></pre>
    </div>
</div>

<div id="toast"><div class="dot"></div><span id="toastMsg"></span></div>

<script>
const API_URL = '/api/extract-vocab';
let EXTRACTED = [];

async function checkAI() {
    const dot = document.getElementById('statusDot');
    const text = document.getElementById('statusText');
    try {
        const res = await fetch('/api/health');
        if (res.ok) { dot.className = "status-dot status-online"; text.innerText = "Sẵn sàng"; }
    } catch (e) { dot.className = "status-dot status-offline"; text.innerText = "Mất kết nối"; }
}
checkAI();

function showToast(msg, type='success'){
    const toast=document.getElementById('toast');
    const text=document.getElementById('toastMsg');
    toast.className=type; text.innerText=msg; 
    
    // Reset animation
    toast.style.animation = 'none';
    toast.offsetHeight; /* trigger reflow */
    toast.style.animation = 'slideUpToast 0.4s var(--spring) forwards';
    toast.style.display="flex";
    
    setTimeout(()=> {
        toast.style.opacity = '0';
        setTimeout(()=> { toast.style.display="none"; toast.style.opacity = '1'; }, 300);
    }, 3000);
}

async function extractWords() {
    const essay = document.getElementById('essayInput').value.trim();
    const req = document.getElementById('reqInput').value.trim();
    const btn = document.getElementById('extractBtn');
    if (!essay) { showToast("Vui lòng nhập bài viết!", "error"); return; }

    btn.disabled = true;
    btn.innerText = "Đang phân tích...";
    document.getElementById('loader').style.display = "block";
    
    const resultArea = document.getElementById('resultArea');
    resultArea.style.display = "none";
    resultArea.style.animation = "none"; // reset animation

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
            document.getElementById('vocabList').innerHTML = "<p style='color: var(--text-muted); font-style: italic;'>Không tìm thấy từ vựng nào phù hợp.</p>";
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
            let blockDelay = 0;
            Object.keys(grouped).forEach(cat => {
                html += `<div class="category-block" style="animation-delay: ${blockDelay}s;">
                            <div class="category-title">📁 ${cat}</div>
                            <div class="vocab-grid">`;
                grouped[cat].forEach(item => {
                    html += `
                    <div class="word-card">
                        <h3>${item.word || 'N/A'}</h3>
                        <span class="word-ipa">${item.ipa || ''}</span>
                        <div class="word-def">${item.definition_vi || item.definition_en || item.definition || item.meaning || ''}</div>
                        <div class="word-ex">"${item.example_sentence || item.example || ''}"</div>
                        <button class="btn-save-word" data-idx="${item.__idx}" data-cat="${cat.replace(/"/g,'&quot;')}" onclick="saveWordFromCard(event)">Lưu vào kho +</button>
                    </div>`;
                });
                html += `</div></div>`;
                blockDelay += 0.15; // Staggered animation cho từng category block
            });

            document.getElementById('vocabList').innerHTML = html;
        }

        document.getElementById('loader').style.display = "none";
        
        resultArea.style.display = "block";
        resultArea.offsetHeight; // trigger reflow
        resultArea.style.animation = "slideUp 0.6s var(--spring) forwards";

    } catch (error) {
        document.getElementById('loader').style.display = "none";
        document.getElementById('debuggerArea').style.display = "block";
        document.getElementById('debugMsg').innerText = "Lỗi: " + error.message;
        document.getElementById('debugRaw').innerText = error.stack || '';
    } finally {
        btn.disabled = false;
        btn.innerText = "Phân tích với AI";
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
    
    // Nút bấm xẹp xuống nhẹ khi ấn
    btn.style.transform = "scale(0.95)";

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

        btn.style.transform = "scale(1)"; // Trả lại kích thước gốc

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
        btn.style.transform = "scale(1)";
        btn.innerText = "Lỗi!"; btn.style.background = "#ef4444";
        showToast("❌ Lỗi kết nối", "error");
    }
}
</script>
</body>
</html>
