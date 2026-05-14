<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vocab AI Pro | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; color: var(--primary); overflow-x: hidden; }
        .main-content { padding: 40px; max-width: 800px; margin: 0 auto; min-height: 100vh; }

        /* --- ENTRANCE ANIMATIONS --- */
        @keyframes springUp {
            0% { opacity: 0; transform: scale(0.9) translateY(40px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes toastIn {
            0% { transform: translateY(100px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        .animate-spring { animation: springUp 0.7s var(--spring) forwards; }

        /* --- SEARCH BOX --- */
        .search-area { 
            background: white; padding: 8px; border-radius: 24px; 
            border: 1px solid var(--border); display: flex; gap: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03); margin-top: 40px;
            transition: 0.3s var(--spring);
        }
        .search-area:focus-within { transform: translateY(-3px); border-color: var(--accent); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.1); }
        
        .search-area input { 
            flex: 1; border: none; padding: 15px 25px; font-size: 1.1rem; 
            font-family: inherit; outline: none; border-radius: 18px;
        }
        
        .btn-search { 
            background: var(--primary); color: white; border: none; 
            padding: 0 35px; border-radius: 18px; font-weight: 800; 
            cursor: pointer; transition: 0.3s var(--spring);
        }
        .btn-search:hover { background: var(--accent); transform: scale(1.05); }

        /* --- SKELETON LOADER (PULSING) --- */
        #skeletonBox { display: none; margin-top: 30px; background: white; padding: 40px; border-radius: 32px; border: 1px solid var(--border); }
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
            position: relative; overflow: hidden;
        }

        .level-badge { position: absolute; top: 40px; right: 40px; background: #ecfdf5; color: #10b981; padding: 8px 16px; border-radius: 14px; font-weight: 900; font-size: 0.85rem; letter-spacing: 1px; }
        .word-title { font-size: 3.5rem; font-weight: 800; margin: 0; letter-spacing: -2px; }
        .ipa { font-family: 'Courier New', monospace; color: var(--text-muted); font-size: 1.3rem; margin-bottom: 30px; display: block; opacity: 0.7; }
        
        .def-container { margin-bottom: 25px; padding-left: 25px; border-left: 5px solid var(--accent); }
        .def-en { font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; line-height: 1.4; }
        .def-vi { color: #1e40af; font-size: 1rem; font-weight: 600; background: #eff6ff; padding: 4px 10px; border-radius: 8px; display: inline-block; }

        /* --- SYNONYMS & ANTONYMS --- */
        .extra-info { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; text-align: left; }
        .info-section { flex: 1; min-width: 140px; }
        .tag-label { font-size: 0.65rem; text-transform: uppercase; display: block; margin-bottom: 8px; color: var(--text-muted); font-weight: 800; letter-spacing: 0.05em; }
        .info-tag { padding: 10px 14px; border-radius: 14px; font-size: 0.85rem; font-weight: 700; line-height: 1.4; }
        .tag-syn { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .tag-ant { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

        .example-quote { background: #f8fafc; padding: 30px; border-radius: 24px; color: #334155; line-height: 1.6; font-size: 1.05rem; position: relative; }
        .example-quote::before { content: '"'; position: absolute; left: 10px; top: 0; font-size: 4rem; opacity: 0.05; font-family: serif; }

        .btn-save { 
            width: 100%; margin-top: 30px; padding: 22px; border-radius: 20px; 
            background: var(--primary); color: white; border: none; font-weight: 800; 
            font-size: 1.1rem; cursor: pointer; transition: 0.4s var(--spring);
        }
        .btn-save:hover { transform: translateY(-5px); background: var(--accent); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.2); }

        /* --- POPUP TOAST --- */
        #saveToast {
            position: fixed; bottom: 40px; left: 50%; transform: translateX(-50%);
            background: var(--primary); color: white; padding: 16px 32px; border-radius: 50px;
            font-weight: 700; display: none; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            align-items: center; gap: 12px;
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="main-content">
        <div class="animate-spring" style="text-align: center; margin-top: 60px;">
            <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 10px; letter-spacing: -1px;">Expand Your Vault.</h1>
            <p style="color: var(--text-muted); font-weight: 600; font-size: 1.1rem;">Instant intelligence, powered by Gemini 2.5</p>
        </div>

        <div class="search-area animate-spring" style="animation-delay: 0.1s;">
            <input type="text" id="wordInput" placeholder="Enter a word to explore..." onkeypress="if(event.key === 'Enter') startSearch()">
            <button class="btn-search" onclick="startSearch()" id="searchBtn">Discover</button>
        </div>

        <div id="skeletonBox">
            <div class="skeleton-line" style="width: 50%; height: 50px;"></div>
            <div class="skeleton-line" style="width: 30%;"></div>
            <div class="skeleton-line" style="width: 100%; height: 100px; margin-top: 20px;"></div>
            <div class="skeleton-line" style="width: 100%; height: 60px;"></div>
        </div>

        <div id="resultBox">
            <div class="vocab-card" id="mainCard">
                <div class="level-badge" id="resLevel">B2</div>
                <h2 class="word-title" id="resWord">Word</h2>
                <span class="ipa" id="resIpa">/phonetic/</span>
                
                <div class="def-container">
                    <div class="def-en" id="resDefEn">English Definition</div>
                    <div class="def-vi" id="resDefVi">Bản dịch</div>
                </div>

                <div class="extra-info">
                    <div class="info-section">
                        <span class="tag-label">Synonyms</span>
                        <div id="resSynonyms" class="info-tag tag-syn">None</div>
                    </div>
                    <div class="info-section">
                        <span class="tag-label">Antonyms</span>
                        <div id="resAntonyms" class="info-tag tag-ant">None</div>
                    </div>
                </div>

                <div class="example-quote">
                    <span id="resEx">"A contextually rich example sentence from the AI."</span>
                </div>

                <button class="btn-save" id="saveBtn" onclick="saveToVault()">
                    Add to Personal Vault +
                </button>
            </div>
        </div>
    </div>

    <div id="saveToast">
        <span style="background: var(--accent); border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">✓</span>
        Word added to your collection!
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
    searchBtn.innerText = "...";
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
        alert("Connection lost. Please restart your Python app.py");
        searchBtn.innerText = "Discover";
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
    
    // Fill Synonyms and Antonyms
    document.getElementById('resSynonyms').innerText = data.synonyms || "None found";
    document.getElementById('resAntonyms').innerText = data.antonyms || "None found";
    
    const resultBox = document.getElementById('resultBox');
    resultBox.style.display = "block";
    
    // Trigger Spring Animation
    const card = document.getElementById('mainCard');
    card.style.animation = 'none';
    card.offsetHeight; /* trigger reflow */
    card.style.animation = "springUp 0.8s var(--spring) forwards";

    const searchBtn = document.getElementById('searchBtn');
    searchBtn.innerText = "Discover";
    searchBtn.disabled = false;
    
    // Reset the save button state if previously saved
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.innerText = "Add to Personal Vault +";
    saveBtn.style.background = "var(--primary)";
}

async function saveToVault() {
    if (!activeWordData) return;
    const saveBtn = document.getElementById('saveBtn');
    const toast = document.getElementById('saveToast');
    
    saveBtn.innerText = "Processing...";
    
    const formData = new FormData();
    Object.keys(activeWordData).forEach(key => formData.append(key, activeWordData[key]));

    const res = await fetch('save.php', { method: 'POST', body: formData });
    const resultText = await res.text();
    
    if (resultText.trim() === "Success") {
        saveBtn.innerText = "Successfully Vaulted ✓";
        saveBtn.style.background = "var(--accent)";
        
        // SHOW SUCCESS POPUP
        toast.style.display = "flex";
        toast.style.animation = "toastIn 0.5s var(--spring) forwards";
        
        setTimeout(() => {
            toast.style.display = "none";
        }, 3000);
    } else if (resultText.trim() === "Duplicate") {
        saveBtn.innerText = "Already in Vault";
        saveBtn.style.background = "#64748b";
    } else {
        saveBtn.innerText = "Error Saving";
    }
}
</script>
</body>
</html>
