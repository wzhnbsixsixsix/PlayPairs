<?php
// Set cookie expiration time to 30 minutes from now
$cookieExpire = time() + 1800;

// Handle logout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    // Retrieve username from cookie before it is cleared
    $username = $_COOKIE['username'] ?? '';

    // Delete avatar file if it exists and contains the username
    if (isset($_COOKIE['avatar']) && file_exists($_COOKIE['avatar'])) {
        if (strpos($_COOKIE['avatar'], $username) !== false) {
            unlink($_COOKIE['avatar']);
        }
    }

    // Clear user identity cookies
    setcookie("username", "", time() - 3600, "/");
    setcookie("avatar", "", time() - 3600, "/");

    // Handle leaderboard data (must retrieve username before clearing cookies)
    // Read the leaderboard data from the JSON file
    $leaderboardData = json_decode(file_get_contents('leaderboard_data.json'), true);
    if (isset($_COOKIE['leaderboard'])) {
        $leaderboard = unserialize($_COOKIE['leaderboard']);
        if (is_array($leaderboard)) {
            // Filter out all records for the current user
            $leaderboard = array_filter($leaderboard, function($entry) use ($username) {
                return ($entry['username'] ?? '') !== $username;
            });
            // Filter out the user's entry from the leaderboard data
            $leaderboardData = array_filter($leaderboardData, function($entry) use ($username) {
                return ($entry['username'] ?? '') !== $username;
            });

            // Save updated leaderboard back to the JSON file
            file_put_contents('leaderboard_data.json', json_encode(array_values($leaderboardData)));
            setcookie('leaderboard', serialize($leaderboard), time() + 3600, '/');
        }
    }

    header("Location: index.php");
    exit();
}

// If the user is logged in, extend the cookie expiration
if (isset($_COOKIE['username'])) {
    setcookie("username", $_COOKIE['username'], $cookieExpire, "/");
    if (isset($_COOKIE['avatar'])) {
        setcookie("avatar", $_COOKIE['avatar'], $cookieExpire, "/");
    }
}
?>
<!-- HTML code starts here -->
<!-- Permanent background music player -->
<audio id="bgMusic" src="bgmusic.mp3" loop data-turbo-permanent style="display:none;"></audio>
<link rel="stylesheet" href="css/logout.css">
<link rel="stylesheet" href="css/navbutton.css">
<!-- Navigation bar -->
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
                <?php if (isset($_COOKIE['username'])): ?>
                    <li class="nav-item">
                        <a class="nav-link navbar-item" href="leaderboard.php">Leaderboard<span class="navbar-item_label">Leaderboard</span></a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <img src="<?= $_COOKIE['avatar'] ?? 'default_avatar.png' ?>"
                            class="rounded-circle border border-3 border-white"
                            width="40"
                            height="40"
                            alt="User Avatar">
                    </li>
                    <li class="nav-item ms-2">
                        <form method="post" class="d-inline" action="navbar.php">
                            <button type="submit" name="logout" 
                                class="logout"
                                onclick="return confirm('Are you sure you want to logout? Logging out will permanently delete your account.')">
                                <div class="sign">
                                    <svg viewBox="0 0 512 512">
                                        <path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path>
                                    </svg>
                                </div>
                                <div class="text">Logout</div>
                            </button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link navbar-item" href="registration.php">Register<span class="navbar-item_label">Register</span></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Music control button -->
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

