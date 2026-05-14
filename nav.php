<div class="nav-wrapper">
    <div class="menu-toggle" onclick="toggleMenu()">
        <div class="bar"></div>
        <div class="bar" style="width: 16px;"></div>
        <div class="bar"></div>
    </div>
</div>

<div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div>
<div class="side-menu" id="sideMenu">
    <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 40px; color: #10b981;">Vocab AI Pro</div>
    <nav>
        <a href="index.php">🏠 Bảng điều khiển</a>
        <a href="essay_extract.php">📝 Trích xuất bài luận</a>
        <a href="board.php">📚 Kho từ vựng</a>
        <a href="study.php">🎯 Học tập</a>
        <a href="leaderboard.php">🏆 Bảng xếp hạng</a>
        <a href="forum.php">💬 Cộng đồng</a>
        <a href="profile.php">👤 Hồ sơ của tôi</a>
        <a href="logout.php" style="color: #ef4444; margin-top: 20px; border: none;">🚪 Đăng xuất</a>
    </nav>
</div>

<style>
    /* Universal Menu Styles */
    .nav-wrapper { 
        position: fixed; 
        top: 20px; 
        left: 20px; 
        z-index: 1000; 
    }
    
    .menu-toggle { 
        cursor: pointer;
        display: flex; 
        flex-direction: column; 
        gap: 5px; 
        padding: 10px; 
        background: white; 
        border-radius: 12px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0; 
        /* Replaced bouncy spring with smooth ease-out */
        transition: 0.3s ease-out; 
    }
    
    .menu-toggle:hover { 
        transform: translateY(-2px); 
        border-color: #10b981;
    }
    
    .bar { 
        width: 22px; 
        height: 3px; 
        background-color: #1e293b; 
        border-radius: 2px;
    }
    
    .side-menu { 
        position: fixed;
        top: 0; 
        left: -300px; /* Slightly wider for better spacing */
        width: 300px; 
        height: 100%; 
        background: white; 
        box-shadow: 10px 0 30px rgba(0,0,0,0.05);
        /* Replaced bouncy cubic-bezier with linear slide */
        transition: 0.4s ease-in-out; 
        z-index: 1001; 
        padding: 40px 30px; 
        box-sizing: border-box;
        font-family: 'Be Vietnam Pro', sans-serif;
    }
    
    .side-menu.active { 
        left: 0;
    }
    
    .menu-overlay { 
        position: fixed;
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        background: rgba(15, 23, 42, 0.4); /* Darker overlay for better focus */
        display: none; 
        z-index: 999; 
        backdrop-filter: blur(8px); /* Increased blur for high-end feel */
        transition: opacity 0.3s ease;
    }
    
    .menu-overlay.active { 
        display: block; 
    }
    
    nav a { 
        display: block;
        padding: 18px 0; 
        text-decoration: none; 
        color: #1e293b; 
        font-weight: 600; 
        border-bottom: 1px solid #f8fafc; 
        transition: 0.2s ease;
        font-size: 1.05rem;
    }
    
    nav a:hover { 
        color: #10b981; 
        padding-left: 8px;
        background: rgba(16, 185, 129, 0.04);
        border-radius: 8px;
    }
</style>

<script>
    function toggleMenu() {
        document.getElementById('sideMenu').classList.toggle('active');
        document.getElementById('menuOverlay').classList.toggle('active');
    }
</script>
