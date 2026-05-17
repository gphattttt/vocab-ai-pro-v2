<?php
// Nạp file kết nối database
include 'db.php';

// Khởi động session để kiểm tra user đăng nhập
session_start();

// Nếu user chưa đăng nhập thì chuyển về trang login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ép kiểu user_id về số nguyên để an toàn hơn khi dùng trong SQL
$user_id = (int) $_SESSION['user_id'];

// Lấy top 10 user theo XP tuần hiện tại
$leaderboard_sql = "
    SELECT 
        id,
        username,
        weekly_xp,
        total_xp
    FROM users
    ORDER BY weekly_xp DESC, total_xp DESC, username ASC
    LIMIT 10
";

// Chạy query lấy leaderboard
$leaderboard_result = $conn->query($leaderboard_sql);

// Lấy thông tin rank của user hiện tại
// Công thức rank: đếm số user có weekly_xp cao hơn user hiện tại rồi + 1
$my_rank_sql = "
    SELECT 
        u.id,
        u.username,
        u.weekly_xp,
        u.total_xp,
        (
            SELECT COUNT(*) + 1
            FROM users other_users
            WHERE other_users.weekly_xp > u.weekly_xp
        ) AS user_rank
    FROM users u
    WHERE u.id = ?
";

// Chuẩn bị query rank cá nhân
$my_rank_stmt = $conn->prepare($my_rank_sql);

// Gắn user_id hiện tại vào query
$my_rank_stmt->bind_param("i", $user_id);

// Chạy query
$my_rank_stmt->execute();

// Lấy kết quả rank cá nhân
$my_rank_result = $my_rank_stmt->get_result();

// Lấy dữ liệu user hiện tại
$my_rank = $my_rank_result->fetch_assoc();

// Tính thời điểm reset kế tiếp: thứ Hai tuần sau lúc 00:00
$next_reset = new DateTime('next monday 00:00:00');

// Tính thời điểm hiện tại
$now = new DateTime();

// Tính khoảng cách từ hiện tại tới lần reset tiếp theo
$reset_diff = $now->diff($next_reset);

// Format text thời gian còn lại
$reset_text = $reset_diff->days . " ngày " . $reset_diff->h . " giờ " . $reset_diff->i . " phút";

// Hàm lấy chữ cái đầu tiên của username để làm avatar
function getInitial($username) {
    return strtoupper(substr(trim($username), 0, 1));
}