<!-- Include Turbo Drive -->
<script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.1.0/dist/turbo.min.js"></script>
<script>
    // State storage key names - using sessionStorage
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
    const DRAG_THRESHOLD = 5; // 5px threshold for drag detection

    control.addEventListener('mousedown', (e) => {
        isDragging = true;
        hasDragged = false; // Reset drag flag on each press
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
                console.error('Playback failed:', err);
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
        // If the movement exceeds the threshold, consider it a drag
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

    // Save position on drag end
    document.addEventListener('mouseup', () => {
        isDragging = false;

        // Save drag position - using sessionStorage
        if (hasDragged) {
            sessionStorage.setItem(DRAG_POSITION_KEY, JSON.stringify({
                left: parseInt(control.style.left),
                top: parseInt(control.style.top)
            }));
        }
    });

    // Intercept click events during capture phase
    musicButton.addEventListener('click', function(e) {
        if (hasDragged) {
            // If the click was caused by dragging, prevent further event propagation
            e.stopImmediatePropagation();
            e.preventDefault();
            // Reset drag flag to avoid affecting the next click
            hasDragged = false;
        }
    }, true);

    // Restore drag position - using sessionStorage
    const savedPos = sessionStorage.getItem(DRAG_POSITION_KEY);
    if (savedPos) {
        try {
            const pos = JSON.parse(savedPos);
            // Add boundary checks
            const maxX = window.innerWidth - control.offsetWidth;
            const maxY = window.innerHeight - control.offsetHeight;

            control.style.left = Math.min(Math.max(pos.left, 0), maxX) + 'px';
            control.style.top = Math.min(Math.max(pos.top, 0), maxY) + 'px';
        } catch (e) {
            console.error('Failed to parse drag position', e);
        }
    }

    // Use the permanent audio element on the page, no need to create a new Audio instance
    window.bgMusic = document.getElementById('bgMusic');

    bgMusic.loop = true;
    bgMusic.autostart = "true";

    // Restore state from sessionStorage
    let savedState = {
        playing: false,
        time: 0,
        savedTimestamp: Date.now()
    };

    try {
        const state = sessionStorage.getItem(MUSIC_STATE_KEY);
        if (state) savedState = JSON.parse(state);
    } catch (e) {
        console.error('State parsing failed', e);
    }

    let isMusicPlaying = savedState.playing || false; // Initialize from saved state
    if (savedState.playing) {
        const elapsed = (Date.now() - savedState.savedTimestamp) / 1000;
        bgMusic.currentTime = savedState.time + elapsed;
        bgMusic.play().then(() => {
            musicButton.classList.add('playing');
        }).catch(err => {
            console.log('Auto-play failed, user interaction required', err);
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
                console.log('Auto-play failed, user interaction required', err);
                control.classList.remove('playing');
                isMusicPlaying = false;
            });
        }
    });

    // Sync animation state when saving position - using sessionStorage
    function saveState() {
        sessionStorage.setItem(MUSIC_STATE_KEY, JSON.stringify({
            playing: !bgMusic.paused,
            time: bgMusic.currentTime,
            savedTimestamp: Date.now(),
            animationState: musicButton.classList.contains('playing'),
            volume: bgMusic.volume
        }));
    }

    // Restore animation state
    if (savedState.animationState) {
        musicButton.classList.add('playing');
    }

    // Use unload event instead of beforeunload for more accurate state saving
    window.addEventListener('unload', saveState);

    // Save state more frequently to ensure accuracy
    setInterval(saveState, 500);

    // Modify turbo:load event handler to ensure playback position is restored
    document.addEventListener('turbo:load', () => {
        const savedState = JSON.parse(sessionStorage.getItem(MUSIC_STATE_KEY) || '{}');

        // Restore volume
        if (savedState.volume) {
            bgMusic.volume = savedState.volume;
        }

        // Important: Restore playback position
        if (savedState.time !== undefined) {
            // Calculate elapsed time if playing
            if (savedState.playing) {
                const elapsed = (Date.now() - savedState.savedTimestamp) / 1000;
                bgMusic.currentTime = savedState.time + elapsed;
            } else {
                bgMusic.currentTime = savedState.time;
            }
        }

        // Restore playback state
        if (savedState.playing && bgMusic.paused) {
            bgMusic.play().then(() => {
                isMusicPlaying = true;
                musicButton.classList.add('playing');
            }).catch(err => {
                console.log('Playback restore failed:', err);
                isMusicPlaying = false;
                musicButton.classList.remove('playing');
            });
        }
    });

    // Save current playback state and time before page switch
    document.addEventListener('turbo:before-visit', () => {
        saveState();
        // Prevent audio from reloading, save actual playback state and time on the window object
        window._bgMusicState = {
            currentTime: bgMusic.currentTime,
            paused: bgMusic.paused,
            volume: bgMusic.volume
        };
    });

    // Improve handling of reload logic during page switch
    document.addEventListener('turbo:before-render', (event) => {
        // Save current playback state
        saveState();

        // Save precise audio element state
        const currentState = {
            currentTime: bgMusic.currentTime,
            paused: bgMusic.paused,
            volume: bgMusic.volume
        };

        // Ensure audio element in new page maintains the same state
        const newBody = event.detail.newBody;
        if (!newBody.querySelector('#bgMusic')) {
            const audioClone = bgMusic.cloneNode(true);
            audioClone.setAttribute('data-turbo-permanent', '');
            newBody.insertBefore(audioClone, newBody.firstChild);
        }

        // Important: Store current state in window object and sessionStorage
        window._bgMusicPreciseState = currentState;
        sessionStorage.setItem('_bgMusicPreciseState', JSON.stringify(currentState));
    });

    // Ensure audio state is correctly restored after new page rendering
    document.addEventListener('turbo:render', () => {
        // Get reference to audio element in new page
        const audio = document.getElementById('bgMusic');

        // First try to restore state from window object
        let preciseState = window._bgMusicPreciseState;

        // If not available, restore from sessionStorage
        if (!preciseState) {
            try {
                const savedPreciseState = sessionStorage.getItem('_bgMusicPreciseState');
                if (savedPreciseState) {
                    preciseState = JSON.parse(savedPreciseState);
                }
            } catch (e) {
                console.error('Failed to restore precise state from sessionStorage', e);
            }
        }

        // Restore from precisely saved state
        if (preciseState) {
            // Restore volume
            audio.volume = preciseState.volume;

            // Restore playback position
            audio.currentTime = preciseState.currentTime;

            // Restore playback state
            if (!preciseState.paused) {
                audio.play().then(() => {
                    musicButton.classList.add('playing');
                }).catch(e => {
                    console.error('Failed to restore playback:', e);
                    musicButton.classList.remove('playing');
                });
            } else {
                audio.pause();
                musicButton.classList.remove('playing');
            }

            // Cleanup
            delete window._bgMusicPreciseState;
            sessionStorage.removeItem('_bgMusicPreciseState');
        }
    });

    // Modify click event handling
    control.addEventListener('click', (e) => {
        if (e.target !== musicButton) return;

        isMusicPlaying = !isMusicPlaying;
        if (isMusicPlaying) {
            bgMusic.play().then(() => {
                musicButton.classList.add('playing');
            }).catch(err => {
                console.error('Playback failed:', err);
                isMusicPlaying = false;
                musicButton.classList.remove('playing');
            });
        } else {
            bgMusic.pause();
            musicButton.classList.remove('playing');
        }
        saveState();
    });

    // Ensure audio element is not reloaded by Turbo Drive
    bgMusic.setAttribute('data-turbo-permanent', '');
    control.setAttribute('data-turbo-permanent', '');
</script>
