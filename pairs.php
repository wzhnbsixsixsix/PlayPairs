<?php
// Fixed cookie handling logic
if (isset($_COOKIE['leaderboard'])) {
    $leaderboardHighScores = unserialize($_COOKIE['leaderboard']); // Changed to unserialize    
    // Added validation step: ensure the decoded value is a valid array
    if (!is_array($leaderboardHighScores)) {
        $leaderboardHighScores = ['1' => 0, '2' => 0, '3' => 0]; // Default scores if invalid
    }
} else {
    $leaderboardHighScores = ['1' => 0, '2' => 0, '3' => 0]; // Default scores if cookie does not exist
}

// Added key existence check
foreach (['1', '2', '3'] as $level) {
    if (!isset($leaderboardHighScores[$level]) || $leaderboardHighScores[$level] < 0) {
        $leaderboardHighScores[$level] = 0; // Ensure scores are non-negative
    }
}

// Set the cookie with serialized high scores, expires in 1 hour
setcookie('leaderboard', serialize($leaderboardHighScores), time() + 3600, "/"); // Changed to serialize
?>
<script>
    // Pass the leaderboard high scores to JavaScript
    const leaderboardHighScores = <?php echo json_encode($leaderboardHighScores); ?>;
</script>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pairs Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/levelchoice.css">
    <link rel="stylesheet" href="css/submit.css">

    <!-- Add canvas-confetti library for celebration effects -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <style>
        .gold-bg {
            background-color: #FFD700 !important;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?> <!-- Include navigation bar -->
    <div id="main" class="container-fluid d-flex align-items-center justify-content-center">
        <!-- Game panel -->
        <div class="game-container">
            <h2>Memory Pairs Game</h2>

            <!-- Record game state -->
            <div id="game-stats" class="d-flex justify-content-around mb-3 align-items-center" style="display:none;">
                <div>Level: <span id="current-level">1</span></div>
                <div>Attempts: <span id="attempts">0</span></div>
                <div>Time: <span id="timer">0</span> seconds</div>
                <div>Score: <span id="score">0</span></div>
                <div id="lives-container">
                    <!-- Heart icon for lives -->
                    <div class="cssload-main">
                        <div class="cssload-heart">
                            <span class="cssload-heartL"></span>
                            <span class="cssload-heartR"></span>
                            <span class="cssload-square"></span>
                        </div>
                        <div class="cssload-shadow"></div>
                    </div>
                    <div>❤️<span id="lives">0</span></div>
                </div>
            </div>

            <div class="level-container">
                <!-- Level selection radio buttons -->
                <div class="radio-wrapper">
                    <input class="input " name="btn" id="level-1" type="radio" checked="true">
                    <div class="btn1">
                        <span aria-hidden="">_</span>Simple
                        <span class="btn1__glitch" aria-hidden="">_Simple</span>
                        <label class="number">r1</label>
                    </div>
                </div>

                <div class="radio-wrapper">
                    <input class="input" name="btn" id="level-2" type="radio">
                    <div class="btn1">
                        Medium<span aria-hidden=""></span>
                        <span class="btn1__glitch" aria-hidden="">Medium</span>
                        <label class="number">r3</label>
                    </div>
                </div>

                <div class="radio-wrapper">
                    <input class="input" name="btn" id="level-3" type="radio">
                    <div class="btn1">
                        Complex<span aria-hidden="">_</span>
                        <span class="btn1__glitch" aria-hidden="">Complex</span>
                        <label class="number">r2</label>
                    </div>
                </div>
            </div>

            <!-- Start game button -->
            <div class="voltage-button d-flex justify-content-center mt-5">
                <button id="start-game" class="btn btn-success ">Start the Game</button>
                <!-- SVG Party -->
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 234.6 61.3" preserveAspectRatio="none" xml:space="preserve">
                    <filter id="glow">
                        <fegaussianblur class="blur" result="coloredBlur" stddeviation="2"></fegaussianblur>
                        <feTurbulence type="fractalNoise" baseFrequency="0.075" numOctaves="0.3" result="turbulence"></feTurbulence>
                        <feDisplacementMap in="SourceGraphic" in2="turbulence" scale="30" xChannelSelector="R" yChannelSelector="G" result="displace"></feDisplacementMap>
                        <femerge>
                            <femergenode in="coloredBlur"></femergenode>
                            <femergenode in="coloredBlur"></femergenode>
                            <femergenode in="coloredBlur"></femergenode>
                            <femergenode in="displace"></femergenode>
                            <femergenode in="SourceGraphic"></femergenode>
                        </femerge>
                    </filter>
                    <path class="voltage line-1" d="m216.3 51.2c-3.7 0-3.7-1.1-7.3-1.1-3.7 0-3.7 6.8-7.3 6.8-3.7 0-3.7-4.6-7.3-4.6-3.7 0-3.7 3.6-7.3 3.6-3.7 0-3.7-0.9-7.3-0.9-3.7 0-3.7-2.7-7.3-2.7-3.7 0-3.7 7.8-7.3 7.8-3.7 0-3.7-4.9-7.3-4.9-3.7 0-3.7-7.8-7.3-7.8-3.7 0-3.7-1.1-7.3-1.1-3.7 0-3.7 3.1-7.3 3.1-3.7 0-3.7 10.9-7.3 10.9-3.7 0-3.7-12.5-7.3-12.5-3.7 0-3.7 4.6-7.3 4.6-3.7 0-3.7 4.5-7.3 4.5-3.7 0-3.7 3.6-7.3 3.6-3.7 0-3.7-10-7.3-10-3.7 0-3.7-0.4-7.3-0.4-3.7 0-3.7 2.3-7.3 2.3-3.7 0-3.7 7.1-7.3 7.1-3.7 0-3.7-11.2-7.3-11.2-3.7 0-3.7 3.5-7.3 3.5-3.7 0-3.7 3.6-7.3 3.6-3.7 0-3.7-2.9-7.3-2.9-3.7 0-3.7 8.4-7.3 8.4-3.7 0-3.7-14.6-7.3-14.6-3.7 0-3.7 5.8-7.3 5.8-2.2 0-3.8-0.4-5.5-1.5-1.8-1.1-1.8-2.9-2.9-4.8-1-1.8 1.9-2.7 1.9-4.8 0-3.4-2.1-3.4-2.1-6.8s-9.9-3.4-9.9-6.8 8-3.4 8-6.8c0-2.2 2.1-2.4 3.1-4.2 1.1-1.8 0.2-3.9 2-5 1.8-1 3.1-7.9 5.3-7.9 3.7 0 3.7 0.9 7.3 0.9 3.7 0 3.7 6.7 7.3 6.7 3.7 0 3.7-1.8 7.3-1.8 3.7 0 3.7-0.6 7.3-0.6 3.7 0 3.7-7.8 7.3-7.8h7.3c3.7 0 3.7 4.7 7.3 4.7 3.7 0 3.7-1.1 7.3-1.1 3.7 0 3.7 11.6 7.3 11.6 3.7 0 3.7-2.6 7.3-2.6 3.7 0 3.7-12.9 7.3-12.9 3.7 0 3.7 10.9 7.3 10.9 3.7 0 3.7 1.3 7.3 1.3 3.7 0 3.7-8.7 7.3-8.7 3.7 0 3.7 11.5 7.3 11.5 3.7 0 3.7-1.4 7.3-1.4 3.7 0 3.7-2.6 7.3-2.6 3.7 0 3.7-5.8 7.3-5.8 3.7 0 3.7-1.3 7.3-1.3 3.7 0 3.7 6.6 7.3 6.6s3.7-9.3 7.3-9.3c3.7 0 3.7 0.2 7.3 0.2 3.7 0 3.7 8.5 7.3 8.5 3.7 0 3.7 0.2 7.3 0.2 3.7 0 3.7-1.5 7.3-1.5 3.7 0 3.7 1.6 7.3 1.6s3.7-5.1 7.3-5.1c2.2 0 0.6 9.6 2.4 10.7s4.1-2 5.1-0.1c1 1.8 10.3 2.2 10.3 4.3 0 3.4-10.7 3.4-10.7 6.8s1.2 3.4 1.2 6.8 1.9 3.4 1.9 6.8c0 2.2 7.2 7.7 6.2 9.5-1.1 1.8-12.3-6.5-14.1-5.5-1.7 0.9-0.1 6.2-2.2 6.2z" fill="transparent" stroke="#fff" />
                    <path class="voltage line-2" d="m216.3 52.1c-3 0-3-0.5-6-0.5s-3 3-6 3-3-2-6-2-3 1.6-6 1.6-3-0.4-6-0.4-3-1.2-6-1.2-3 3.4-6 3.4-3-2.2-6-2.2-3-3.4-6-3.4-3-0.5-6-0.5-3 1.4-6 1.4-3 4.8-6 4.8-3-5.5-6-5.5-3 2-6 2-3 2-6 2-3 1.6-6 1.6-3-4.4-6-4.4-3-0.2-6-0.2-3 1-6 1-3 3.1-6 3.1-3-4.9-6-4.9-3 1.5-6 1.5-3 1.6-6 1.6-3-1.3-6-1.3-3 3.7-6 3.7-3-6.4-6-6.4-3 2.5-6 2.5h-6c-3 0-3-0.6-6-0.6s-3-1.4-6-1.4-3 0.9-6 0.9-3 4.3-6 4.3-3-3.5-6-3.5c-2.2 0-3.4-1.3-5.2-2.3-1.8-1.1-3.6-1.5-4.6-3.3s-4.4-3.5-4.4-5.7c0-3.4 0.4-3.4 0.4-6.8s2.9-3.4 2.9-6.8-0.8-3.4-0.8-6.8c0-2.2 0.3-4.2 1.3-5.9 1.1-1.8 0.8-6.2 2.6-7.3 1.8-1 5.5-2 7.7-2 3 0 3 2 6 2s3-0.5 6-0.5 3 5.1 6 5.1 3-1.1 6-1.1 3-5.6 6-5.6 3 4.8 6 4.8 3 0.6 6 0.6 3-3.8 6-3.8 3 5.1 6 5.1 3-0.6 6-0.6 3-1.2 6-1.2 3-2.6 6-2.6 3-0.6 6-0.6 3 2.9 6 2.9 3-4.1 6-4.1 3 0.1 6 0.1 3 3.7 6 3.7 3 0.1 6 0.1 3-0.6 6-0.6 3 0.7 6 0.7 3-2.2 6-2.2 3 4.4 6 4.4 3-1.7 6-1.7 3-4 6-4 3 4.7 6 4.7 3-0.5 6-0.5 3-0.8 6-0.8 3-3.8 6-3.8 3 6.3 6 6.3 3-4.8 6-4.8 3 1.9 6 1.9 3-1.9 6-1.9 3 1.3 6 1.3c2.2 0 5-0.5 6.7 0.5 1.8 1.1 2.4 4 3.5 5.8 1 1.8 0.3 3.7 0.3 5.9 0 3.4 3.4 3.4 3.4 6.8s-3.3 3.4-3.3 6.8 4 3.4 4 6.8c0 2.2-6 2.7-7 4.4-1.1 1.8 1.1 6.7-0.7 7.7-1.6 0.8-4.7-1.1-6.8-1.1z" fill="transparent" stroke="#fff" />
                </svg>
                <div class="dots">
                    <div class="dot dot-1"></div>
                    <div class="dot dot-2"></div>
                    <div class="dot dot-3"></div>
                    <div class="dot dot-4"></div>
                    <div class="dot dot-5"></div>
                </div>
            </div>

            <div id="card-container" class="card-container"></div>

            <!-- Game end screen -->
            <div id="game-end" style="display: none;" class="containerS">
                <h3>Game Completed!</h3>
                <p>Your final score: <span id="final-score">0</span></p>
                <p>Time taken: <span id="final-time">0</span> seconds</p>

                <?php if (isset($_COOKIE['username'])): ?>
                    <div class="mt-3">
                        <!-- Form wrapping neon button for score submission -->
                        <form action="submit_score.php" method="post">
                            <input class="input-btn" type="radio" id="submit-score" name="action" value="submit-score" checked>
                            <label class="neon-btn" for="submit-score">
                                <span class="span"></span>
                                <span class="txt">Submit Score</span>
                            </label>
                        </form>

                        <form action="play_again.php" method="post">
                            <input class="input-btn" type="radio" id="play-again" name="action" value="play-again" checked>
                            <label class="neon-btn" for="play-again">
                                <span class="span"></span>
                                <span class="txt">Play Again</span>
                            </label>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="mt-3">
                        <p>Register to save your score!</p>
                        <a href="registration.php" class="btn btn-primary me-2">Register</a>
                        <form action="play_again.php" method="post">
                            <input class="input-btn" type="radio" id="play-again-no-reg" name="action" value="play-again-no-reg" checked>
                            <label class="neon-btn" for="play-again-no-reg">
                                <span class="span"></span>
                                <span class="txt">Play Again</span>
                            </label>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Audio elements -->
    <audio id="flip-sound" src="https://cdn.jsdelivr.net/gh/amdcaruso/memory-game/audios/flip.wav"></audio>
    <audio id="match-sound" src="https://cdn.jsdelivr.net/gh/amdcaruso/memory-game/audios/match.wav"></audio>
    <audio id="success-sound" src="https://cdn.jsdelivr.net/gh/amdcaruso/memory-game/audios/success.wav"></audio>

    <script>
        // Game state variables
        const gameState = {
            level: 1, // 1: Simple, 2: Medium, 3: Complex
            gameMode: 1, // Current game mode
            currentLevel: 1, // Current level within complex mode
            cards: [],
            flippedCards: [],
            bgm: document.getElementById('bgm'),
            matchedCards: [],
            attempts: 0,
            startTime: 0,
            elapsedTime: 0,
            timerInterval: null,
            score: 0,
            levelScores: {
                1: 0,
                2: 0,
                3: 0
            },
            highScores: {
                1: 0,
                2: 0,
                3: 0
            },
            cardsToMatch: 2, // Default is matching pairs
            isGameRunning: false // Flag to check if the game is running
        };

        // DOM Elements
        const cardContainer = document.getElementById('card-container');
        const startButton = document.getElementById('start-game');
        const gameStats = document.getElementById('game-stats');
        const attemptsElement = document.getElementById('attempts');
        const timerElement = document.getElementById('timer');
        const scoreElement = document.getElementById('score');
        const currentLevelElement = document.getElementById('current-level');
        const gameEndElement = document.getElementById('game-end');
        const finalScoreElement = document.getElementById('final-score');
        const finalTimeElement = document.getElementById('final-time');
        const submitScoreButton = document.getElementById('submit-score');
        const playAgainButton = document.getElementById('play-again');
        const playAgainNoRegButton = document.getElementById('play-again-no-reg');
        const levelButtons = {
            1: document.getElementById('level-1'),
            2: document.getElementById('level-2'),
            3: document.getElementById('level-3')
        };

        // Audio elements
        const flipSound = document.getElementById('flip-sound');
        const matchSound = document.getElementById('match-sound');
        const successSound = document.getElementById('success-sound');

        // Initialize the game
        function init() {
            const checkedLevel = document.querySelector('input[name="btn"]:checked').id;
            gameState.level = parseInt(checkedLevel.split('-')[1]); // Get selected level
            // Set up event listeners
            startButton.addEventListener('click', startGame);

            // Level selection
            document.querySelectorAll('input[name="btn"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    if (e.target.id === 'level-1') selectLevel(1);
                    if (e.target.id === 'level-2') selectLevel(2);
                    if (e.target.id === 'level-3') selectLevel(3);
                });
            });

            // Game end buttons
            if (submitScoreButton) {
                submitScoreButton.addEventListener('click', submitScore);
            }

            if (playAgainButton) {
                playAgainButton.addEventListener('click', resetGame);
            }

            if (playAgainNoRegButton) {
                playAgainNoRegButton.addEventListener('click', resetGame);
            }
        }

        function updateLives() {
            const livesElement = document.getElementById('lives');
            if (livesElement) {
                livesElement.textContent = getFailureThreshold() - gameState.failures; // Update lives display
            }
        }

        // Select game level
        function selectLevel(level) {
            gameState.level = level;

            // Update UI
            Object.values(levelButtons).forEach((button, index) => {
                if (index + 1 === level) {
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-primary');
                } else {
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-secondary');
                }
            });
        }

        // Start the game
        function startGame() {
            const musicBtn = document.querySelector('#button-container-1 .music-btn');
            document.querySelector('#button-container-1 .music-btn').classList.add('playing');
            // New music control logic
            const musicControl = document.getElementById('bgMusic');

            if (window.bgMusic) {
                if (window.bgMusic.paused) {
                    window.bgMusic.play().then(() => {
                        musicControl.textContent = '♫';
                        localStorage.setItem(MUSIC_STATE_KEY, JSON.stringify({
                            playing: true,
                            time: window.bgMusic.currentTime,
                            savedTimestamp: Date.now()
                        }));
                    }).catch(err => {
                        console.log('Failed to resume playback:', err);
                    });
                }
            }

            gameState.isGameRunning = true; // Set game running flag
            gameState.failures = 0; // Initialize failure count
            updateLives(); // Reset lives at the start of the game

            gameState.gameMode = gameState.level; // Set game mode
            gameState.currentLevel = 1; // Reset current level
            gameState.failures = 0; // Reset failures
            updateLives(); // Update lives display

            gameState.attempts = 0; // Reset attempts
            gameState.score = 0; // Reset score

            gameState.matchedCards = []; // Clear matched cards
            gameState.flippedCards = []; // Clear flipped cards

            // Reset level scores
            gameState.levelScores = {
                1: 0,
                2: 0,
                3: 0
            };

            // Hide start button and show game stats
            startButton.style.display = 'none';
            document.querySelector('.level-container').style.display = 'none';
            gameStats.style.display = 'flex';
            gameEndElement.style.display = 'none';

            // Update UI
            attemptsElement.textContent = gameState.attempts;
            scoreElement.textContent = gameState.score;

            // Update UI: Initialize lives
            updateLives();

            // Start timer
            startTimer();

            // Set up cards based on game mode
            setupCards();
        }

        // Get the maximum number of allowed failures based on current mode
        function getFailureThreshold() {
            if (gameState.gameMode === 1) {
                return 4; // Simple mode
            } else if (gameState.gameMode === 2) {
                return 6; // Medium mode
            } else if (gameState.gameMode === 3) {
                if (gameState.currentLevel === 1) return 4; // Level 1
                else if (gameState.currentLevel === 2) return 6; // Level 2
                else if (gameState.currentLevel === 3) return 9; // Level 3
            }
            return Infinity; // Default case
        }

        // Set up cards based on game mode
        function setupCards() {

            updateLives();
            // Clear card container
            cardContainer.innerHTML = '';

            let cardCount = 0;
            let matchGroups = [];

            // Determine card count and match groups based on game mode and level
            if (gameState.gameMode === 1) {
                // Simple mode: 6 cards (3 pairs)
                cardCount = 6;
                gameState.cardsToMatch = 2;

                // Create 3 pairs
                for (let i = 0; i < cardCount / 2; i++) {
                    matchGroups.push([i, i]);
                }
            } else if (gameState.gameMode === 2) {
                // Medium mode: 10 cards (5 pairs)
                cardCount = 10;
                gameState.cardsToMatch = 2;

                // Create 5 pairs
                for (let i = 0; i < cardCount / 2; i++) {
                    matchGroups.push([i, i]);
                }
            } else if (gameState.gameMode === 3) {
                // Complex mode: Variable cards based on level
                currentLevelElement.textContent = gameState.currentLevel;

                if (gameState.currentLevel === 1) {
                    // Level 1: 6 cards (3 pairs)
                    cardCount = 6;
                    gameState.cardsToMatch = 2;

                    // Create 3 pairs
                    for (let i = 0; i < cardCount / 2; i++) {
                        matchGroups.push([i, i]);
                    }
                } else if (gameState.currentLevel === 2) {
                    // Level 2: 9 cards (3 triplets)
                    cardCount = 9;
                    gameState.cardsToMatch = 3;

                    // Create 3 triplets
                    for (let i = 0; i < cardCount / 3; i++) {
                        matchGroups.push([i, i, i]);
                    }
                } else if (gameState.currentLevel === 3) {
                    // Level 3: 16 cards (3 quadruplets)
                    cardCount = 16;
                    gameState.cardsToMatch = 4;

                    // Create 3 quadruplets
                    for (let i = 0; i < cardCount / 4; i++) {
                        matchGroups.push([i, i, i, i]);
                    }
                }
            }

            // Create cards
            gameState.cards = [];


            // 生成随机种子数组（每组一个唯一种子）
            const groupSeeds = matchGroups.map(() => Math.floor(Math.random() * 1000));

            // 创建卡片值数组（基于组索引）
            let cardValues = [];
            matchGroups.forEach((group, groupIndex) => {
                group.forEach(() => {
                    cardValues.push(groupSeeds[groupIndex]); // 使用组级种子
                });
            });



            // Shuffle card values
            // cardValues = shuffleArray(cardValues);


            // Create card elements
            cardValues.forEach((seed, index) => {
                const card = document.createElement('div');
                card.className = 'memory-card';
                card.dataset.index = index;
                card.dataset.seed = seed; // 使用种子作为匹配依据

                const cardFront = document.createElement('div');
                cardFront.className = 'card-front';

                const cardBack = document.createElement('div');
                cardBack.className = 'card-back';

                const emojiData = generateEmojiCombination(seed);
                cardFront.innerHTML = createEmojiElement(emojiData);

                card.appendChild(cardFront);
                card.appendChild(cardBack);
                card.addEventListener('click', () => flipCard(card));

                cardContainer.appendChild(card);
                gameState.cards.push(card);
            });
        }

            // Generate emoji combination
            function generateEmojiCombination(seed) {
                //Using a hash algorithm to increase randomness
                const hash = (s) => {
                    let h = 0xdeadbeef;
                    for (let i = 0; i < s.length; i++) {
                        h = Math.imul(h ^ s.charCodeAt(i), 2654435761);
                    }
                    return (h ^ h >>> 16) >>> 0;
                };

                const hashedSeed = hash(seed.toString());

                // Obtain indices of different parts through bitwise operations.
                const skinIndex = (hashedSeed & 0b11) % 3 + 1; // 3种皮肤
                const eyesIndex = ((hashedSeed >> 2) & 0b111) % 6 + 1; // 6种眼睛
                const mouthIndex = ((hashedSeed >> 5) & 0b111) % 6 + 1; // 6种嘴巴

                return {
                    skin: `skin${skinIndex}`,
                    eyes: `eyes${eyesIndex}`,
                    mouth: `mouth${mouthIndex}`
                };
            }

            // Create emoji element from components
            function createEmojiElement(emojiData) {
                return `
                <div style="position: relative; width: 60px; height: 60px;">
                    <img src="emoji_assets/skin/${emojiData.skin}.png" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                    <img src="emoji_assets/eyes/${emojiData.eyes}.png" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                    <img src="emoji_assets/mouth/${emojiData.mouth}.png" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                </div>
            `;
            }

            // Flip card
            function flipCard(card) {
                // Ignore if game not running or card already flipped/matched
                if (!gameState.isGameRunning ||
                    gameState.flippedCards.includes(card) ||
                    gameState.matchedCards.includes(card)) {
                    return;
                }

                // Play flip sound
                flipSound.currentTime = 0;
                flipSound.play();

                // Flip the card
                card.classList.add('flipped');
                gameState.flippedCards.push(card);

                // Check for match if enough cards are flipped
                if (gameState.flippedCards.length === gameState.cardsToMatch) {
                    gameState.attempts++;
                    attemptsElement.textContent = gameState.attempts; // Update attempts

                    // Check if all flipped cards match
                    const allMatch = gameState.flippedCards.every(c =>
                        c.dataset.seed === gameState.flippedCards[0].dataset.seed);


                    if (allMatch) {
                        // Match found
                        matchSound.currentTime = 0;
                        matchSound.play();

                        // Mark cards as matched
                        gameState.flippedCards.forEach(c => {
                            c.classList.add('matched');
                            gameState.matchedCards.push(c);
                        });

                        // Clear flipped cards
                        gameState.flippedCards = [];

                        // Check if all cards are matched
                        if (gameState.matchedCards.length === gameState.cards.length) {
                            // Level completed
                            handleLevelComplete();
                        }
                    } else {
                        // No match, flip cards back after delay
                        setTimeout(() => {
                            gameState.flippedCards.forEach(c => {
                                c.classList.remove('flipped');
                            });
                            gameState.flippedCards = [];
                            gameState.failures++; // Increment failure count
                            updateLives(); // Update lives display

                            // Check if exceeded allowed failure count
                            let allowedFailures = getFailureThreshold();
                            if (gameState.failures >= allowedFailures) {
                                alert("Game Over! Too many wrong guesses.");
                                endGame();
                                return;
                            }
                        }, 1000);
                    }
                }
            }

            // Handle level completion
            function handleLevelComplete() {
                gameState.failures = 0; // Reset failures
                updateLives(); // Update lives display
                // Calculate score for the level
                const levelScore = calculateScore();


                // Store level score
                gameState.levelScores[gameState.currentLevel] = levelScore;

                // Trigger confetti celebration
                triggerConfetti();

                // Only show gold background if score exceeds leaderboard high score
                if (levelScore > Number(leaderboardHighScores[gameState.currentLevel])) {
                    document.querySelector('.game-container').classList.add('gold-bg');

                    setTimeout(() => {
                        document.querySelector('.game-container').classList.remove('gold-bg');
                    }, 3000);
                }

                // Update total score
                gameState.score += levelScore;
                scoreElement.textContent = gameState.score;

                // Check if complex mode and more levels to go
                if (gameState.gameMode === 3 && gameState.currentLevel < 3) {
                    // Move to next level
                    gameState.currentLevel++;
                    currentLevelElement.textContent = gameState.currentLevel;
                    updateLives();

                    // Reset for next level
                    gameState.matchedCards = [];
                    gameState.flippedCards = [];

                    // Set up cards for next level
                    setTimeout(() => {
                        setupCards();
                    }, 1500);
                } else {
                    // Game completed
                    endGame();
                }
            }

            // Calculate score based on attempts and time
            function calculateScore() {
                // Base score
                let baseScore = 100;

                // Penalty for attempts (more attempts = lower score)
                const attemptsPenalty = gameState.attempts;

                // Penalty for time (more time = lower score)
                const timePenalty = Math.floor(gameState.elapsedTime * 2);

                // Calculate final score
                let finalScore = baseScore - attemptsPenalty - timePenalty;

                // Ensure score is not negative
                return Math.max(finalScore, 0);
            }

            // End the game
            function endGame() {
                // Stop timer
                clearInterval(gameState.timerInterval);

                // Play success sound
                successSound.currentTime = 0;
                successSound.play();

                // Show grand confetti celebration for game completion
                triggerConfetti(true);

                // Update final score and time
                finalScoreElement.textContent = gameState.score;
                finalTimeElement.textContent = gameState.elapsedTime;

                // Show game end screen
                gameEndElement.style.display = 'block';

                // Game is no longer running
                gameState.isGameRunning = false;
            }

            // Submit score to leaderboard
            function submitScore() {
                // Create form data
                const formData = new FormData();
                formData.append(`level_${gameState.level}`, gameState.score); // 提交当前关卡分数



                // Send POST request to leaderboard.php
                fetch('leaderboard.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.includes('success')) { // 修改这里
                            // Redirect to leaderboard
                            window.location.href = 'leaderboard.php';
                        } else {
                            console.error('Error submitting score:', data);
                            alert('Error submitting score. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error submitting score. Please try again.');
                    });
            }


            // 新增：根据游戏模式返回允许的最大时间（秒）
            function getTimeLimit() {
                if (gameState.gameMode === 1) {
                    return 15;
                } else if (gameState.gameMode === 2) {
                    return 45;
                } else if (gameState.gameMode === 3) {
                    if (gameState.currentLevel === 1) return 15;
                    else if (gameState.currentLevel === 2) return 45;
                    else if (gameState.currentLevel === 3) return 60;
                }
                return Infinity;
            }

            // Reset game
            function resetGame() {
                // 停止计时器
                clearInterval(gameState.timerInterval);

                // 重置游戏状态变量
                gameState.isGameRunning = false;
                gameState.failures = 0;
                gameState.attempts = 0;
                gameState.score = 0;
                gameState.elapsedTime = 0;
                gameState.matchedCards = [];
                gameState.flippedCards = [];

                // 更新UI显示的值
                attemptsElement.textContent = "0";
                timerElement.textContent = "0";
                scoreElement.textContent = "0";
                currentLevelElement.textContent = "1";

                // 隐藏结束界面
                gameEndElement.style.display = 'none';

                // 显示关卡选择区域和开始按钮（使用 class 选择器）
                document.querySelector('.level-container').style.display = 'flex';
                startButton.style.display = 'block';

                // 隐藏游戏状态面板
                gameStats.style.display = 'none';

                // 清空卡片容器
                cardContainer.innerHTML = '';
            }

            // Start timer
            function startTimer() {
                gameState.startTime = Date.now();
                gameState.elapsedTime = 0;

                gameState.timerInterval = setInterval(() => {
                    gameState.elapsedTime = Math.floor((Date.now() - gameState.startTime) / 1000);
                    timerElement.textContent = gameState.elapsedTime;

                    if (gameState.elapsedTime >= getTimeLimit()) {
                        alert("Time's up!");
                        endGame();
                        clearInterval(gameState.timerInterval);
                    }
                }, 1000);
            }

            // Utility function to shuffle array
            function shuffleArray(array) {
                const newArray = [...array];
                for (let i = newArray.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
                }
                return newArray;
            }

            // Function to trigger confetti celebration
            function triggerConfetti(isGameEnd = false) {
                // Default confetti for level completion
                const options = {
                    particleCount: 100,
                    spread: 70,
                    origin: {
                        y: 0.6
                    }
                };

                // More elaborate confetti for game end
                if (isGameEnd) {
                    // Create a grand finale with multiple bursts
                    const duration = 3000;
                    const animationEnd = Date.now() + duration;
                    const defaults = {
                        startVelocity: 30,
                        spread: 360,
                        ticks: 60,
                        zIndex: 0
                    };

                    const interval = setInterval(function() {
                        const timeLeft = animationEnd - Date.now();

                        if (timeLeft <= 0) {
                            return clearInterval(interval);
                        }

                        const particleCount = 50 * (timeLeft / duration);

                        // Random colors and positions
                        confetti(Object.assign({}, defaults, {
                            particleCount,
                            origin: {
                                x: randomInRange(0.1, 0.3),
                                y: Math.random() - 0.2
                            }
                        }));
                        confetti(Object.assign({}, defaults, {
                            particleCount,
                            origin: {
                                x: randomInRange(0.7, 0.9),
                                y: Math.random() - 0.2
                            }
                        }));
                    }, 250);
                } else {
                    // Simple confetti burst for level completion
                    confetti(options);
                }
            }

            // Helper function for random range
            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            // Initialize the game when the page loads
            document.addEventListener('DOMContentLoaded', init);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>