<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vault & Folders | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b; --danger: #ef4444;
            --sidebar-width: 300px;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; color: var(--primary); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* --- SIDEBAR --- */
        .sidebar { 
            width: var(--sidebar-width); background: white; border-right: 1px solid var(--border); 
            padding: 40px 25px; position: fixed; top: 0; left: 0; height: 100vh; box-sizing: border-box;
            display: flex; flex-direction: column; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 100;
        }
        .sidebar.collapsed { transform: translateX(-100%); }

        .sidebar-toggle {
            position: absolute; right: -24px; top: 100px;
            width: 24px; height: 60px; background: white; border: 1px solid var(--border); 
            border-left: none; border-radius: 0 12px 12px 0; display: flex; 
            align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05); transition: 0.3s; z-index: 110;
            color: var(--text-muted);
        }
        .sidebar-toggle:hover { background: var(--accent); color: white; border-color: var(--accent); }
        .sidebar-toggle::after { content: "❮"; transition: 0.3s; font-size: 0.8rem; font-weight: 800; }
        .sidebar.collapsed .sidebar-toggle::after { content: "❯"; }

        /* --- MAIN CONTENT --- */
        .main-content { 
            flex: 1; margin-left: var(--sidebar-width); transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            padding: 40px; padding-top: 80px; min-width: 0;
        }
        .sidebar.collapsed + .main-content { margin-left: 0; }
        .container { max-width: 1000px; margin: 0 auto; }

        /* --- FOLDERS --- */
        .folder-list { margin-top: 30px; flex: 1; overflow-y: auto; }
        .folder-item { 
            padding: 12px 18px; border-radius: 12px; cursor: pointer; transition: 0.2s;
            font-weight: 700; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 5px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .folder-item:hover { background: #f1f5f9; color: var(--primary); }
        .folder-item.active { background: var(--primary); color: white; }
        
        .new-folder-box { margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border); }
        .folder-input { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 10px; box-sizing: border-box; }
        
        /* --- HEADER & CARDS --- */
        .header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; gap: 20px; flex-wrap: wrap; }
        .search-bar { flex: 1; min-width: 200px; padding: 12px 20px; border-radius: 14px; border: 2px solid var(--border); outline: none; }
        
        .btn-toggle { background: white; border: 1px solid var(--border); padding: 10px 15px; border-radius: 12px; cursor: pointer; font-weight: 700; transition: 0.2s; }
        .btn-toggle.active { background: var(--primary); color: white; border-color: var(--primary); }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .word-card { background: white; border-radius: 24px; padding: 25px; border: 1px solid var(--border); position: relative; cursor: pointer; transition: 0.3s var(--spring); }
        .word-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        
        /* List View Support */
        .grid.list-mode { grid-template-columns: 1fr; gap: 12px; }
        .grid.list-mode .card-details { max-height: 0; overflow: hidden; opacity: 0; transition: 0.3s; }
        .grid.list-mode .word-card.active .card-details { max-height: 600px; opacity: 1; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border); }
        .list-preview { display: none; align-items: center; gap: 15px; width: 100%; }
        .grid.list-mode .list-preview { display: flex; padding-right: 120px; }
        .grid.list-mode .grid-header { display: none; }

        .select-checkbox { position: absolute; top: 20px; left: 20px; width: 22px; height: 22px; z-index: 20; display: none; }
        .selection-mode .select-checkbox { display: block; }
        .selection-mode .word-card.selected { border-color: var(--accent); background: #f0fdf4; }

        .bulk-action-bar { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: var(--primary); color: white; padding: 15px 30px; border-radius: 20px; display: flex; align-items: center; gap: 20px; z-index: 3000; transition: 0.4s var(--spring); }
        .bulk-action-bar.active { bottom: 30px; }

        .card-actions { position: absolute; top: 20px; right: 20px; display: flex; gap: 8px; z-index: 10; }
        .action-btn { width: 32px; height: 32px; border-radius: 10px; border: 1px solid var(--border); background: white; cursor: pointer; }

        .level-badge { font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 8px; background: #f1f5f9; text-transform: uppercase; }
        .ipa { font-family: 'Courier New', monospace; color: var(--text-muted); font-size: 0.85rem; }
        .vi-text { color: #1e40af; border-left: 3px solid #bfdbfe; padding-left: 12px; font-style: italic; font-size: 0.9rem; margin-top: 10px; }

        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; z-index: 2000; backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 40px; border-radius: 30px; width: 100%; max-width: 500px; animation: springUp 0.5s var(--spring); }
        @keyframes springUp { from { transform: scale(0.8) translateY(50px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-toggle" onclick="toggleSidebar()" title="Toggle Folders"></div>
        <h2 style="font-weight: 800; margin-bottom: 5px;">Folders</h2>
        <p style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600; margin-bottom: 25px;">Organize your learning</p>
        <div class="folder-list" id="categoryList"></div>
        <div class="new-folder-box">
            <input type="text" id="newCatName" class="folder-input" placeholder="New folder name...">
            <button class="btn-toggle" style="width: 100%; background: var(--accent); color: white;" onclick="createCategory()">+ Create Folder</button>
        </div>
    </aside>

    <main class="main-content" id="mainContent">
        <div class="container">
            <div class="header-flex">
                <div>
                    <h1 id="viewTitle" style="margin:0; font-weight: 800; font-size: 2.2rem;">All Words</h1>
                    <p id="wordCountLabel" style="color: var(--text-muted); font-weight: 600; margin-top: 5px;">Refreshing vault...</p>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <div style="display: flex; gap: 5px;">
                        <button class="btn-toggle" id="gridBtn" onclick="setView('grid')">Grid</button>
                        <button class="btn-toggle" id="listBtn" onclick="setView('list')">List</button>
                        <button class="btn-toggle btn-manage" id="manageBtn" onclick="toggleSelectionMode()">Manage</button>
                    </div>
                    
                    <div style="display: flex; gap: 5px;">
                        <a href="export.php?type=csv" class="btn-toggle" style="text-decoration: none;" title="Export CSV">📊</a>
                        <a href="export.php?type=docx" class="btn-toggle" style="text-decoration: none;" title="Export DOCX">📄</a>
                    </div>

                    <input type="text" class="search-bar" id="vaultSearch" placeholder="Quick search..." onkeyup="renderWords()">
                </div>
            </div>

            <div class="grid" id="wordGrid"></div>
        </div>
    </main>

    <div class="bulk-action-bar" id="bulkBar">
        <span id="selectedCount">0 selected</span>
        <button class="btn-toggle" onclick="selectAll()">Select All</button>
        <button class="btn-toggle" style="background: var(--danger); color:white; border:none;" onclick="deleteSelected()">Delete Selected</button>
    </div>

    <div class="modal" id="editModal" onclick="if(event.target == this) closeModal()">
        <div class="modal-content">
            <h2 style="margin-top:0; font-weight: 800;">Edit Entry</h2>
            <form id="editForm">
                <input type="hidden" name="id" id="edit-id">
                <div style="margin-bottom:15px;"><label style="font-size:0.7rem; font-weight:800; text-transform:uppercase;">Word</label>
                <input type="text" name="word" id="edit-word" class="folder-input" style="font-size:1rem; font-weight:700;" required></div>
                <div style="display: flex; gap: 15px; margin-bottom:15px;">
                    <div style="flex:1;"><label>IPA</label><input type="text" name="ipa" id="edit-ipa" class="folder-input"></div>
                    <div style="flex:1;"><label>Level</label>
                        <select name="level" id="edit-level" class="folder-input">
                            <option value="A1">A1</option><option value="A2">A2</option><option value="B1">B1</option>
                            <option value="B2">B2</option><option value="C1">C1</option><option value="C2">C2</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:15px;"><label>English Definition</label><textarea name="defEn" id="edit-defEn" class="folder-input" rows="2"></textarea></div>
                <div style="margin-bottom:20px;"><label>Vietnamese Translation</label><input type="text" name="defVi" id="edit-defVi" class="folder-input"></div>
                <button type="submit" class="btn-toggle" style="width:100%; background:var(--primary); color:white;">Save Changes</button>
            </form>
        </div>
    </div>

<script>
    let allWords = [];
    let currentFolderId = null;
    let selectionMode = false;

    async function init() {
        if (localStorage.getItem('sidebarState') === 'hidden') document.getElementById('sidebar').classList.add('collapsed');
        await loadCategories();
        await refreshWordData();
        setView(localStorage.getItem('vaultView') || 'grid');
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarState', sidebar.classList.contains('collapsed') ? 'hidden' : 'visible');
    }

    async function loadCategories() {
        const res = await fetch('category_actions.php');
        const cats = await res.json();
        const list = document.getElementById('categoryList');
        list.innerHTML = `<div class="folder-item ${currentFolderId === null ? 'active' : ''}" onclick="selectFolder(null, this)"><span>📂 All Words</span></div>`;
        cats.forEach(c => {
            list.innerHTML += `<div class="folder-item ${currentFolderId == c.id ? 'active' : ''}" onclick="selectFolder(${c.id}, this)"><span>📁 ${c.name}</span></div>`;
        });
    }

    function selectFolder(id, el) {
        currentFolderId = id;
        document.querySelectorAll('.folder-item').forEach(item => item.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('viewTitle').innerText = id ? el.innerText.replace('📁 ', '') : "All Words";
        renderWords();
    }

    async function createCategory() {
        const name = document.getElementById('newCatName').value.trim();
        if(!name) return;
        const fd = new FormData(); fd.append('action', 'create'); fd.append('name', name);
        await fetch('category_actions.php', { method: 'POST', body: fd });
        document.getElementById('newCatName').value = "";
        loadCategories();
    }

    async function refreshWordData() {
        const res = await fetch('fetch_board.php');
        allWords = await res.json();
        renderWords();
    }

    function renderWords() {
        const grid = document.getElementById('wordGrid');
        const search = document.getElementById('vaultSearch').value.toUpperCase();
        const filtered = allWords.filter(w => (currentFolderId === null || w.category_id == currentFolderId) && w.word.toUpperCase().includes(search));
        
        document.getElementById('wordCountLabel').innerText = `${filtered.length} Word(s) Found`;
        grid.innerHTML = "";
        filtered.forEach((w, idx) => {
            const card = document.createElement('div');
            card.className = `word-card ${selectionMode ? 'selection-mode' : ''}`;
            card.id = `word-${w.id}`;
            card.style.animation = `cardEntrance 0.5s var(--spring) forwards ${idx * 0.03}s`;
            card.onclick = (e) => handleCardClick(e, card, w);
            card.innerHTML = `
                <input type="checkbox" class="select-checkbox" value="${w.id}" onclick="event.stopPropagation(); updateBulkBar();">
                <div class="card-actions">
                    <button class="action-btn" onclick="openEditModal(${JSON.stringify(w).replace(/"/g, '&quot;')}, event)">✎</button>
                    <button class="action-btn" onclick="deleteWord(${w.id}, event)" style="color:var(--danger)">✕</button>
                </div>
                <div class="grid-header">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; padding-right:70px;"><span class="level-badge">${w.level}</span><span class="ipa">${w.ipa}</span></div>
                    <h2 style="margin:0; font-size:1.6rem; font-weight:800;">${w.word}</h2>
                </div>
                <div class="list-preview"><span class="level-badge">${w.level}</span><strong style="font-size:1.1rem; min-width:80px;">${w.word}</strong><span class="vi-preview">${w.definition_vi}</span></div>
                <div class="card-details">
                    <p style="font-size:0.95rem; line-height:1.5; margin:15px 0; font-weight:500;">${w.definition_en}</p>
                    <div class="vi-text">${w.definition_vi}</div>
                </div>`;
            grid.appendChild(card);
        });
    }

    function setView(mode) {
        document.getElementById('wordGrid').classList.toggle('list-mode', mode === 'list');
        document.getElementById('gridBtn').classList.toggle('active', mode === 'grid');
        document.getElementById('listBtn').classList.toggle('active', mode === 'list');
        localStorage.setItem('vaultView', mode);
    }

    function toggleSelectionMode() {
        selectionMode = !selectionMode;
        document.getElementById('manageBtn').classList.toggle('active', selectionMode);
        document.getElementById('manageBtn').innerText = selectionMode ? "Cancel" : "Manage";
        renderWords();
        updateBulkBar();
    }

    function handleCardClick(e, card, word) {
        if (e.target.closest('.action-btn')) return;
        if (selectionMode) {
            const cb = card.querySelector('.select-checkbox');
            cb.checked = !cb.checked;
            card.classList.toggle('selected', cb.checked);
            updateBulkBar();
        } else if (document.getElementById('wordGrid').classList.contains('list-mode')) {
            card.classList.toggle('active');
        }
    }

    function updateBulkBar() {
        const count = document.querySelectorAll('.select-checkbox:checked').length;
        document.getElementById('selectedCount').innerText = `${count} selected`;
        document.getElementById('bulkBar').classList.toggle('active', selectionMode && count > 0);
    }

    function selectAll() {
        document.querySelectorAll('.select-checkbox').forEach(cb => { cb.checked = true; cb.closest('.word-card').classList.add('selected'); });
        updateBulkBar();
    }

    async function deleteWord(id, e) {
        e.stopPropagation();
        if(!confirm("Remove word?")) return;
        const fd = new FormData(); fd.append('id', id);
        await fetch('delete_word.php', { method: 'POST', body: fd });
        refreshWordData();
    }

    async function deleteSelected() {
        const ids = Array.from(document.querySelectorAll('.select-checkbox:checked')).map(cb => cb.value).join(',');
        if(!confirm("Delete selected words?")) return;
        const fd = new FormData(); fd.append('id', ids);
        await fetch('delete_word.php', { method: 'POST', body: fd });
        selectionMode = false; refreshWordData();
    }

    function openEditModal(w, e) {
        e.stopPropagation();
        document.getElementById('edit-id').value = w.id;
        document.getElementById('edit-word').value = w.word;
        document.getElementById('edit-ipa').value = w.ipa;
        document.getElementById('edit-level').value = w.level;
        document.getElementById('edit-defEn').value = w.definition_en;
        document.getElementById('edit-defVi').value = w.definition_vi;
        document.getElementById('editModal').classList.add('active');
    }

    function closeModal() { document.getElementById('editModal').classList.remove('active'); }

    document.getElementById('editForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target); fd.append('form', 'noun'); fd.append('sentence', '');
        await fetch('update_word.php', { method: 'POST', body: fd });
        closeModal(); refreshWordData();
    }

    window.onload = init;
</script>
</body>
</html>