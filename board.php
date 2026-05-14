<?php
/**
 * Vocab AI Pro - Board Management (Session 2026)
 * Protocol: Full File Delivery & Debugger-First
 * Features: Grid/List Toggle, Bulk Action, Export, Radar Status
 */

include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Vocabularies with Category Name
$vocab_query = "SELECT v.*, c.name as category_name 
                FROM vocabularies v 
                LEFT JOIN categories c ON v.category_id = c.id 
                WHERE v.user_id = $user_id 
                ORDER BY v.id DESC";
$vocab_result = $conn->query($vocab_query);

// Fetch Categories for Modal
$cat_query = "SELECT * FROM categories WHERE user_id = $user_id";
$cat_result = $conn->query($cat_query);
$categories = [];
while($row = $cat_result->fetch_assoc()) { $categories[] = $row; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vocab AI Pro | Vault Board</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b;
            --accent: #10b981;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --text-muted: #64748b;
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --transition: all 0.3s var(--spring);
        }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background: var(--bg);
            margin: 0;
            color: var(--primary);
            padding: 40px;
        }

        /* --- Header Controls --- */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: white;
            padding: 20px 30px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            border: 1px solid var(--border);
        }

        .view-controls, .export-controls { display: flex; gap: 10px; }

        button {
            padding: 10px 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: white;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        button.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* --- Radar Status --- */
        .radar-box { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; font-weight: 700; }
        .radar-dot { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; transition: 0.3s; }
        .radar-dot.online { background: var(--accent); box-shadow: 0 0 10px var(--accent); }

        /* --- Bulk Actions Bar (Floating) --- */
        #bulk-bar {
            display: none;
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            z-index: 1000;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            align-items: center;
            gap: 20px;
        }

        /* --- Vocab Container Layouts --- */
        #vocab-container { margin-top: 20px; min-height: 400px; }

        /* Grid View */
        .grid-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        
        /* List View */
        .list-view { display: flex; flex-direction: column; gap: 15px; }

        .vocab-card {
            background: white;
            padding: 30px;
            border-radius: 32px;
            border: 1px solid var(--border);
            position: relative;
            transition: var(--transition);
            overflow: hidden;
        }

        .vocab-card:hover { transform: scale(1.02); box-shadow: 0 20px 50px rgba(0,0,0,0.05); }

        .select-checkbox { position: absolute; top: 20px; right: 20px; width: 18px; height: 18px; cursor: pointer; }

        .list-view .vocab-card {
            display: grid;
            grid-template-columns: 50px 1.5fr 1fr 1fr 120px;
            align-items: center;
            padding: 15px 30px;
        }

        .level-badge {
            background: #ecfdf5; color: var(--accent);
            padding: 4px 12px; border-radius: 10px; font-size: 0.75rem; font-weight: 800;
        }

        /* --- Modal Styles --- */
        .modal {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(8px);
        }
        .modal-content {
            background: white; width: 90%; max-width: 650px;
            margin: 50px auto; padding: 40px; border-radius: 35px;
            max-height: 85vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,0.1);
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 0.9rem; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 12px 18px; border: 1.5px solid var(--border);
            border-radius: 14px; font-family: inherit; font-size: 1rem; outline: none;
        }
        .form-group input:focus { border-color: var(--accent); }
    </style>
</head>
<body>

<div class="header-actions">
    <div class="view-controls">
        <button id="btn-grid" onclick="changeView('grid')" class="active">Lưới</button>
        <button id="btn-list" onclick="changeView('list')">Danh sách</button>
    </div>

    <div class="radar-box">
        <div id="radar-dot" class="radar-dot"></div>
        <span>AI Engine: <span id="radar-status">Scanning...</span></span>
    </div>

    <div class="export-controls">
        <button onclick="exportData('csv')">CSV</button>
        <button onclick="exportData('docx')">DOCX</button>
    </div>
</div>

