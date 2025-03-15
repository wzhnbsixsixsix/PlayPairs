<?php
// 修改后的会话启动方式（带状态检查）
if (session_status() === PHP_SESSION_NONE) {
    session_id(uniqid());
    session_start();
}

// 新增会话活性检测
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();

// 防止会话固定攻击
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// 处理注销请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    // 删除头像文件（如果存在）
    if (isset($_SESSION['avatar']) && file_exists($_SESSION['avatar'])) {
        if (isset($_SESSION['username']) && strpos($_SESSION['avatar'], $_SESSION['username']) !== false) {
            unlink($_SESSION['avatar']);
        }
    }

    // 清除会话
    session_regenerate_id(true);
    session_unset();
    session_destroy();

    // 清除客户端 Cookie
    setcookie("username", "", time() - 3600, "/");
    setcookie("avatar", "", time() - 3600, "/");

    header("Location: index.php");
    exit();
}
?>
<!-- 新增：永久存在的背景音乐播放器 -->
<audio id="bgMusic" src="bgmusic.mp3" loop data-turbo-permanent style="display:none;"></audio>
<link rel="stylesheet" href="css/logout.css">
<link rel="stylesheet" href="css/navbutton.css">
<!-- 导航栏 -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container navbut1 navbar-container">

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav w-100">

                <li class="nav-item">
                    <a class="nav-link navbar-item" href="index.php">Home<span class="navbar-item_label">Home</span></a>
                </li>

                <li class="nav-item">
                    <a class="nav-link navbar-item" href="pairs.php">Play Pairs<span class="navbar-item_label">Play Pairs</span></a>
                </li>

                <?php if (isset($_SESSION['username'])): ?>
                    <li class="nav-item">
                        <a class="nav-link navbar-item" href="leaderboard.php">Leaderboard<span class="navbar-item_label">Leaderboard</span></a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <img src="<?= $_SESSION['avatar'] ?? 'default_avatar.png' ?>"
                            class="rounded-circle border border-3 border-white"
                            width="40"
                            height="40"
                            alt="User Avatar">
                    </li>
                    <li class="nav-item ms-2">
                        <form method="post" class="d-inline">
                            <button type="submit" name="logout"
                                class="logout"
                                onclick="return confirm('Are you sure you want to logout?')">
                                <div class="sign"><svg viewBox="0 0 512 512">
                                        <path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path>
                                    </svg></div>
                                <div class="text">Logout</div>
                            </button>
                        </form>
                    </li>

                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link navbar-item" href="registration.php">Register<span class="navbar-item_label">Home</span></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- 音乐控制按钮 -->
<div id="button-container-1" data-turbo-permanent
    style="
    position: fixed; 
    z-index: 1000; 
    width: 50px; 
    height: 50px;
    right:10px ;
    top:5px">
    <button type="button" class="music-btn" onclick="event.srcElement.classList.toggle('playing');"></button>
    <div class="visual-layer">
        <div class="animation-layer">
            <span></span><span></span><span></span>
            <span></span><span></span><span></span>
        </div>
    </div>
</div>

