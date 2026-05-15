<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}
$user_id = $_SESSION['user_id'];

// 1. Fetch User Info
$stmt = $conn->prepare("SELECT username, email, xp, bio, profile_pic, created_at, has_taken_test, study_path, current_level, roadmap_text FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Fetch User's Posts
$stmt_posts = $conn->prepare("SELECT q.*, (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as reply_count 
                              FROM questions q 
                              WHERE q.user_id = ? 
                              ORDER BY q.created_at DESC");
$stmt_posts->bind_param("i", $user_id);
$stmt_posts->execute();
$my_posts_query = $stmt_posts->get_result();
$stmt_posts->close();

// 3. Stats & Leveling Logic
$stmt_rank = $conn->prepare("SELECT COUNT(*) + 1 as `rank` FROM users WHERE xp > (SELECT xp FROM users WHERE id = ?)");
$stmt_rank->bind_param("i", $user_id);
$stmt_rank->execute();
$rank_res = $stmt_rank->get_result();
$rank = ($rank_res && $rank_res->num_rows > 0) ? $rank_res->fetch_assoc()['rank'] : "N/A";
$stmt_rank->close();

$stmt_words = $conn->prepare("SELECT COUNT(*) as total FROM vocabularies WHERE user_id = ?");
$stmt_words->bind_param("i", $user_id);
$stmt_words->execute();
$words_res = $stmt_words->get_result();
$total_words = ($words_res && $words_res->num_rows > 0) ? $words_res->fetch_assoc()['total'] : 0;
$stmt_words->close();

$xp = $user['xp'] ?? 0;
$level = floor($xp / 100) + 1; 

// 4. Lấy dữ liệu biểu đồ (7 ngày gần nhất)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($date));
    
    $stmt_chart = $conn->prepare("SELECT COUNT(*) as cnt FROM vocabularies WHERE user_id = ? AND DATE(created_at) = ?");
    $stmt_chart->bind_param("is", $user_id, $date);
    $stmt_chart->execute();
    $chart_data[] = $stmt_chart->get_result()->fetch_assoc()['cnt'];
    $stmt_chart->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ của tôi | Vocab AI Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #1e293b; --accent: #10b981; --bg: #f8fafc; --border: #e2e8f0; --text-muted: #64748b; --transition: cubic-bezier(0.4, 0, 0.2, 1); }
        body { font-family: 'Be Vietnam Pro', sans-serif; background: var(--bg); margin: 0; padding: 40px; color: var(--primary); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: slideUp 0.6s var(--transition) forwards; opacity: 0; }
        .container { max-width: 800px; margin: 60px auto 0 auto; }
        
        .profile-card { background: white; border-radius: 32px; padding: 40px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.03); text-align: center; margin-bottom: 30px; }
        .avatar-container { position: relative; width: 110px; height: 110px; margin: 0 auto 20px auto; }
        .avatar-large { width: 100%; height: 100%; border-radius: 35px; object-fit: cover; background: linear-gradient(135deg, #10b981, #3b82f6); box-shadow: 0 12px 24px rgba(0,0,0,0.1); border: 4px solid white; }
        .bio-text { color: var(--text-muted); font-size: 0.95rem; margin: 15px auto; max-width: 450px; line-height: 1.6; }

        .tab-nav { display: flex; justify-content: center; gap: 40px; margin-bottom: 30px; border-bottom: 2px solid var(--border); overflow-x: auto; white-space: nowrap; padding-bottom: 10px; }
        .tab-btn { background: none; border: none; padding: 15px 5px; font-weight: 800; font-size: 0.95rem; cursor: pointer; color: var(--text-muted); border-bottom: 3px solid transparent; transition: 0.3s; font-family: inherit; }
        .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: slideUp 0.4s var(--transition) forwards; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-item { background: white; padding: 22px; border-radius: 24px; border: 1px solid var(--border); transition: 0.3s var(--transition); text-align: center; }
        .stat-item:hover { transform: translateY(-5px); border-color: var(--accent); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.05); }

        /* --- DASHBOARD LỘ TRÌNH CSS --- */
        .chart-container { background: white; padding: 30px; border-radius: 30px; border: 1px solid var(--border); margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .level-badge { display: inline-block; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; padding: 10px 20px; border-radius: 20px; font-weight: 800; font-size: 1.2rem; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3); }
        .ai-feedback { background: #f0fdf4; border-left: 5px solid var(--accent); padding: 25px; border-radius: 20px; margin-bottom: 20px; font-size: 1.05rem; line-height: 1.6; color: #064e3b; }
        .ai-roadmap { background: white; border: 2px solid var(--border); padding: 30px; border-radius: 25px; line-height: 1.8; }
        .ai-roadmap h4 { margin-top: 0; color: var(--primary); font-size: 1.2rem; font-weight: 800; }
        .ai-roadmap ul { padding-left: 20px; }
        .ai-roadmap li { margin-bottom: 15px; }

        .post-card { background: white; border-radius: 20px; padding: 25px; border: 1px solid var(--border); margin-bottom: 15px; text-decoration: none; color: inherit; display: block; text-align: left; transition: 0.3s var(--transition); }
        .post-card:hover { transform: scale(1.02); border-color: var(--accent); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }

        .edit-form { text-align: left; }
        .input-group { margin-bottom: 24px; }
        .input-group label { display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; color: var(--text-muted); letter-spacing: 0.05em; }
        .input-field { width: 100%; padding: 16px; border-radius: 16px; border: 2px solid var(--border); font-family: inherit; box-sizing: border-box; transition: 0.3s; font-size: 0.95rem; }
        .input-field:focus { outline: none; border-color: var(--accent); background: #f0fdf4; }
        .btn-update { width: 100%; padding: 20px; background: var(--primary); color: white; border: none; border-radius: 18px; font-weight: 800; cursor: pointer; margin-top: 10px; transition: 0.3s var(--transition); font-size: 1rem; }
        .btn-update:hover { background: var(--accent); transform: translateY(-3px); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.2); }
        .file-input-wrapper { display: flex; align-items: center; gap: 15px; background: #f8fafc; padding: 12px; border-radius: 16px; border: 1px dashed var(--border); }

        @media (max-width: 600px) { body { padding: 20px; padding-top: 60px; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="profile-card animate-in">
            <div class="avatar-container">
                <img src="uploads/avatars/<?php echo htmlspecialchars($user['profile_pic'] ?: 'default_avatar.png'); ?>" class="avatar-large" id="headerAvatar">
            </div>
            <h1 style="margin:0; font-weight:800; font-size: 2.2rem; letter-spacing: -1px;"><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="bio-text"><?php echo !empty($user['bio']) ? htmlspecialchars($user['bio']) : "Người khám phá Gemini. Bậc thầy từ vựng. Chưa có giới thiệu."; ?></p>
            <div style="font-size: 0.8rem; font-weight: 900; color: var(--accent); letter-spacing: 1.5px; text-transform: uppercase;">
                CẤP ĐỘ <?php echo $level; ?> MASTER
            </div>
        </div>

        <div class="tab-nav animate-in" style="animation-delay: 0.1s;">
            <button class="tab-btn active" onclick="openTab(event, 'academic')">Học Thuật & Lộ Trình</button>
            <button class="tab-btn" onclick="openTab(event, 'overview')">Cài đặt Tài khoản</button>
            <button class="tab-btn" onclick="openTab(event, 'history')">Thảo luận (<?php echo $my_posts_query->num_rows; ?>)</button>
        </div>

        <!-- TAB 1: ACADEMIC DASHBOARD (MỚI) -->
        <div id="academic" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-item">
                    <span style="display:block; font-size:1.8rem; font-weight:800; color: var(--accent);"><?php echo $total_words; ?></span>
                    <span style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">TỪ ĐÃ LƯU</span>
                </div>
                <div class="stat-item">
                    <span style="display:block; font-size:1.8rem; font-weight:800;">#<?php echo htmlspecialchars($rank); ?></span>
                    <span style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">XẾP HẠNG</span>
                </div>
                <div class="stat-item">
                    <span style="display:block; font-size:1.8rem; font-weight:800;"><?php echo $xp; ?></span>
                    <span style="font-size:0.7rem; font-weight:700; color:var(--text-muted);">TỔNG XP</span>
                </div>
            </div>

            <!-- Biểu đồ Chart.js -->
            <div class="chart-container">
                <h3 style="margin-top:0; font-weight:800; margin-bottom: 20px;">Tần suất học 7 ngày qua</h3>
                <canvas id="vocabChart" height="100"></canvas>
            </div>

            <!-- Kết quả AI Chấm điểm -->
            <?php if($user['has_taken_test'] == 1 && !empty($user['roadmap_text'])): ?>
                <div style="margin-bottom: 40px;">
                    <h3 style="font-weight: 800; margin-bottom: 20px;">Trình độ hiện tại</h3>
                    <div class="level-badge">🏆 Trình độ: <?php echo htmlspecialchars($user['current_level']); ?></div>
                    
                    <!-- Hiển thị Roadmap HTML từ DB -->
                    <?php echo $user['roadmap_text']; ?>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button onclick="retakeTest()" style="background: none; border: 2px solid var(--border); padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; color: var(--text-muted);">Làm lại bài kiểm tra 🔄</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-container" style="text-align: center; padding: 50px 20px;">
                    <h3 style="font-weight: 800;">Bạn chưa hoàn thành bài đánh giá năng lực</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Làm bài kiểm tra ngay để AI xây dựng lộ trình dành riêng cho bạn.</p>
                    <a href="index.php" style="background: var(--accent); color: white; padding: 15px 30px; border-radius: 15px; text-decoration: none; font-weight: 800; display: inline-block;">Làm bài kiểm tra</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB 2: OVERVIEW & EDIT -->
        <div id="overview" class="tab-content">
            <div class="profile-card edit-form">
                <h3 style="margin-top:0; margin-bottom: 30px; font-weight: 800;">Cập nhật thông tin cá nhân</h3>
                <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label>Ảnh đại diện</label>
                        <div class="file-input-wrapper">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($user['profile_pic'] ?: 'default_avatar.png'); ?>" id="previewImg" style="width: 55px; height: 55px; border-radius: 15px; object-fit: cover;">
                            <input type="file" name="profile_pic" accept="image/*" onchange="preview(this)">
                        </div>
                    </div>
                    <div class="input-group"><label>Tên hiển thị</label><input type="text" name="new_username" class="input-field" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
                    <div class="input-group"><label>Địa chỉ Email</label><input type="email" name="new_email" class="input-field" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                    <div class="input-group"><label>Giới thiệu bản thân</label><textarea name="bio" class="input-field" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea></div>
                    <div class="input-group"><label>Mật khẩu mới (Tùy chọn)</label><input type="password" name="new_password" class="input-field" placeholder="Để trống nếu không muốn đổi"></div>
                    <div class="input-group" style="margin-top: 35px; padding: 25px; background: #fffbeb; border-radius: 24px; border: 1px solid #fef3c7;">
                        <label style="color: #92400e;">Xác nhận danh tính (Mật khẩu hiện tại)</label>
                        <input type="password" name="current_password" class="input-field" style="border-color: #fde68a;" placeholder="Bắt buộc để lưu thay đổi" required>
                    </div>
                    <button type="submit" class="btn-update">✨ Lưu tất cả thay đổi</button>
                </form>
            </div>
            <div style="text-align: center; margin-bottom: 40px;"><a href="logout.php" style="color: #ef4444; text-decoration: none; font-weight: 700; font-size: 0.95rem;">Đăng xuất khỏi Vocab AI Pro</a></div>
        </div>

        <!-- TAB 3: HISTORY -->
        <div id="history" class="tab-content">
            <?php if ($my_posts_query->num_rows > 0): ?>
                <?php while($post = $my_posts_query->fetch_assoc()): ?>
                    <a href="view_question.php?id=<?php echo $post['id']; ?>" class="post-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin:0; font-weight: 700;"><?php echo htmlspecialchars($post['title']); ?></h4>
                            <span style="font-size: 0.7rem; background: #f1f5f9; padding: 5px 10px; border-radius: 8px; font-weight: 800; color: var(--text-muted);">ĐÃ ĐĂNG</span>
                        </div>
                        <div style="margin-top: 12px; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">
                            <span>📅 <?php echo date("d/m/Y", strtotime($post['created_at'])); ?></span>
                            <span style="margin-left: 20px;">💬 <?php echo $post['reply_count']; ?> Bình luận</span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 80px 40px; color: var(--text-muted); background: white; border-radius: 32px; border: 1px solid var(--border);">
                    <p style="font-weight: 600; margin-bottom: 15px;">Bạn chưa tham gia thảo luận nào.</p>
                    <a href="ask_question.php" style="color: var(--accent); font-weight: 800; text-decoration: none; font-size: 1rem;">Đặt câu hỏi đầu tiên ngay!</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
    // Tab Switching Logic
    function openTab(evt, tabName) {
        const contents = document.getElementsByClassName("tab-content");
        for (let i = 0; i < contents.length; i++) contents[i].classList.remove("active");
        
        const buttons = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < buttons.length; i++) buttons[i].classList.remove("active");
        
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    // Chart.js Logic (Vẽ biểu đồ)
    const ctx = document.getElementById('vocabChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Từ vựng đã lưu',
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] }, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });

    function preview(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById('previewImg').src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function retakeTest() {
        if(confirm("Bạn có chắc muốn làm lại bài kiểm tra? Lộ trình cũ sẽ bị ghi đè!")) {
            // Đặt lại session qua index.php
            window.location.href = 'index.php?retake=1';
        }
    }
</script>
</body>
</html>
