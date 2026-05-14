<?php
session_start();

// --- NEW FIX: Kick logged-out users back to the login page ---
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
    <title>AI Essay Extractor | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b; --danger: #ef4444;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); }
        .container { max-width: 900px; margin: 60px auto; }

        /* --- AI STATUS BADGE --- */
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 20px; font-size: 0.75rem;
            font-weight: 700; margin-bottom: 20px; border: 1px solid var(--border);
            background: white; transition: 0.3s;
        }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
        .status-online { background: var(--accent); box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
        .status-offline { background: var(--danger); box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); }

        /* --- UI ELEMENTS --- */
        .card { background: white; padding: 40px; border-radius: 30px; border: 1px solid var(--border); box-shadow: 0 10px 40px rgba(0,0,0,0.02); }
        
        textarea {
            width: 100%; border-radius: 20px; border: 2px solid var(--border);
            padding: 25px; font-family: inherit; font-size: 1.05rem; box-sizing: border-box;
            outline: none; transition: 0.3s; resize: vertical; min-height: 250px;
        }
        textarea:focus { border-color: var(--accent); background: #f0fdf4; }

        .controls { display: flex; gap: 15px; align-items: center; margin-top: 25px; flex-wrap: wrap; }
        
        select { padding: 12px 20px; border-radius: 12px; border: 1px solid var(--border); font-family: inherit; font-weight: 600; cursor: pointer; }

        .btn-extract {
            background: var(--primary); color: white; border: none; padding: 15px 35px;
            border-radius: 12px; cursor: pointer; font-weight: 800; transition: 0.3s var(--spring);
            display: flex; align-items: center; gap: 10px;
        }
        .btn-extract:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-extract:disabled { opacity: 0.6; cursor: not-allowed; }

        /* --- RESULTS --- */
        .result-item {
            background: white; padding: 20px; border-radius: 18px; border: 1px solid var(--border);
            margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;
            animation: slideIn 0.4s var(--spring) forwards;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        .tag { font-size: 0.65rem; background: #f1f5f9; padding: 4px 10px; border-radius: 8px; font-weight: 800; text-transform: uppercase; }
        .folder-tag { background: #ecfdf5; color: #059669; border: 1px solid #dcfce7; }

        .error-box {
            display: none; background: #fef2f2; color: #ef4444; padding: 15px 25px;
            border-radius: 14px; margin-top: 20px; font-weight: 600; border: 1px solid #fee2e2;
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="status-badge" id="aiStatus">
            <div class="status-dot" id="statusDot"></div>
            <span id="statusText">Checking AI Engine...</span>
        </div>

        <div class="card">
            <h1 style="margin: 0 0 10px 0; font-weight: 800; font-size: 2.2rem; letter-spacing: -1px;">Essay Extractor</h1>
            <p style="color: var(--text-muted); font-weight: 600; margin-bottom: 30px;">Identify and categorize advanced vocabulary from your writing instantly.</p>

            <textarea id="essayInput" placeholder="Paste your essay or article here..."></textarea>
            
            <div id="errorMessage" class="error-box"></div>

            <div class="controls">
                <div style="flex: 1; display: flex; align-items: center; gap: 15px;">
                    <label style="font-weight: 800; font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted);">Target:</label>
                    <select id="levelReq">
                        <option value="B2 level words">B2 (Upper Intermediate)</option>
                        <option value="C1 level words" selected>C1 (Advanced Intelligence)</option>
                        <option value="C2 level words">C2 (Mastery)</option>
                    </select>
                </div>
                <button class="btn-extract" onclick="extractWords()" id="extractBtn">
                    <span id="btnIcon">⚡</span> <span id="btnText">Analyze Essay</span>
                </button>
            </div>
        </div>

        <div id="resultsArea" style="margin-top: 50px; display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 id="resultCount" style="margin:0; font-weight: 800;">0 Words Found</h3>
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">Confirm and Save All</span>
            </div>
            
            <div id="wordsList"></div>
            
            <button class="btn-extract" id="saveAllBtn" 
                    style="background: var(--accent); width: 100%; margin-top: 20px; justify-content: center;" 
                    onclick="saveAllWords()">
                Confirm & Add to Vault ✓
            </button>
        </div>
    </div>

<script>
    // Updated to use the Nginx proxy path
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
                text.innerText = "AI Engine: Online";
            }
        } catch (e) {
            dot.className = "status-dot status-offline";
            text.innerText = "AI Engine: Offline (Check PM2 Status)";
        }
    }

    checkBackend();
    setInterval(checkBackend, 10000);

    // --- 2. ROBUST EXTRACTION LOGIC ---
    async function extractWords() {
        const essay = document.getElementById('essayInput').value.trim();
        const req = document.getElementById('levelReq').value;
        const btn = document.getElementById('extractBtn');
        const btnText = document.getElementById('btnText');
        const errorBox = document.getElementById('errorMessage');

        if (!essay) {
            showError("Please paste some text first.");
            return;
        }

        errorBox.style.display = "none";
        btn.disabled = true;
        btnText.innerText = "Processing Text...";
        document.getElementById('resultsArea').style.display = "none";

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 25000);

            const res = await fetch(`${API_URL}/extract-vocab`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ essay, requirement: req }),
                signal: controller.signal
            });

            if (!res.ok) throw new Error("AI Server returned an error.");

            extractedWords = await res.json();
            renderResults();
        } catch (e) {
            if (e.name === 'AbortError') {
                showError("Request timed out. The essay might be too long.");
            } else {
                showError("Cannot connect to AI. Ensure PM2 is running 'vocab-ai'.");
            }
            btn.disabled = false;
            btnText.innerText = "Analyze Essay";
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
        document.getElementById('resultCount').innerText = `${extractedWords.length} Intelligent Match(es)`;

        extractedWords.forEach((w, index) => {
            list.innerHTML += `
                <div class="result-item" style="animation-delay: ${index * 0.05}s">
                    <div>
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
                            <strong style="font-size: 1.2rem;">${w.word}</strong>
                            <span class="tag">${w.level}</span>
                        </div>
                        <div style="font-size: 0.9rem; color: #1e40af; font-weight: 600;">${w.definition_vi}</div>
                    </div>
                    <div class="tag folder-tag">Folder: ${w.suggested_category}</div>
                </div>
            `;
        });

        document.getElementById('resultsArea').style.display = "block";
        btn.disabled = false;
        btnText.innerText = "Analyze Essay";
    }

    async function saveAllWords() {
        const btn = document.getElementById('saveAllBtn');
        btn.innerText = "Building Vault...";
        btn.disabled = true;
        
        try {
            const res = await fetch('process_essay.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ words: extractedWords })
            });

            const result = await res.json();
            if (result.success) {
                btn.innerText = `Added ${result.count} Words ✓`;
                setTimeout(() => window.location.href = 'board.php', 1500);
            }
        } catch (e) {
            alert("Error saving to database.");
            btn.innerText = "Confirm & Add to Vault ✓";
            btn.disabled = false;
        }
    }
</script>
</body>
</html>
