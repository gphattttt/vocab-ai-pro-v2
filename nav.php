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
        <a href="index.php">🏠 Dashboard</a>
        <a href="essay_extract.php">📝 Essay Extractor</a>
        <a href="board.php">📚 My Vault</a>
        <a href="study.php">🎯 Study</a>
        <a href="leaderboard.php">🏆 Hall of Fame</a>
        <a href="forum.php">💬 Community</a>
        <a href="profile.php">👤 My Profile</a>
        <a href="logout.php" style="color: #ef4444; margin-top: 20px; border: none;">🚪 Logout</a>
    </nav>
</div>

<style>
    /* Universal Menu Styles */
    .nav-wrapper { position: fixed; top: 20px; left: 20px; z-index: 1000; }
    .menu-toggle { 
        cursor: pointer; display: flex; flex-direction: column; gap: 5px; 
        padding: 10px; background: white; border-radius: 12px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; 
        transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
    }
    .menu-toggle:hover { transform: scale(1.1); border-color: #10b981; }
    .bar { width: 22px; height: 3px; background-color: #1e293b; border-radius: 2px; }
    
    .side-menu { 
        position: fixed; top: 0; left: -280px; width: 280px; height: 100%; 
        background: white; box-shadow: 5px 0 25px rgba(0,0,0,0.05); 
        transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        z-index: 1001; padding: 30px; box-sizing: border-box;
    }
    .side-menu.active { left: 0; }
    
    .menu-overlay { 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.2); display: none; z-index: 999; 
        backdrop-filter: blur(4px); 
    }
    .menu-overlay.active { display: block; }
    
    nav a { 
        display: block; padding: 15px 0; text-decoration: none; 
        color: #1e293b; font-weight: 700; border-bottom: 1px solid #f1f5f9; 
        transition: 0.2s; 
    }
    nav a:hover { color: #10b981; padding-left: 10px; }
</style>

<script>
    function toggleMenu() {
        document.getElementById('sideMenu').classList.toggle('active');
        document.getElementById('menuOverlay').classList.toggle('active');
    }
</script>