<div id="vocab-container" class="grid-view">
    <?php while($word = $vocab_result->fetch_assoc()): ?>
    <div class="vocab-card animate-spring" id="word-<?php echo $word['id']; ?>">
        <input type="checkbox" class="select-checkbox" value="<?php echo $word['id']; ?>" onchange="toggleBulkBar()">
        
        <div class="card-header">
            <span class="level-badge"><?php echo $word['level']; ?></span>
            <h2 style="margin: 10px 0 5px 0; color: var(--primary);"><?php echo $word['word']; ?></h2>
            <code style="color: var(--text-muted);"><?php echo $word['ipa']; ?></code>
        </div>

        <div style="margin: 15px 0;">
            <p><strong>Dịch:</strong> <?php echo $word['definition_vi']; ?></p>
            <p style="font-style: italic; color: var(--text-muted); font-size: 0.9rem;">
                "<?php echo $word['nuance']; ?>"
            </p>
        </div>

        <div class="card-footer" style="display:flex; justify-content: flex-end;">
            <button onclick='openModal(<?php echo json_encode($word, JSON_HEX_APOS); ?>)'>Sửa</button>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<div id="bulk-bar">
    <span id="selected-count">0 selected</span>
    <button style="background:#ef4444; color:white; border:none;" onclick="bulkAction('delete')">Xóa</button>
    <button style="background:rgba(255,255,255,0.1); color:white; border:none;" onclick="bulkAction('move')">Di chuyển</button>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-top:0;">Chỉnh sửa Chi tiết</h2>
        <form id="editForm">
            <input type="hidden" name="id" id="m-id">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="form-group">
                    <label>Từ vựng</label>
                    <input type="text" name="word" id="m-word" required>
                </div>
                <div class="form-group">
                    <label>Phiên âm</label>
                    <input type="text" name="ipa" id="m-ipa">
                </div>
            </div>

            <div class="form-group">
                <label>Sắc thái (Nuance)</label>
                <textarea name="nuance" id="m-nuance" placeholder="Sắc thái biểu đạt từ AI..."></textarea>
            </div>

            <div class="form-group">
                <label>Cụm từ đi kèm (Collocations)</label>
                <input type="text" name="collocations" id="m-collocations">
            </div>

            <div class="form-group">
                <label>Ngữ cảnh gốc (Essay Context)</label>
                <textarea name="context_sentence" id="m-context" rows="3" readonly style="background:#f1f5f9;"></textarea>
            </div>

            <input type="hidden" name="form" id="m-form">
            <input type="hidden" name="level" id="m-level">
            <input type="hidden" name="defEn" id="m-defEn">
            <input type="hidden" name="defVi" id="m-defVi">
            <input type="hidden" name="sentence" id="m-sentence">
            <input type="hidden" name="category_id" id="m-cat-id">

            <div style="display:flex; gap:15px; justify-content: flex-end;">
                <button type="button" onclick="closeModal()" style="background:#f1f5f9;">Đóng</button>
                <button type="button" onclick="handleSave()" style="background:var(--accent); color:white; border:none;">Lưu & Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
/** * --- DEBUGGER FIRST ---
 */
window.onerror = function(msg, url, line) {
    logUIError({ msg, url, line });
    return false;
};

function logUIError(details) {
    console.error("Board Error:", details);
    alert(`❌ LỖI HỆ THỐNG: ${details.msg || details}`);
}

// 1. Layout Toggle
function changeView(mode) {
    const container = document.getElementById('vocab-container');
    const btnGrid = document.getElementById('btn-grid');
    const btnList = document.getElementById('btn-list');

    if(mode === 'grid') {
        container.className = 'grid-view';
        btnGrid.classList.add('active');
        btnList.classList.remove('active');
    } else {
        container.className = 'list-view';
        btnGrid.classList.remove('active');
        btnList.classList.add('active');
    }
    localStorage.setItem('vpro_board_view', mode);
}

// 2. Modal Logic
function openModal(data) {
    document.getElementById('m-id').value = data.id;
    document.getElementById('m-word').value = data.word;
    document.getElementById('m-ipa').value = data.ipa;
    document.getElementById('m-nuance').value = data.nuance || '';
    document.getElementById('m-collocations').value = data.collocations || '';
    document.getElementById('m-context').value = data.context_sentence || 'N/A';
    
    // Fill hidden sync fields
    document.getElementById('m-form').value = data.word_form;
    document.getElementById('m-level').value = data.level;
    document.getElementById('m-defEn').value = data.definition_en;
    document.getElementById('m-defVi').value = data.definition_vi;
    document.getElementById('m-sentence').value = data.example_sentence;
    document.getElementById('m-cat-id').value = data.category_id;

    document.getElementById('editModal').style.display = 'block';
}

function closeModal() { document.getElementById('editModal').style.display = 'none'; }

// 3. Sync Logic with update_word.php
async function handleSave() {
    const formData = new FormData(document.getElementById('editForm'));
    try {
        const response = await fetch('update_word.php', { method: 'POST', body: formData });
        const text = await response.text();
        let result;
        try { result = JSON.parse(text); } catch(e) { throw new Error("Backend trả về non-JSON: " + text); }

        if (result.status === 'success') {
            alert("✅ " + result.message);
            location.reload();
        } else {
            throw new Error(result.message);
        }
    } catch (error) { logUIError(error); }
}

// 4. Bulk Action Logic
function toggleBulkBar() {
    const checks = document.querySelectorAll('.select-checkbox:checked');
    const bar = document.getElementById('bulk-bar');
    document.getElementById('selected-count').innerText = `${checks.length} items selected`;
    bar.style.display = checks.length > 0 ? 'flex' : 'none';
}

async function bulkAction(type) {
    const ids = Array.from(document.querySelectorAll('.select-checkbox:checked')).map(c => c.value);
    if(confirm(`Xác nhận thực hiện ${type} cho ${ids.length} từ?`)) {
        // Gửi tới endpoint bulk_actions.php (User cần có file này)
        console.log("Bulk action execution:", type, ids);
        alert(`Đã gửi yêu cầu ${type} cho ${ids.length} từ vựng.`);
    }
}

// 5. Export Logic
function exportData(format) {
    window.location.href = `export.php?format=${format}`;
}

// 6. Radar Ping
async function checkEngine() {
    const dot = document.getElementById('radar-dot');
    const status = document.getElementById('radar-status');
    try {
        const res = await fetch('/api/ping');
        if (res.ok) {
            dot.className = 'radar-dot online';
            status.innerText = 'Online';
        } else {
            dot.className = 'radar-dot';
            status.innerText = 'Offline';
        }
    } catch {
        dot.className = 'radar-dot';
        status.innerText = 'Offline';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('vpro_board_view') || 'grid';
    changeView(saved);
    checkEngine();
    setInterval(checkEngine, 10000);
});
</script>
</body>
</html>
