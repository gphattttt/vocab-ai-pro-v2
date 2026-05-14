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
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kho từ vựng & Thư mục | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b; --accent: #10b981; --bg: #f8fafc;
            --border: #e2e8f0; --text-muted: #64748b; --danger: #ef4444;
            --sidebar-width: 280px;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; color: var(--primary); display: flex; min-height: 100vh; overflow-x: hidden; }

        /* --- SIDEBAR --- */
        .sidebar { 
            width: var(--sidebar-width); background: white; border-right: 1px solid var(--border); 
            padding: 30px 20px; position: fixed; top: 0; left: 0; height: 100vh; box-sizing: border-box;
            display: flex; flex-direction: column; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000; box-shadow: 4px 0 15px rgba(0,0,0,0.03);
        }
        .sidebar.collapsed { transform: translateX(-100%); }

        .sidebar-toggle {
            position: absolute; right: -28px; top: 80px;
            width: 28px; height: 60px; background: white; border: 1px solid var(--border); 
            border-left: none; border-radius: 0 12px 12px 0; display: flex; 
            align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 4px 0 10px rgba(0,0,0,0.05); transition: 0.3s; z-index: 1010; color: var(--text-muted);
        }
        .sidebar-toggle:hover { background: var(--accent); color: white; border-color: var(--accent); }
        .sidebar-toggle::after { content: "❮"; transition: 0.3s; font-size: 0.8rem; font-weight: 800; }
        .sidebar.collapsed .sidebar-toggle::after { content: "❯"; }

        /* --- MAIN CONTENT --- */
        .main-content { 
            flex: 1; margin-left: var(--sidebar-width); transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            padding: 40px; padding-top: 80px; min-width: 0; box-sizing: border-box; width: 100%;
        }
        .sidebar.collapsed + .main-content { margin-left: 0; }
        .container { max-width: 1000px; margin: 0 auto; width: 100%; }

        /* --- FOLDERS --- */
        .folder-list { margin-top: 20px; flex: 1; overflow-y: auto; padding-right: 5px; }
        .folder-item { 
            padding: 12px 16px; border-radius: 12px; cursor: pointer; transition: 0.2s;
            font-weight: 600; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 5px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .folder-item:hover { background: #f1f5f9; color: var(--primary); }
        .folder-item.active { background: var(--primary); color: white; }
        
        .new-folder-box { margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border); }
        .folder-input { 
            width: 100%; padding: 14px; border-radius: 12px; border: 1px solid var(--border); 
            margin-bottom: 10px; box-sizing: border-box; font-family: inherit; font-size: 1rem;
        }
        .folder-input:focus { outline: none; border-color: var(--accent); }
        
        /* --- HEADER & CARDS --- */
        .header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; gap: 15px; flex-wrap: wrap; }
        .header-title-area { flex: 1; min-width: 250px; }
        .header-controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        
        .search-bar { flex: 1; min-width: 180px; padding: 12px 20px; border-radius: 12px; border: 2px solid var(--border); outline: none; font-size: 1rem; font-family: inherit; transition: 0.3s; }
        .search-bar:focus { border-color: var(--accent); }
        
        /* FIX #1: Added color: var(--primary) here so we don't need inline styles, allowing .active to override it properly */
        .btn-toggle { background: white; border: 1px solid var(--border); padding: 12px 18px; border-radius: 12px; cursor: pointer; font-weight: 700; font-size: 0.9rem; transition: 0.2s; white-space: nowrap; color: var(--primary); }
        .btn-toggle:hover { background: #f8fafc; }
        .btn-toggle.active { background: var(--primary); color: white; border-color: var(--primary); }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .word-card { background: white; border-radius: 20px; padding: 25px; border: 1px solid var(--border); position: relative; cursor: pointer; transition: 0.3s var(--spring); }
        .word-card:hover { transform: translateY(-4px); border-color: var(--accent); box-shadow: 0 10px 20px rgba(0,0,0,0.03); }
        
        /* List View Support */
        .grid.list-mode { grid-template-columns: 1fr; gap: 12px; }
        .grid.list-mode .card-details { max-height: 0; overflow: hidden; opacity: 0; transition: 0.3s; }
        .grid.list-mode .word-card.active .card-details { max-height: 600px; opacity: 1; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border); }
        .list-preview { display: none; align-items: center; gap: 15px; width: 100%; }
        .grid.list-mode .list-preview { display: flex; padding-right: 90px; }
        .grid.list-mode .grid-header { display: none; }

        .select-checkbox { position: absolute; top: 20px; left: 20px; width: 24px; height: 24px; z-index: 20; display: none; cursor: pointer; }
        .selection-mode .select-checkbox { display: block; }
        .selection-mode .word-card.selected { border-color: var(--accent); background: #f0fdf4; }

        .bulk-action-bar { 
            position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: var(--primary);
            color: white; padding: 15px 25px; border-radius: 16px; display: flex; align-items: center; gap: 15px; 
            z-index: 3000; transition: 0.4s var(--spring); flex-wrap: wrap; justify-content: center; width: max-content; max-width: 90%;
        }
        .bulk-action-bar.active { bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }

        .card-actions { position: absolute; top: 20px; right: 20px; display: flex; gap: 8px; z-index: 10; }
        .action-btn { width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border); background: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; font-size: 1.1rem; }
        .action-btn:hover { background: #f1f5f9; }

        .level-badge { font-size: 0.65rem; font-weight: 800; padding: 5px 10px; border-radius: 8px; background: #f1f5f9; text-transform: uppercase; color: var(--primary); }
        .ipa { font-family: 'Courier New', monospace; color: var(--text-muted); font-size: 0.85rem; font-weight: 500; }
        .vi-text { color: #1e40af; border-left: 3px solid #bfdbfe; padding-left: 12px; font-style: italic; font-size: 0.95rem; margin-top: 10px; font-weight: 500;}
        .vi-preview { font-size: 0.9rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Modal */
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; z-index: 2000; backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 24px; width: 100%; max-width: 500px; animation: springUp 0.5s var(--spring); box-sizing: border-box; max-height: 90vh; overflow-y: auto; }
        @keyframes springUp { from { transform: scale(0.9) translateY(30px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 8px; color: var(--text-muted); }

        /* --- MOBILE RESPONSIVENESS --- */
        @media (max-width: 768px) {
            /* FIX #2: Strict -100% translation so it tucks entirely off-screen, and removing the box-shadow */
            .sidebar.collapsed { transform: translateX(-100%); box-shadow: none; } 
            .sidebar:not(.collapsed) { transform: translateX(0); box-shadow: 10px 0 30px rgba(0,0,0,0.15); }
            
            .main-content { margin-left: 0 !important; padding: 20px; padding-top: 60px; }
            
            .header-flex { flex-direction: column; align-items: stretch; }
            .header-controls { justify-content: flex-start; }
            .search-bar { width: 100%; flex: none; }
            
            .grid { grid-template-columns: 1fr; } 
            
            .grid.list-mode .vi-preview { display: none; }
            
            h1#viewTitle { font-size: 1.8rem !important; }
            .bulk-action-bar { width: 90%; padding: 12px 20px; flex-direction: column; gap: 10px; }
            .bulk-action-bar .btn-toggle { width: 100%; }
        }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <aside class="sidebar collapsed" id="sidebar">
        <div class="sidebar-toggle" onclick="toggleSidebar()" title="Đóng/Mở Thư mục"></div>
        <h2 style="font-weight: 800; margin-bottom: 5px; margin-top: 0;">Thư mục</h2>
        <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500; margin-bottom: 25px;">Sắp xếp bài học</p>
        <div class="folder-list" id="categoryList"></div>
        <div class="new-folder-box">
            <input type="text" id="newCatName" class="folder-input" placeholder="Tên thư mục mới...">
            <button class="btn-toggle" style="width: 100%; background: var(--accent); color: white; border: none; padding: 14px;" onclick="createCategory()">+ Tạo thư mục</button>
        </div>
    </aside>

    <main class="main-content" id="mainContent">
        <div class="container">
            <div class="header-flex">
                <div class="header-title-area">
                    <h1 id="viewTitle" style="margin:0; font-weight: 800; font-size: 2.2rem; letter-spacing: -0.5px;">Tất cả từ vựng</h1>
                    <p id="wordCountLabel" style="color: var(--text-muted); font-weight: 600; margin-top: 8px; font-size: 0.95rem;">Đang tải dữ liệu...</p>
                </div>
                
                <div class="header-controls">
                    <div style="display: flex; gap: 8px;">
                        <button class="btn-toggle active" id="gridBtn" onclick="setView('grid')">Lưới</button>
                        <button class="btn-toggle" id="listBtn" onclick="setView('list')">Danh sách</button>
                        <button class="btn-toggle" id="manageBtn" onclick="toggleSelectionMode()">Quản lý</button>
                    </div>
                    
                    <div style="display: flex; gap: 8px;">
                        <a href="export.php?type=csv" class="btn-toggle" style="text-decoration: none; padding: 12px;" title="Xuất CSV">📊</a>
                        <a href="export.php?type=docx" class="btn-toggle" style="text-decoration: none; padding: 12px;" title="Xuất DOCX">📄</a>
                    </div>
                </div>
                
                <input type="text" class="search-bar" id="vaultSearch" placeholder="Tìm kiếm nhanh..." onkeyup="renderWords()">
            </div>

            <div class="grid" id="wordGrid"></div>
        </div>
    </main>

    <div class="bulk-action-bar" id="bulkBar">
        <span id="selectedCount" style="font-weight: 600;">0 mục đã chọn</span>
        <div style="display: flex; gap: 10px; width: 100%; justify-content: center;">
            <button class="btn-toggle" style="color: var(--primary); flex: 1;" onclick="selectAll()">Chọn tất cả</button>
            <button class="btn-toggle" style="background: var(--danger); color:white; border:none; flex: 1;" onclick="deleteSelected()">Xóa đã chọn</button>
        </div>
    </div>

    <div class="modal" id="editModal" onclick="if(event.target == this) closeModal()">
        <div class="modal-content">
            <h2 style="margin-top:0; font-weight: 800; margin-bottom: 25px;">Chỉnh sửa từ</h2>
            <form id="editForm">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="form-group">
                    <label class="form-label">Từ vựng</label>
                    <input type="text" name="word" id="edit-word" class="folder-input" style="font-size:1.1rem; font-weight:700; color: var(--primary);" required>
                </div>
                
                <div style="display: flex; gap: 15px; margin-bottom:15px; flex-wrap: wrap;">
                    <div style="flex:1; min-width: 120px;">
                        <label class="form-label">Phiên âm (IPA)</label>
                        <input type="text" name="ipa" id="edit-ipa" class="folder-input">
                    </div>
                    <div style="flex:1; min-width: 120px;">
                        <label class="form-label">Cấp độ</label>
                        <input type="text" name="level" id="edit-level" class="folder-input" placeholder="VD: B2, C1, Y tế...">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Định nghĩa tiếng Anh</label>
                    <textarea name="defEn" id="edit-defEn" class="folder-input" rows="3" style="resize: vertical;"></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom: 25px;">
                    <label class="form-label">Nghĩa tiếng Việt</label>
                    <input type="text" name="defVi" id="edit-defVi" class="folder-input">
                </div>
                
                <button type="submit" class="btn-toggle" style="width:100%; background:var(--primary); color:white; border:none; padding: 16px; font-size: 1rem;">Lưu thay đổi</button>
            </form>
        </div>
    </div>

<script>
    let allWords = [];
    let currentFolderId = null;
    let selectionMode = false;

    async function init() {
        if (window.innerWidth <= 768 || localStorage.getItem('sidebarState') === 'hidden') {
            document.getElementById('sidebar').classList.add('collapsed');
        } else {
            document.getElementById('sidebar').classList.remove('collapsed');
        }
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
        list.innerHTML = `<div class="folder-item ${currentFolderId === null ? 'active' : ''}" onclick="selectFolder(null, this)"><span>📂 Tất cả từ vựng</span></div>`;
        cats.forEach(c => {
            list.innerHTML += `<div class="folder-item ${currentFolderId == c.id ? 'active' : ''}" onclick="selectFolder(${c.id}, this)"><span>📁 ${c.name}</span></div>`;
        });
    }

    function selectFolder(id, el) {
        currentFolderId = id;
        document.querySelectorAll('.folder-item').forEach(item => item.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('viewTitle').innerText = id ? el.innerText.replace('📁 ', '') : "Tất cả từ vựng";
        
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('collapsed');
        }
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
        
        document.getElementById('wordCountLabel').innerText = `${filtered.length} từ được tìm thấy`;
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
                    <button class="action-btn" onclick="openEditModal(${JSON.stringify(w).replace(/"/g, '&quot;')}, event)" title="Chỉnh sửa">✎</button>
                    <button class="action-btn" onclick="deleteWord(${w.id}, event)" style="color:var(--danger)" title="Xóa">✕</button>
                </div>
                <div class="grid-header">
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px; padding-right:80px;"><span class="level-badge">${w.level}</span><span class="ipa">${w.ipa}</span></div>
                    <h2 style="margin:0; font-size:1.6rem; font-weight:800;">${w.word}</h2>
                </div>
                <div class="list-preview">
                    <span class="level-badge">${w.level}</span>
                    <strong style="font-size:1.1rem; min-width:80px;">${w.word}</strong>
                    <span class="vi-preview">${w.definition_vi}</span>
                </div>
                <div class="card-details">
                    <p style="font-size:0.95rem; line-height:1.6; margin:15px 0; font-weight:500;">${w.definition_en}</p>
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
        document.getElementById('manageBtn').innerText = selectionMode ? "Hủy" : "Quản lý";
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
        document.getElementById('selectedCount').innerText = `${count} mục đã chọn`;
        document.getElementById('bulkBar').classList.toggle('active', selectionMode && count > 0);
    }

    function selectAll() {
        document.querySelectorAll('.select-checkbox').forEach(cb => { cb.checked = true; cb.closest('.word-card').classList.add('selected'); });
        updateBulkBar();
    }

    async function deleteWord(id, e) {
        e.stopPropagation();
        if(!confirm("Bạn có chắc chắn muốn xóa từ này?")) return;
        const fd = new FormData(); fd.append('id', id);
        await fetch('delete_word.php', { method: 'POST', body: fd });
        refreshWordData();
    }

    async function deleteSelected() {
        const ids = Array.from(document.querySelectorAll('.select-checkbox:checked')).map(cb => cb.value).join(',');
        if(!confirm("Xóa các từ đã chọn?")) return;
        const fd = new FormData(); fd.append('id', ids);
        await fetch('delete_word.php', { method: 'POST', body: fd });
        selectionMode = false; 
        document.getElementById('manageBtn').innerText = "Quản lý";
        document.getElementById('manageBtn').classList.remove('active');
        refreshWordData();
        updateBulkBar();
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