<!-- 引入 Turbo Drive -->
<script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.1.0/dist/turbo.min.js"></script>
<script>
    // 状态存储键名 - 改用 sessionStorage
    const MUSIC_STATE_KEY = 'music_state';
    const DRAG_POSITION_KEY = 'music_control_position';

    const control = document.getElementById('button-container-1');
    const musicButton = control.querySelector('button');
    let isDragging = false,
        hasDragged = false;
    let offsetX = 0,
        offsetY = 0,
        startX = 0,
        startY = 0;
    const DRAG_THRESHOLD = 5; // 5px 内不算拖动

    control.addEventListener('mousedown', (e) => {
        isDragging = true;
        hasDragged = false; // 每次按下重置拖拽标识
        startX = e.clientX;
        startY = e.clientY;
        const rect = control.getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
        e.preventDefault();
    });

    window.MUSIC_STATE_KEY = MUSIC_STATE_KEY;
    window.toggleBgMusic = function() {
        if (bgMusic.paused) {
            bgMusic.play().then(() => {
                musicButton.classList.add('playing');
                saveState();
            }).catch(err => {
                console.error('播放失败:', err);
                musicButton.classList.remove('playing');
            });
        } else {
            bgMusic.pause();
            musicButton.classList.remove('playing');
            saveState();
        }
    };

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        // 如果移动距离超过阈值，则认为发生了拖拽
        if (!hasDragged && (Math.abs(e.clientX - startX) > DRAG_THRESHOLD || Math.abs(e.clientY - startY) > DRAG_THRESHOLD)) {
            hasDragged = true;
        }
        if (isDragging) {
            const newX = e.clientX - offsetX;
            const newY = e.clientY - offsetY;
            control.style.left = newX + 'px';
            control.style.top = newY + 'px';
        }
    });

    // 在拖拽结束时保存位置
    document.addEventListener('mouseup', () => {
        isDragging = false;

        // 保存拖拽位置 - 改用 sessionStorage
        if (hasDragged) {
            sessionStorage.setItem(DRAG_POSITION_KEY, JSON.stringify({
                left: parseInt(control.style.left),
                top: parseInt(control.style.top)
            }));
        }
    });

    // 在捕获阶段拦截click事件
    musicButton.addEventListener('click', function(e) {
        if (hasDragged) {
            // 如果是拖拽产生的click，则阻止事件继续传递（包括inline onclick）
            e.stopImmediatePropagation();
            e.preventDefault();
            // 重置拖拽标记，避免下次点击受影响
            hasDragged = false;
        }
    }, true);

    // 恢复拖拽位置 - 改用 sessionStorage
    const savedPos = sessionStorage.getItem(DRAG_POSITION_KEY);
    if (savedPos) {
        try {
            const pos = JSON.parse(savedPos);
            // 添加边界检查
            const maxX = window.innerWidth - control.offsetWidth;
            const maxY = window.innerHeight - control.offsetHeight;

            control.style.left = Math.min(Math.max(pos.left, 0), maxX) + 'px';
            control.style.top = Math.min(Math.max(pos.top, 0), maxY) + 'px';
        } catch (e) {
            console.error('解析拖拽位置失败', e);
        }
    }

    // 修改：使用页面中永久的 audio 元素，不再新建 Audio 实例
    window.bgMusic = document.getElementById('bgMusic');

    bgMusic.loop = true;
    bgMusic.autostart = "true";

    // 从 sessionStorage 中恢复状态
    let savedState = {
        playing: false,
        time: 0,
        savedTimestamp: Date.now()
    };

    try {
        const state = sessionStorage.getItem(MUSIC_STATE_KEY);
        if (state) savedState = JSON.parse(state);
    } catch (e) {
        console.error('状态解析失败', e);
    }

    let isMusicPlaying = savedState.playing || false; // 从保存的状态初始化
    if (savedState.playing) {
        const elapsed = (Date.now() - savedState.savedTimestamp) / 1000;
        bgMusic.currentTime = savedState.time + elapsed;
        bgMusic.play().then(() => {
            musicButton.classList.add('playing');
        }).catch(err => {
            console.log('自动播放失败，需要用户交互', err);
            isMusicPlaying = false;
            musicButton.classList.remove('playing');
        });
    }

    bgMusic.addEventListener('pause', () => {
        musicButton.classList.remove('playing');
    });

    bgMusic.addEventListener('loadedmetadata', () => {
        if (isMusicPlaying) {
            bgMusic.play().catch(err => {
                console.log('自动播放失败，需要用户交互', err);
                control.classList.remove('playing');
                isMusicPlaying = false;
            });
        }
    });

    // 在保存位置时同步保存动画状态 - 改用 sessionStorage
    function saveState() {
        sessionStorage.setItem(MUSIC_STATE_KEY, JSON.stringify({
            playing: !bgMusic.paused,
            time: bgMusic.currentTime,
            savedTimestamp: Date.now(),
            animationState: musicButton.classList.contains('playing'),
            volume: bgMusic.volume
        }));
    }

    // 恢复时添加动画状态
    if (savedState.animationState) {
        musicButton.classList.add('playing');
    }

    // 使用 unload 事件而非 beforeunload 来更准确地保存状态
    window.addEventListener('unload', saveState);

    // 更频繁地保存状态以确保精确性
    setInterval(saveState, 500);

    // 修改 turbo:load 事件处理程序，确保恢复播放位置
    document.addEventListener('turbo:load', () => {
        const savedState = JSON.parse(sessionStorage.getItem(MUSIC_STATE_KEY) || '{}');

        // 恢复音量
        if (savedState.volume) {
            bgMusic.volume = savedState.volume;
        }

        // 重要：恢复播放位置
        if (savedState.time !== undefined) {
            // 计算经过的时间（如果正在播放）
            if (savedState.playing) {
                const elapsed = (Date.now() - savedState.savedTimestamp) / 1000;
                bgMusic.currentTime = savedState.time + elapsed;
            } else {
                bgMusic.currentTime = savedState.time;
            }
        }

        // 恢复播放状态
        if (savedState.playing && bgMusic.paused) {
            bgMusic.play().then(() => {
                isMusicPlaying = true;
                musicButton.classList.add('playing');
            }).catch(err => {
                console.log('播放恢复失败:', err);
                isMusicPlaying = false;
                musicButton.classList.remove('playing');
            });
        }
    });

    // 修改：在页面切换前保存当前播放状态和时间
    document.addEventListener('turbo:before-visit', () => {
        saveState();
        // 防止音频重新加载，在 window 对象上保存实际播放状态和时间
        window._bgMusicState = {
            currentTime: bgMusic.currentTime,
            paused: bgMusic.paused,
            volume: bgMusic.volume
        };
    });

    // 修改: 改进处理页面切换时的重载逻辑
    document.addEventListener('turbo:before-render', (event) => {
        // 保存当前的播放状态
        saveState();

        // 保存精确的音频元素状态
        const currentState = {
            currentTime: bgMusic.currentTime,
            paused: bgMusic.paused,
            volume: bgMusic.volume
        };

        // 确保新页面中的音频元素保持相同状态
        const newBody = event.detail.newBody;
        if (!newBody.querySelector('#bgMusic')) {
            const audioClone = bgMusic.cloneNode(true);
            audioClone.setAttribute('data-turbo-permanent', '');
            newBody.insertBefore(audioClone, newBody.firstChild);
        }

        // 重要：将当前状态存储到 window 对象和 sessionStorage 中
        window._bgMusicPreciseState = currentState;
        sessionStorage.setItem('_bgMusicPreciseState', JSON.stringify(currentState));
    });

    // 修改：确保在新页面渲染后音频状态正确恢复
    document.addEventListener('turbo:render', () => {
        // 获取对新页面中音频元素的引用
        const audio = document.getElementById('bgMusic');

        // 首先尝试从 window 对象恢复状态
        let preciseState = window._bgMusicPreciseState;

        // 如果 window 对象中没有，则从 sessionStorage 中恢复
        if (!preciseState) {
            try {
                const savedPreciseState = sessionStorage.getItem('_bgMusicPreciseState');
                if (savedPreciseState) {
                    preciseState = JSON.parse(savedPreciseState);
                }
            } catch (e) {
                console.error('无法从 sessionStorage 恢复精确状态', e);
            }
        }

        // 从精确保存的状态恢复
        if (preciseState) {
            // 恢复音量
            audio.volume = preciseState.volume;

            // 恢复播放位置
            audio.currentTime = preciseState.currentTime;

            // 恢复播放状态
            if (!preciseState.paused) {
                audio.play().then(() => {
                    musicButton.classList.add('playing');
                }).catch(e => {
                    console.error('无法恢复播放:', e);
                    musicButton.classList.remove('playing');
                });
            } else {
                audio.pause();
                musicButton.classList.remove('playing');
            }

            // 清理
            delete window._bgMusicPreciseState;
            sessionStorage.removeItem('_bgMusicPreciseState');
        }
    });

    // 修改点击事件处理
    control.addEventListener('click', (e) => {
        if (e.target !== musicButton) return;

        isMusicPlaying = !isMusicPlaying;
        if (isMusicPlaying) {
            bgMusic.play().then(() => {
                musicButton.classList.add('playing');
            }).catch(err => {
                console.error('播放失败:', err);
                isMusicPlaying = false;
                musicButton.classList.remove('playing');
            });
        } else {
            bgMusic.pause();
            musicButton.classList.remove('playing');
        }
        saveState();
    });

    // 确保音频元素不被 Turbo Drive 重新加载
    bgMusic.setAttribute('data-turbo-permanent', '');
    control.setAttribute('data-turbo-permanent', '');
</script>