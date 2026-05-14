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
        :root {
            --primary: #1e293b; --accent: #10b981; --danger: #ef4444; --warning: #f59e0b;
            --bg: #f8fafc; --border: #e2e8f0; --text-muted: #64748b;
        }
        body { font-family: 'Be Vietnam Pro', sans-serif; background: var(--bg); margin: 0; padding: 20px; color: var(--primary); }
        .container { max-width: 900px; margin: 0 auto; }
        
        .card { background: white; padding: 30px; border-radius: 25px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.03); margin-bottom: 20px; }
        .title { font-size: 1.5rem; font-weight: 800; margin-bottom: 20px; color: var(--primary); }
        
        textarea { width: 100%; height: 200px; padding: 20px; border-radius: 15px; border: 2px solid var(--border); font-family: inherit; font-size: 1rem; resize: vertical; box-sizing: border-box; margin-bottom: 20px; transition: 0.3s; }
        textarea:focus { outline: none; border-color: var(--accent); }
        
        select, input.req-input { width: 100%; padding: 15px; border-radius: 15px; border: 2px solid var(--border); font-family: inherit; font-size: 1rem; margin-bottom: 20px; box-sizing: border-box; }
        
        .btn-main { background: var(--primary); color: white; padding: 18px 30px; border: none; border-radius: 15px; font-weight: 700; font-size: 1.1rem; cursor: pointer; width: 100%; transition: 0.3s; }
        .btn-main:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-main:disabled { background: var(--text-muted); cursor: not-allowed; }

        /* Loader */
        #loader { display: none; text-align: center; padding: 40px; font-weight: 700; color: var(--accent); }

        /* Kết quả */
        #resultArea { display: none; }
        .section-title { font-weight: 800; color: var(--accent); border-bottom: 2px dashed var(--border); padding-bottom: 10px; margin-top: 30px; margin-bottom: 20px; }
        .coaching-box { background: #f0fdf4; padding: 20px; border-radius: 15px; line-height: 1.6; border: 1px solid #dcfce7; }
        
        /* Grid từ vựng */
        .vocab-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .word-card { background: white; border: 1px solid var(--border); padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .word-card h3 { margin: 0 0 5px 0; color: var(--accent); font-size: 1.3rem; }
        .word-ipa { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px; display: block; }
        .word-def { font-weight: 600; margin-bottom: 10px; }
        .word-ex { font-style: italic; color: #475569; font-size: 0.95rem; }

        /* Debugger */
        #debuggerArea { display: none; background: #0f172a; color: #e2e8f0; padding: 20px; border-radius: 15px; margin-top: 20px; font-family: monospace; }
        .debug-title { color: var(--warning); font-weight: bold; margin-bottom: 10px; }
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

            <div class="section-title">💎 Từ vựng nâng cao</div>
            <div id="vocabList" class="vocab-grid"></div>
        </div>

        <div id="debuggerArea">
            <div class="debug-title">⚠️ BẢNG ĐIỀU KHIỂN LỖI (DEBUGGER)</div>
            <div id="debugMsg" style="margin-bottom: 15px; color: #ef4444;"></div>
            <div style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 5px;">Raw Response:</div>
            <pre id="debugRaw" style="background: #1e293b; padding: 15px; border-radius: 10px; overflow-x: auto; font-size: 0.8rem;"></pre>
        </div>
    </div>

<script>
    // Tuỳ chỉnh URL API dựa trên môi trường của bạn
    const API_URL = '/api/extract-vocab'; 

    async function extractWords() {
        const essay = document.getElementById('essayInput').value.trim();
        const req = document.getElementById('reqInput').value.trim();
        const btn = document.getElementById('extractBtn');
        
        if (!essay) { alert("Vui lòng nhập bài viết!"); return; }

        // Reset Giao diện
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

            // Parse text trước để nếu lỗi có thể xem được raw
            const textRaw = await response.text(); 
            let data;
            
            try {
                data = JSON.parse(textRaw);
            } catch (e) {
                throw new Error("Lỗi định dạng JSON từ Backend trả về.\nRaw data: " + textRaw);
            }

            if (!response.ok || data.error) {
                throw new Error(data.error || `HTTP Error ${response.status}`);
            }

            // --- JAVASCRIPT PHÒNG THỦ (SAFE RENDERING) ---
            
            // 1. Render Coaching (an toàn)
            const coachingText = data.coaching || data.feedback || data.summary || "AI không tạo nhận xét cho bài này.";
            // Thay thế \n bằng thẻ <br> để xuống dòng đẹp mắt
            document.getElementById('coachingContent').innerHTML = coachingText.replace(/\n/g, '<br>');

            // 2. Render Words (Tìm mọi tên mảng có thể, mặc định là mảng rỗng nếu không có)
            const wordsArray = data.words || data.vocabulary || data.vocabList || [];
            
            if (!Array.isArray(wordsArray) || wordsArray.length === 0) {
                document.getElementById('vocabList').innerHTML = "<p style='grid-column: 1/-1;'>Không tìm thấy từ vựng nào phù hợp với yêu cầu.</p>";
            } else {
                let html = '';
                wordsArray.forEach(item => {
                    // Xử lý trường hợp AI lại trả về chuỗi thay vì object
                    if (typeof item === 'string') {
                        html += `
                        <div class="word-card">
                            <h3>${item}</h3>
                        </div>`;
                    } else {
                        // Dùng item.word || 'N/A' để tránh lỗi undefined nếu thuộc tính bị thiếu
                        html += `
                        <div class="word-card">
                            <h3>${item.word || 'N/A'}</h3>
                            <span class="word-ipa">${item.ipa || ''}</span>
                            <div class="word-def">${item.definition || item.meaning || ''}</div>
                            <div class="word-ex">${item.example || ''}</div>
                        </div>`;
                    }
                });
                document.getElementById('vocabList').innerHTML = html;
            }

            // Hiển thị kết quả thành công
            document.getElementById('loader').style.display = "none";
            document.getElementById('resultArea').style.display = "block";

        } catch (error) {
            // Hiển thị Debugger xịn xò
            document.getElementById('loader').style.display = "none";
            document.getElementById('debuggerArea').style.display = "block";
            document.getElementById('debugMsg').innerText = "Lỗi xử lý Frontend: " + error.message;
            console.error("Chi tiết lỗi:", error);
        } finally {
            btn.disabled = false;
        }
    }
</script>
</body>
</html>
