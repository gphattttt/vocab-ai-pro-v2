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
    <title>Bảng điều khiển | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
    font-family: 'Be Vietnam Pro', sans-serif; /* Changed from 'Inter' to 'Be Vietnam Pro' */
    background: var(--bg); 
    margin: 0; 
    color: var(--primary);
    overflow-x: hidden; 
}
        .main-content { padding: 40px; max-width: 800px; margin: 0 auto; min-height: 100vh; box-sizing: border-box; width: 100%; }

        /* --- ENTRANCE ANIMATIONS --- */
        @keyframes springUp {
            0% { opacity: 0; transform: scale(0.9) translateY(40px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes toastIn {
            0% { transform: translate(-50%, 100px); opacity: 0; }
            100% { transform: translate(-50%, 0); opacity: 1; }
        }

        .animate-spring { animation: springUp 0.7s var(--spring) forwards; }

        /* --- HEADER TEXT --- */
        .page-title { font-size: 3rem; font-weight: 800; margin-bottom: 10px; letter-spacing: -1px; }
        .page-subtitle { color: var(--text-muted); font-weight: 500; font-size: 1.1rem; margin-top: 0; }

        /* --- SEARCH BOX --- */
        .search-area { 
            background: white; padding: 10px; border-radius: 24px; 
            border: 1px solid var(--border); display: flex; gap: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03); margin-top: 40px; transition: 0.3s var(--spring);
        }
        .search-area:focus-within { transform: translateY(-3px); border-color: var(--accent); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.1); }
        
        .search-area input { 
            flex: 1; border: none; padding: 15px 20px; font-size: 1rem; /* 1rem prevents iOS auto-zoom */
            font-family: inherit; outline: none; border-radius: 18px; min-width: 0;
        }
        
        .btn-search { 
            background: var(--primary); color: white; border: none; 
            padding: 0 35px; border-radius: 18px; font-weight: 800; font-size: 1rem;
            cursor: pointer; transition: 0.3s var(--spring); white-space: nowrap; flex-shrink: 0;
        }
        .btn-search:hover { background: var(--accent); transform: scale(1.03); }

        /* --- SKELETON LOADER (PULSING) --- */
        #skeletonBox { display: none; margin-top: 30px; background: white; padding: 40px; border-radius: 32px; border: 1px solid var(--border); box-sizing: border-box;}
        .skeleton-line { 
            height: 20px; background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%; animation: skeleton-move 1.5s infinite;
            border-radius: 8px; margin-bottom: 15px;
        }
        @keyframes skeleton-move { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        /* --- RESULT CARD --- */
        #resultBox { display: none; margin-top: 30px; }
        .vocab-card { 
            background: white; padding: 45px; border-radius: 35px; 
            border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            position: relative; overflow: hidden; box-sizing: border-box;
        }

        .level-badge { position: absolute; top: 40px; right: 40px; background: #ecfdf5; color: #10b981; padding: 8px 16px; border-radius: 14px; font-weight: 900; font-size: 0.85rem; letter-spacing: 1px; }
        .word-title { font-size: 3.5rem; font-weight: 800; margin: 0; letter-spacing: -1.5px; word-break: break-word; padding-right: 60px; line-height: 1.1; }
        .ipa { font-family: 'Courier New', monospace; color: var(--text-muted); font-size: 1.2rem; margin-top: 10px; margin-bottom: 30px; display: block; font-weight: 500; }
        
        .def-container { margin-bottom: 30px; padding-left: 20px; border-left: 4px solid var(--accent); }
        .def-en { font-size: 1.15rem; font-weight: 600; margin-bottom: 10px; line-height: 1.5; color: var(--primary); }
        .def-vi { color: #1e40af; font-size: 1rem; font-weight: 600; background: #eff6ff; padding: 6px 12px; border-radius: 8px; display: inline-block; line-height: 1.4; }

        /* --- SYNONYMS & ANTONYMS --- */
        .extra-info { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; text-align: left; }
        .info-section { flex: 1; min-width: 140px; }
        .tag-label { font-size: 0.75rem; text-transform: uppercase; display: block; margin-bottom: 8px; color: var(--text-muted); font-weight: 800; letter-spacing: 0.05em; }
        .info-tag { padding: 12px 16px; border-radius: 14px; font-size: 0.9rem; font-weight: 600; line-height: 1.4; }
        .tag-syn { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .tag-ant { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

        .example-quote { background: #f8fafc; padding: 25px 30px; border-radius: 20px; color: #334155; line-height: 1.6; font-size: 1.05rem; position: relative; font-weight: 500; font-style: italic; }
        .example-quote::before { content: '"'; position: absolute; left: 12px; top: 10px; font-size: 3rem; opacity: 0.1; font-family: Georgia, serif; line-height: 1; }

        .btn-save { 
            width: 100%; margin-top: 30px; padding: 20px; border-radius: 18px; 
            background: var(--primary); color: white; border: none; font-weight: 800; 
            font-size: 1.1rem; cursor: pointer; transition: 0.3s var(--spring);
            display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-save:hover { transform: translateY(-4px); background: var(--accent); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.2); }

        /* --- POPUP TOAST --- */
        #saveToast {
            position: fixed; bottom: 40px; left: 50%; transform: translateX(-50%);
            background: var(--primary); color: white; padding: 16px 24px; border-radius: 50px;
            font-weight: 600; display: none; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            align-items: center; gap: 12px; white-space: nowrap; font-size: 0.95rem;
        }

        /* --- MOBILE RESPONSIVENESS --- */
        @media (max-width: 768px) {
            .main-content { padding: 20px; padding-top: 40px; }
            .page-title { font-size: 2.2rem; }
            .page-subtitle { font-size: 1rem; }
            
            .search-area { flex-direction: column; padding: 15px; border-radius: 20px; }
            .search-area input { padding: 10px; text-align: center; }
            .btn-search { padding: 15px; width: 100%; border-radius: 12px; }
            
            .vocab-card { padding: 25px; border-radius: 24px; }
            .level-badge { top: 25px; right: 25px; padding: 6px 12px; font-size: 0.75rem; }
            .word-title { font-size: 2.2rem; padding-right: 0; margin-top: 35px; } /* Margin top to clear badge if word is long */
            .ipa { font-size: 1.1rem; margin-bottom: 20px; }
            
            #skeletonBox { padding: 25px; border-radius: 24px; }
            .example-quote { padding: 20px; font-size: 1rem; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="main-content">
        <div class="animate-spring" style="text-align: center; margin-top: 20px;">
            <h1 class="page-title">Mở rộng kho từ vựng.</h1>
            <p class="page-subtitle">Thông minh tức thì, trợ lực bởi Gemini 2.5</p>
        </div>

        <div class="search-area animate-spring" style="animation-delay: 0.1s;">
            <input type="text" id="wordInput" placeholder="Nhập một từ tiếng Anh để khám phá..." onkeypress="if(event.key === 'Enter') startSearch()">
            <button class="btn-search" onclick="startSearch()" id="searchBtn">Khám phá</button>
        </div>

        <div id="skeletonBox">
            <div class="skeleton-line" style="width: 50%; height: 40px;"></div>
            <div class="skeleton-line" style="width: 30%;"></div>
            <div class="skeleton-line" style="width: 100%; height: 80px; margin-top: 20px;"></div>
            <div class="skeleton-line" style="width: 100%; height: 60px;"></div>
        </div>

        <div id="resultBox">
            <div class="vocab-card" id="mainCard">
                <div class="level-badge" id="resLevel">B2</div>
                <h2 class="word-title" id="resWord">Word</h2>
                <span class="ipa" id="resIpa">/phonetic/</span>
                
                <div class="def-container">
                    <div class="def-en" id="resDefEn">English Definition</div>
                    <div class="def-vi" id="resDefVi">Bản dịch tiếng Việt</div>
                </div>

                <div class="extra-info">
                    <div class="info-section">
                        <span class="tag-label">Từ đồng nghĩa</span>
                        <div id="resSynonyms" class="info-tag tag-syn">Không có</div>
                    </div>
                    <div class="info-section">
                        <span class="tag-label">Từ trái nghĩa</span>
                        <div id="resAntonyms" class="info-tag tag-ant">Không có</div>
                    </div>
                </div>

                <div class="example-quote">
                    <span id="resEx">"Một câu ví dụ phong phú ngữ cảnh từ AI."</span>
                </div>

                <button class="btn-save" id="saveBtn" onclick="saveToVault()">
                    Thêm vào kho từ vựng +
                </button>
            </div>
        </div>
    </div>

    <div id="saveToast">
        <span style="background: var(--accent); border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">✓</span>
        Đã lưu thành công! (+10 XP)
    </div>

<script>
let activeWordData = null;

async function startSearch() {
    const word = document.getElementById('wordInput').value.trim();
    if (!word) return;

    const resultBox = document.getElementById('resultBox');
    const skeleton = document.getElementById('skeletonBox');
    const searchBtn = document.getElementById('searchBtn');

    // UI Reset
    resultBox.style.display = "none";
    skeleton.style.display = "block";
    searchBtn.innerText = "Đang xử lý...";
    searchBtn.disabled = true;

    try {
        // 1. CACHE CHECK (Fast Path)
        const cacheRes = await fetch(`check_cache.php?word=${word}`);
        const cacheData = await cacheRes.json();

        if (cacheData.exists) {
            renderResult(cacheData.data);
        } else {
            // 2. AI FETCH (Slow Path) - Updated for study4ever.site via Nginx Proxy
            const aiRes = await fetch(`/api/get-vocab?word=${word}`);
            const aiData = await aiRes.json();
            renderResult(aiData);
        }
    } catch (e) {
        alert("Mất kết nối. Vui lòng kiểm tra lại dịch vụ Python AI.");
        searchBtn.innerText = "Khám phá";
        searchBtn.disabled = false;
    } finally {
        skeleton.style.display = "none";
    }
}

function renderResult(data) {
    activeWordData = data;
    document.getElementById('resWord').innerText = data.word;
    document.getElementById('resIpa').innerText = data.ipa;
    document.getElementById('resLevel').innerText = data.level;
    document.getElementById('resDefEn').innerText = data.definition_en;
    document.getElementById('resDefVi').innerText = data.definition_vi;
    document.getElementById('resEx').innerText = `"${data.example_sentence}"`;
    
    // Fill Synonyms and Antonyms (Handle null/empty responses elegantly)
    document.getElementById('resSynonyms').innerText = data.synonyms && data.synonyms !== "None" ? data.synonyms : "Không tìm thấy";
    document.getElementById('resAntonyms').innerText = data.antonyms && data.antonyms !== "None" ? data.antonyms : "Không tìm thấy";
    
    const resultBox = document.getElementById('resultBox');
    resultBox.style.display = "block";
    
    // Trigger Spring Animation
    const card = document.getElementById('mainCard');
    card.style.animation = 'none';
    card.offsetHeight; /* trigger reflow */
    card.style.animation = "springUp 0.8s var(--spring) forwards";

    const searchBtn = document.getElementById('searchBtn');
    searchBtn.innerText = "Khám phá";
    searchBtn.disabled = false;
    
    // Reset the save button state if previously saved
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.innerText = "Thêm vào kho từ vựng +";
    saveBtn.style.background = "var(--primary)";
    saveBtn.disabled = false;
}

async function saveToVault() {
    if (!activeWordData) return;
    const saveBtn = document.getElementById('saveBtn');
    const toast = document.getElementById('saveToast');
    
    saveBtn.innerText = "Đang lưu...";
    saveBtn.disabled = true;
    
    const formData = new FormData();
    Object.keys(activeWordData).forEach(key => formData.append(key, activeWordData[key]));
    
    try {
        const res = await fetch('save.php', { method: 'POST', body: formData });
        const resultText = await res.text();
        
        if (resultText.trim() === "Success") {
            saveBtn.innerText = "Đã lưu vào kho ✓";
            saveBtn.style.background = "var(--accent)";
            
            // SHOW SUCCESS POPUP WITH GAMIFICATION XP MESSAGE
            toast.style.display = "flex";
            toast.style.animation = "toastIn 0.5s var(--spring) forwards";
            
            setTimeout(() => {
                toast.style.display = "none";
            }, 3500);
            
        } else if (resultText.trim() === "Duplicate") {
            saveBtn.innerText = "Từ này đã có trong kho";
            saveBtn.style.background = "#64748b";
        } else {
            saveBtn.innerText = "Lỗi khi lưu";
            saveBtn.disabled = false;
        }
    } catch (e) {
        saveBtn.innerText = "Lỗi kết nối";
        saveBtn.disabled = false;
    }
}
</script>
</body>
</html>
