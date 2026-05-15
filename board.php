<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$vocab_query = "SELECT v.*, c.name as category_name 
                FROM vocabularies v 
                LEFT JOIN categories c ON v.category_id = c.id 
                WHERE v.user_id = $user_id 
                ORDER BY v.id DESC";
$vocab_result = $conn->query($vocab_query);

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
        :root { --primary:#1e293b; --accent:#10b981; --bg:#f8fafc; --border:#e2e8f0; --text-muted:#64748b; --spring:cubic-bezier(0.175,0.885,0.32,1.275); --transition:all 0.3s var(--spring);}
        body {font-family:'Be Vietnam Pro',sans-serif;background:var(--bg);margin:0;color:var(--primary);overflow-x:hidden;}
        
        /* --- ANIMATIONS --- */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes popIn { 0% { opacity: 0; transform: scale(0.95); } 100% { opacity: 1; transform: scale(1); } }
        @keyframes slideUpBar { from { opacity: 0; transform: translate(-50%, 50px); } to { opacity: 1; transform: translate(-50%, 0); } }
        @keyframes pulseDot { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

        .app-layout{display:grid;grid-template-columns:280px 1fr;gap:24px;padding:30px;box-sizing:border-box;}
        
        .sidebar{background:white;border:1px solid var(--border);border-radius:24px;padding:24px;box-shadow:0 10px 25px rgba(0,0,0,0.03);height:fit-content;position:sticky;top:20px; animation: slideInLeft 0.6s var(--spring) forwards;}
        .sidebar h3{margin:0 0 12px;font-size:1.1rem;font-weight:800;}
        .sidebar p{font-size:.85rem;color:var(--text-muted);margin-top:0;}
        .cat-input{width:100%;padding:12px 14px;border-radius:14px;border:1.5px solid var(--border);font-family:inherit;margin-bottom:12px;outline:none;transition: var(--transition);}
        .cat-input:focus{border-color:var(--accent); box-shadow: 0 4px 12px rgba(16,185,129,0.1);}
        .btn-add-cat{width:100%;padding:12px;border-radius:14px;border:none;background:var(--primary);color:white;font-weight:800;cursor:pointer;transition:var(--transition);}
        .btn-add-cat:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(0,0,0,0.08);}
        .cat-list{margin-top:16px;display:flex;flex-direction:column;gap:8px;max-height:320px;overflow-y:auto; padding-right:5px;}
        .cat-item{display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px solid var(--border);padding:8px 12px;border-radius:12px;font-size:.9rem; transition: var(--transition);}
        .cat-item:hover{border-color:#cbd5e1; transform:translateX(3px);}
        .cat-tag{font-weight:700;color:var(--primary);}
        
        .header-actions{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;background:white;padding:18px 24px;border-radius:24px;box-shadow:0 10px 25px rgba(0,0,0,0.03); animation: slideUp 0.6s var(--spring) forwards;}
        .view-controls,.export-controls{display:flex;gap:10px;flex-wrap:wrap;}
        button{padding:10px 20px;border-radius:12px;border:1px solid var(--border);background:white;font-weight:600;cursor:pointer;transition:var(--transition);display:flex;align-items:center;gap:6px;}
        button:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(0,0,0,0.05);}
        button:active{transform:translateY(0);}
        button.active{background:var(--primary);color:white;border-color:var(--primary);}
        
        .radar-box{display:flex;align-items:center;gap:10px;font-size:.85rem;font-weight:700;}
        .radar-dot{width:8px;height:8px;border-radius:50%;background:#ef4444;transition:.3s;}
        .radar-dot.online{background:var(--accent); animation: pulseDot 2s infinite;}
        
        #bulk-bar{display:none;position:fixed;bottom:30px;left:50%;transform:translateX(-50%);background:var(--primary);color:white;padding:12px 18px;border-radius:50px;z-index:1000;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);align-items:center;gap:15px; animation: slideUpBar 0.4s var(--spring) forwards;}
        #bulk-bar.hide { animation: slideDownBar 0.4s var(--spring) forwards; }
        @keyframes slideDownBar { from { opacity: 1; transform: translate(-50%, 0); } to { opacity: 0; transform: translate(-50%, 50px); } }
        
        #bulk-bar select{padding:8px 12px;border-radius:10px;border:none;outline:none;font-weight:600;}
        
        #vocab-container{margin-top:20px;min-height:400px;}
        .grid-view{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;}
        .list-view{display:flex;flex-direction:column;gap:12px;}
        
        .vocab-card{background:white;padding:26px;border-radius:26px;border:1px solid var(--border);position:relative;transition:var(--transition);overflow:hidden; opacity:0; animation: slideUp 0.5s var(--spring) forwards;}
        .vocab-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(0,0,0,0.06); border-color:#cbd5e1;}
        .select-checkbox{position:absolute;top:18px;right:18px;width:18px;height:18px;cursor:pointer; accent-color: var(--accent);}
        
        /* Tạo delay cho các card hiện ra mượt mà */
        .vocab-card:nth-child(1) { animation-delay: 0.05s; }
        .vocab-card:nth-child(2) { animation-delay: 0.1s; }
        .vocab-card:nth-child(3) { animation-delay: 0.15s; }
        .vocab-card:nth-child(4) { animation-delay: 0.2s; }
        .vocab-card:nth-child(5) { animation-delay: 0.25s; }
        .vocab-card:nth-child(6) { animation-delay: 0.3s; }
        .vocab-card:nth-child(n+7) { animation-delay: 0.35s; }

        .list-view .vocab-card{display:grid;grid-template-columns:40px 1.2fr 0.8fr 1.5fr 120px;align-items:start;gap:12px;}
        .list-col-def{color:var(--text-muted);font-size:.9rem;line-height:1.4;}
        .level-badge{background:#ecfdf5;color:var(--accent);padding:4px 12px;border-radius:10px;font-size:.75rem;font-weight:800; display:inline-block; margin-bottom:8px;}
        
        .modal{display:none;position:fixed;z-index:2000;left:0;top:0;width:100%;height:100%;background:rgba(30,41,59,.6);backdrop-filter:blur(5px); animation: fadeIn 0.3s ease;}
        .modal-content{background:white;width:90%;max-width:650px;margin:50px auto;padding:30px;border-radius:30px;max-height:85vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,0.2); animation: popIn 0.4s var(--spring);}
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;margin-bottom:8px;font-weight:700;font-size:.9rem;}
        .form-group input,.form-group textarea{width:100%;padding:12px 18px;border:1.5px solid var(--border);border-radius:14px;font-family:inherit;font-size:1rem;outline:none; transition: var(--transition);}
        .form-group input:focus, .form-group textarea:focus {border-color:var(--accent); box-shadow: 0 0 0 3px rgba(16,185,129,0.1);}
        
        #toast{position:fixed;bottom:24px;right:24px;z-index:3000;background:white;border:1px solid var(--border);padding:12px 16px;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,0.12);display:none;align-items:center;gap:10px;font-weight:600;font-size:.9rem; animation: slideUp 0.3s var(--spring) forwards;}
        #toast.success{border-color:#86efac;} #toast.error{border-color:#fda4af;}
        #toast .dot{width:10px;height:10px;border-radius:50%;}
        #toast.success .dot{background:#10b981;} #toast.error .dot{background:#ef4444;}
        
        @media (max-width:980px){.app-layout{grid-template-columns:1fr;padding:20px;}.sidebar{position:static; animation: fadeIn 0.5s ease;}}
        @media (max-width:768px){.header-actions{flex-direction:column;align-items:flex-start;}.view-controls,.export-controls{width:100%;}.view-controls button,.export-controls button{flex:1;justify-content:center;}}
        @media (max-width:480px){.app-layout{padding:16px;}.vocab-card{padding:20px;border-radius:22px;}#bulk-bar{width:calc(100% - 24px);left:12px;transform:none;justify-content:space-between; animation: slideUp 0.4s var(--spring);}}
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="app-layout">
    <aside class="sidebar">
        <h3>📁 Quản lý Category</h3>
        <p>Thêm category mới trực tiếp tại đây.</p>
        <input type="text" id="newCategoryInput" class="cat-input" placeholder="Ví dụ: IELTS, Academic...">
        <button class="btn-add-cat" onclick="createCategory()">+ Thêm Category</button>

        <div class="cat-list" id="catList">
            <?php foreach($categories as $cat): ?>
                <div class="cat-item" id="cat-item-<?php echo $cat['id']; ?>">
                    <span class="cat-tag"><?php echo htmlspecialchars($cat['name']); ?></span>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <span style="color: var(--text-muted); font-size: 0.75rem;">#<?php echo $cat['id']; ?></span>
                        <button style="background:none; border:none; color:#ef4444; padding:4px; cursor:pointer; transform:none; box-shadow:none;" onclick="deleteCategory(<?php echo $cat['id']; ?>)" title="Xoá Category">🗑️</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <main>
        <div class="header-actions">
            <div class="view-controls">
                <button id="btn-grid" onclick="changeView('grid')" class="active">Lưới</button>
                <button id="btn-list" onclick="changeView('list')">Danh sách</button>
                <button onclick="toggleSelectAll()" style="background:#f1f5f9;border-color:var(--border);">☑️ Chọn tất cả</button>
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
            <div class="vocab-card" id="word-<?php echo $word['id']; ?>">
                <input type="checkbox" class="select-checkbox" value="<?php echo $word['id']; ?>" onchange="toggleBulkBar()">
                <div class="card-header list-col-word">
                    <span class="level-badge"><?php echo $word['level']; ?></span>
                    <h2 style="margin:0 0 5px 0;color:var(--primary);"><?php echo $word['word']; ?></h2>
                </div>
                <div class="list-col-ipa"><code style="color:var(--text-muted); background:#f1f5f9; padding:2px 6px; border-radius:6px;"><?php echo $word['ipa']; ?></code></div>
                <div class="list-col-def" style="margin-top:12px;">
                    <p style="margin:0;"><strong>VI:</strong> <?php echo $word['definition_vi']; ?></p>
                    <p style="margin-top:6px;color:var(--text-muted);font-size:.9rem;">
                        <strong>EN:</strong> <?php echo $word['definition_en']; ?>
                    </p>
                    <?php if (!empty($word['nuance'])): ?>
                        <p style="font-style:italic;color:var(--text-muted);font-size:.85rem;margin-top:6px; border-left: 2px solid var(--accent); padding-left: 8px;">
                            "<?php echo $word['nuance']; ?>"
                        </p>
                    <?php endif; ?>
                </div>
                <div class="card-footer" style="display:flex;justify-content:flex-end; margin-top:15px;">
                    <button onclick='openModal(<?php echo json_encode($word, JSON_HEX_APOS); ?>)' style="background:#f1f5f9; border:none; padding:8px 16px;">Sửa</button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>
</div>

<div id="bulk-bar">
    <span id="selected-count" style="font-weight:700;">0 selected</span>
    <button style="background:#ef4444;color:white;border:none;" onclick="bulkAction('delete')">Xóa</button>
    <select id="bulkCategory">
        <option value="null">Chuyển về (No Category)</option>
        <?php foreach($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <button style="background:rgba(255,255,255,0.2);color:white;border:none;" onclick="bulkAction('move')">Di chuyển</button>
</div>

<div id="toast"><div class="dot"></div><span id="toastMsg"></span></div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-top:0; color:var(--primary); font-weight:800;">Chỉnh sửa Chi tiết</h2>
        <form id="editForm">
            <input type="hidden" name="id" id="m-id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group"><label>Từ vựng</label><input type="text" name="word" id="m-word" required></div>
                <div class="form-group"><label>Phiên âm</label><input type="text" name="ipa" id="m-ipa"></div>
            </div>
            <div class="form-group"><label>Sắc thái (Nuance)</label><textarea name="nuance" id="m-nuance" rows="2"></textarea></div>
            <div class="form-group"><label>Cụm từ đi kèm (Collocations)</label><input type="text" name="collocations" id="m-collocations"></div>
            <div class="form-group"><label>Ngữ cảnh gốc (Essay Context)</label><textarea name="context_sentence" id="m-context" rows="3" readonly style="background:#f1f5f9; color:#475569;"></textarea></div>
            <input type="hidden" name="form" id="m-form">
            <input type="hidden" name="level" id="m-level">
            <input type="hidden" name="defEn" id="m-defEn">
            <input type="hidden" name="defVi" id="m-defVi">
            <input type="hidden" name="sentence" id="m-sentence">
            <input type="hidden" name="category_id" id="m-cat-id">
            <div style="display:flex;gap:15px;justify-content:flex-end; margin-top:20px;">
                <button type="button" onclick="closeModal()" style="background:#f1f5f9; border:none;">Đóng</button>
                <button type="button" onclick="handleSave()" style="background:var(--accent);color:white;border:none; box-shadow:0 4px 10px rgba(16,185,129,0.3);">Lưu & Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
function showToast(msg, type='success'){const toast=document.getElementById('toast');const text=document.getElementById('toastMsg');toast.className=type;text.innerText=msg;toast.style.display="flex";setTimeout(()=>toast.style.display="none",3000);}

function changeView(mode){
    const c=document.getElementById('vocab-container');const g=document.getElementById('btn-grid');const l=document.getElementById('btn-list');
    if(mode==='grid'){c.className='grid-view';g.className='active';l.className='';}
    else{c.className='list-view';l.className='active';g.className='';}
    localStorage.setItem('vpro_board_view',mode);
}

function toggleBulkBar(){
    const checks=document.querySelectorAll('.select-checkbox:checked');
    const bar=document.getElementById('bulk-bar');
    document.getElementById('selected-count').innerText=`${checks.length} selected`;
    if(checks.length>0) {
        bar.className = '';
        bar.style.display='flex';
    } else {
        bar.className = 'hide';
        setTimeout(() => { if(document.querySelectorAll('.select-checkbox:checked').length === 0) bar.style.display='none'; }, 400);
    }
}

// Nút chọn tất cả
let isAllSelected = false;
function toggleSelectAll() {
    isAllSelected = !isAllSelected;
    const checkboxes = document.querySelectorAll('.select-checkbox');
    checkboxes.forEach(cb => cb.checked = isAllSelected);
    toggleBulkBar();
}

async function bulkAction(type){
    const ids=Array.from(document.querySelectorAll('.select-checkbox:checked')).map(c=>c.value);
    if(ids.length===0) return;

    if(type === 'delete') {
        if(!confirm('Cảnh báo: Bạn có chắc chắn muốn xóa vĩnh viễn ' + ids.length + ' từ vựng đã chọn?')) return;
    }

    const formData=new FormData();
    formData.append('action', type);
    formData.append('id', ids.join(','));
    if(type==='move'){
        const cat=document.getElementById('bulkCategory').value;
        formData.append('category_id', cat);
    }

    const res=await fetch('word_actions.php',{method:'POST',body:formData});
    const text=await res.text();
    let data;
    try{data=JSON.parse(text);}catch(e){showToast('Lỗi JSON','error');return;}

    if(data.status==='success'){
        showToast(data.message || 'Thành công');
        if(type==='delete'){
            ids.forEach(id=>{
                const el=document.getElementById('word-'+id);
                if(el){ el.style.transform='scale(0.8)'; el.style.opacity='0'; setTimeout(()=>el.remove(),300); }
            });
            setTimeout(toggleBulkBar, 300);
        }
        if(type==='move'){setTimeout(()=>location.reload(),600);}
    }else{showToast(data.message || 'Lỗi','error');}
}

function openModal(data){
    document.getElementById('m-id').value=data.id;
    document.getElementById('m-word').value=data.word;
    document.getElementById('m-ipa').value=data.ipa;
    document.getElementById('m-nuance').value=data.nuance || '';
    document.getElementById('m-collocations').value=data.collocations || '';
    document.getElementById('m-context').value=data.context_sentence || 'N/A';
    document.getElementById('m-form').value=data.word_form;
    document.getElementById('m-level').value=data.level;
    document.getElementById('m-defEn').value=data.definition_en;
    document.getElementById('m-defVi').value=data.definition_vi;
    document.getElementById('m-sentence').value=data.example_sentence;
    document.getElementById('m-cat-id').value=data.category_id;
    document.getElementById('editModal').style.display='block';
}
function closeModal(){
    const modal = document.getElementById('editModal');
    modal.style.opacity = '0';
    setTimeout(() => { modal.style.display='none'; modal.style.opacity = '1'; }, 300);
}

async function handleSave(){
    const formData=new FormData(document.getElementById('editForm'));
    formData.append('action','update');

    const res=await fetch('word_actions.php',{method:'POST',body:formData});
    const text=await res.text();
    let data;
    try{data=JSON.parse(text);}catch(e){showToast('Lỗi server','error');return;}

    if(data.status==='success'){showToast('✅ '+data.message);setTimeout(()=>location.reload(),600);}
    else{showToast('❌ '+data.message,'error');}
}

function exportData(format){ window.location.href = `export.php?type=${format}`; }

async function checkEngine(){
    const dot=document.getElementById('radar-dot');const status=document.getElementById('radar-status');
    try{const res=await fetch('/api/health');if(res.ok){dot.className='radar-dot online';status.innerText='Online';}else{dot.className='radar-dot';status.innerText='Offline';}}
    catch{dot.className='radar-dot';status.innerText='Offline';}
}

async function createCategory(){
    const input=document.getElementById('newCategoryInput');const name=input.value.trim();if(!name) return;
    const formData=new FormData();formData.append('action','create');formData.append('name',name);
    try{const res=await fetch('category_actions.php',{method:'POST',body:formData});const text=(await res.text()).trim();
        if(text==='Success'){ showToast('✅ Đã thêm category.'); setTimeout(()=>location.reload(),600); }
        else{showToast('❌ Lỗi tạo category.','error');}
    }catch(e){showToast('❌ Lỗi kết nối.','error');}
}

async function deleteCategory(id){
    if(!confirm('Bạn có chắc chắn muốn xóa Category này?\n\n(Các từ vựng bên trong Category này sẽ được giữ lại an toàn và chuyển về trạng thái không có Category)')) return;
    const formData = new FormData(); formData.append('action', 'delete'); formData.append('id', id);
    try{
        const res = await fetch('category_actions.php', {method: 'POST', body: formData});
        if((await res.text()).trim() === 'Success'){
            const el = document.getElementById('cat-item-'+id);
            el.style.opacity='0'; el.style.transform='translateX(-20px)';
            setTimeout(()=>el.remove(), 300);
            showToast('✅ Đã xóa category.');
            setTimeout(()=>location.reload(),600);
        } else { showToast('❌ Không thể xóa category.', 'error'); }
    }catch(e){ showToast('❌ Lỗi kết nối.', 'error'); }
}

document.addEventListener('DOMContentLoaded',()=>{
    const saved=localStorage.getItem('vpro_board_view')||'grid';
    changeView(saved);
    checkEngine();
    setInterval(checkEngine,10000);
    
    // Đóng modal khi click ra ngoài
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) closeModal();
    }
});
</script>
</body>
</html>