// Hàm format số XP cho đẹp
function formatXp($xp) {
    return number_format((int) $xp);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">

    <!-- Giúp giao diện responsive tốt trên điện thoại -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Weekly Leaderboard | Vocab AI Pro</title>

    <!-- Font dùng đồng bộ với style hiện đại của project -->
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ================================
           BIẾN MÀU CHUNG CỦA TRANG
        ================================= */
        :root {
            --primary: #1e293b;
            --accent: #10b981;
            --accent-soft: #ecfdf5;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text-muted: #64748b;
            --gold: #eab308;
            --silver: #64748b;
            --bronze: #ea580c;
            --danger: #ef4444;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            --spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Reset cơ bản để layout ổn định hơn */
        * {
            box-sizing: border-box;
        }

        /* Body chính của trang */
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(16, 185, 129, 0.12), transparent 28%),
                radial-gradient(circle at top right, rgba(234, 179, 8, 0.12), transparent 26%),
                var(--bg);
            margin: 0;
            color: var(--primary);
            overflow-x: hidden;
        }

        /* Animation fade + trượt lên cho card */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Animation nhẹ cho top card */
        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.96);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Wrapper tổng của trang */
        .page-wrap {
            width: min(1120px, calc(100% - 32px));
            margin: 36px auto 70px auto;
        }

        /* Khu vực hero đầu trang */
        .hero {
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 30px;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            align-items: center;
            backdrop-filter: blur(12px);
            animation: slideUp 0.5s var(--spring) forwards;
        }

        /* Nhãn nhỏ trên hero */
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 800;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.82rem;
            margin-bottom: 14px;
        }

        /* Tiêu đề chính */
        .hero h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.4rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
            font-weight: 900;
        }

        /* Mô tả nhỏ dưới tiêu đề */
        .hero p {
            color: var(--text-muted);
            margin: 14px 0 0 0;
            line-height: 1.7;
            max-width: 680px;
        }

        /* Card countdown reset tuần */
        .reset-card {
            background: var(--primary);
            color: white;
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.18);
        }

        /* Label trong reset card */
        .reset-card .label {
            color: #cbd5e1;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        /* Text thời gian reset */
        .reset-card .time {
            font-size: 1.45rem;
            font-weight: 900;
        }

        /* Ghi chú nhỏ trong reset card */
        .reset-card .note {
            margin-top: 10px;
            color: #cbd5e1;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        /* Grid chính gồm bảng leaderboard và card user */
        .content-grid {
            display: grid;
            grid-template-columns: 1.5fr 0.8fr;
            gap: 24px;
            margin-top: 24px;
        }

        /* Card trắng chung */
        .panel {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: slideUp 0.55s var(--spring) forwards;
        }

        /* Header của panel */
        .panel-header {
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        /* Tiêu đề panel */
        .panel-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 900;
        }

        /* Pill nhỏ hiển thị loại leaderboard */
        .panel-pill {
            background: #f1f5f9;
            color: var(--text-muted);
            font-weight: 800;
            font-size: 0.78rem;
            padding: 7px 10px;
            border-radius: 999px;
        }

        /* Danh sách leaderboard */
        .leaderboard-list {
            padding: 14px;
        }

        /* Một dòng user trong leaderboard */
        .rank-row {
            display: grid;
            grid-template-columns: 54px 1fr auto;
            align-items: center;
            gap: 14px;
            padding: 16px;
            border-radius: 20px;
            transition: all 0.28s var(--spring);
            animation: slideUp 0.45s var(--spring) forwards;
            opacity: 0;
        }

        /* Hiệu ứng hover dòng user */
        .rank-row:hover {
            background: #f8fafc;
            transform: translateY(-2px);
        }

        /* Badge số hạng */
        .rank-badge {
            width: 42px;
            height: 42px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: var(--text-muted);
            font-weight: 900;
        }

        /* Khu vực thông tin user */
        .user-info {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }

        /* Avatar chữ cái đầu username */
        .avatar {
            width: 46px;
            height: 46px;
            border-radius: 17px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: 900;
            flex: 0 0 auto;
        }

        /* Tên user */
        .username {
            font-weight: 850;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Text phụ dưới username */
        .subtext {
            color: var(--text-muted);
            font-size: 0.82rem;
            margin-top: 3px;
        }

        /* Pill XP bên phải */
        .xp-pill {
            background: var(--accent-soft);
            color: var(--accent);
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 900;
            white-space: nowrap;
            font-size: 0.9rem;
        }

        /* Style riêng cho hạng 1 */
        .rank-1 {
            background: linear-gradient(90deg, #fef9c3, #ffffff);
            border: 1px solid #fde047;
        }

        .rank-1 .rank-badge,
        .rank-1 .avatar {
            background: #fde047;
            color: #854d0e;
        }

        .rank-1 .xp-pill {
            background: #fef3c7;
            color: #92400e;
        }

        /* Style riêng cho hạng 2 */
        .rank-2 {
            border: 1px solid #cbd5e1;
        }

        .rank-2 .rank-badge,
        .rank-2 .avatar {
            background: #cbd5e1;
            color: #1e293b;
        }

        /* Style riêng cho hạng 3 */
        .rank-3 {
            border: 1px solid #fed7aa;
        }

        .rank-3 .rank-badge,
        .rank-3 .avatar {
            background: #ffedd5;
            color: #c2410c;
        }

        /* Card rank cá nhân */
        .my-rank-card {
            padding: 24px;
        }

        /* Avatar lớn trong card cá nhân */
        .my-avatar {
            width: 74px;
            height: 74px;
            border-radius: 26px;
            background: var(--accent-soft);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.8rem;
            margin-bottom: 16px;
        }

        /* Text hạng cá nhân */
        .my-rank-number {
            font-size: 2.2rem;
            font-weight: 950;
            letter-spacing: -0.05em;
            margin: 8px 0;
        }

        /* Grid thống kê nhỏ */
        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 18px;
        }

        /* Một ô thống kê */
        .stat-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 14px;
        }

        /* Label trong thống kê */
        .stat-label {
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 700;
        }

        /* Value trong thống kê */
        .stat-value {
            font-weight: 900;
            margin-top: 4px;
        }

        /* Empty state nếu chưa có user */
        .empty-state {
            padding: 34px;
            text-align: center;
            color: var(--text-muted);
        }

        /* Responsive cho tablet */
        @media (max-width: 900px) {
            .hero,
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Responsive cho mobile */
        @media (max-width: 560px) {
            .page-wrap {
                width: min(100% - 22px, 1120px);
                margin-top: 20px;
            }

            .hero {
                padding: 22px;
                border-radius: 24px;
            }

            .panel-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .rank-row {
                grid-template-columns: 44px 1fr;
            }

            .xp-pill {
                grid-column: 2;
                width: fit-content;
            }
        }
    </style>
</head>
<body>

<?php include 'nav.php'; ?>

<main class="page-wrap">

    <section class="hero">
        <div>
            <div class="eyebrow">🏆 Weekly Competition</div>
            <h1>Bảng xếp hạng tuần</h1>
            <p>
                Leaderboard hiện xếp hạng <strong>theo tuần</strong>. 
                XP tuần sẽ reset về 0 vào đầu tuần mới.
            </p>
        </div>

        <aside class="reset-card">
            <div class="label">Reset tuần tiếp theo sau</div>
            <div class="time"><?php echo htmlspecialchars($reset_text); ?></div>
            <div class="note">
                Hệ thống dự kiến reset vào 00:00 thứ Hai hằng tuần.
            </div>
        </aside>
    </section>

    <section class="content-grid">

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Top Learners</h2>
                <span class="panel-pill">Sắp xếp theo Weekly XP</span>
            </div>

            <div class="leaderboard-list">
                <?php if ($leaderboard_result && $leaderboard_result->num_rows > 0): ?>
                    <?php
                    $rank = 1;
                    $delay = 0.05;
                    while ($row = $leaderboard_result->fetch_assoc()):
                        $rank_class = ($rank <= 3) ? "rank-" . $rank : "";
                    ?>
                        <div 
                            class="rank-row <?php echo $rank_class; ?>" 
                            style="animation-delay: <?php echo $delay; ?>s;"
                        >
                            <div class="rank-badge">
                                <?php echo $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '#' . $rank)); ?>
                            </div>

                            <div class="user-info">
                                <div class="avatar">
                                    <?php echo htmlspecialchars(getInitial($row['username'])); ?>
                                </div>

                                <div>
                                    <div class="username">
                                        <?php echo htmlspecialchars($row['username']); ?>
                                    </div>
                                    <div class="subtext">
                                        Total XP: <?php echo formatXp($row['total_xp']); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="xp-pill">
                                <?php echo formatXp($row['weekly_xp']); ?> XP
                            </div>
                        </div>
                    <?php
                        $rank++;
                        $delay += 0.06;
                    endwhile;
                    ?>
                <?php else: ?>
                    <div class="empty-state">
                        Chưa có dữ liệu leaderboard.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <aside class="panel my-rank-card">
            <div class="my-avatar">
                <?php echo htmlspecialchars(getInitial($my_rank['username'] ?? 'U')); ?>
            </div>

            <div class="subtext">Your Weekly Rank</div>

            <div class="my-rank-number">
                #<?php echo isset($my_rank['user_rank']) ? (int) $my_rank['user_rank'] : '-'; ?>
            </div>

            <div class="username">
                <?php echo htmlspecialchars($my_rank['username'] ?? 'Unknown User'); ?>
            </div>

            <div class="stat-grid">
                <div class="stat-box">
                    <div class="stat-label">Weekly XP</div>
                    <div class="stat-value">
                        <?php echo formatXp($my_rank['weekly_xp'] ?? 0); ?>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Total XP</div>
                    <div class="stat-value">
                        <?php echo formatXp($my_rank['total_xp'] ?? 0); ?>
                    </div>
                </div>
            </div>
        </aside>

    </section>

</main>

</body>
</html>
