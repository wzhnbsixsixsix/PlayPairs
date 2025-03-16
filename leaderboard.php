<?php
// At the top of leaderboard.php, add user index lookup:
// Check if the user is logged in (via cookie)
if (!isset($_COOKIE['username'])) {
    header("Location: registration.php");
    exit();
}
// Restore leaderboard data from cookie, initialize as an empty array if not exists
// Modified leaderboard initialization logic (around line 11)
$leaderboard = [];
if (isset($_COOKIE['leaderboard'])) {
    $leaderboard = unserialize($_COOKIE['leaderboard']);

    // New: Filter invalid entries (fix non-array element issue)
    $leaderboard = array_filter((array)$leaderboard, function ($entry) {
        return is_array($entry) && isset($entry['username']);
    });

    // Retain original array check
    if (!is_array($leaderboard)) {
        $leaderboard = [];
    }
}
// Handle form submission from pairs.php
// Modified score submission handling logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submittedLevel = null;
    $submittedScore = 0;

    if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        $adminPassword = '123'; // Password set here
        if (hash_equals($adminPassword, $_POST['password'] ?? '')) {
            setcookie('leaderboard', '', time() - 3600, '/');
            echo 'success';
            exit();
        } else {
            http_response_code(401);
            echo 'invalid_password';
            exit();
        }
    }
    // Get the current submitted level and score
    foreach (['1', '2', '3'] as $level) {
        if (isset($_POST["level_$level"])) {
            $submittedLevel = $level;
            $submittedScore = intval($_POST["level_$level"]);
            break;
        }
    }

    $username = $_COOKIE['username'] ?? 'Anonymous';
    $userExists = false;

    // Check if the user exists
    // Modified user existence check logic
    foreach ($leaderboard as &$user) {
        if ($user['username'] === $username) {
            $userExists = true;

            // Ensure all level fields exist (fix undefined key issue)
            $user += ['level1' => 0, 'level2' => 0, 'level3' => 0];

            $currentLevel = "level$submittedLevel";
            if ($submittedScore > ($user[$currentLevel] ?? 0)) {
                $user[$currentLevel] = $submittedScore;
                // Recalculate total score
                $user['score'] = ($user['level1'] ?? 0) +
                    ($user['level2'] ?? 0) +
                    ($user['level3'] ?? 0);
            }
            break;
        }
    }

    // Create a new record for new users
    if (!$userExists) {
        $leaderboard[] = [
            'username' => $username,
            'score'    => $submittedScore,
            'level1'   => ($submittedLevel == 1) ? $submittedScore : 0,
            'level2'   => ($submittedLevel == 2) ? $submittedScore : 0,
            'level3'   => ($submittedLevel == 3) ? $submittedScore : 0
        ];
    }

    // Save the updated leaderboard
    setcookie('leaderboard', serialize($leaderboard), time() + 3600, '/');
    echo "success";
    exit();
}
// Sort the leaderboard by total score in descending order
$leaderboard = array_filter($leaderboard, function ($entry) {
    return is_array($entry) && isset($entry['score']);
});

usort($leaderboard, function ($a, $b) {
    return $b['score'] - $a['score'];
});

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Pairs Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div id="main" class="container-fluid d-flex align-items-center justify-content-center">
        <div class="content-box p-4 rounded">
            <h2 class="mb-4 text-center">Leaderboard</h2>

            <button class="clear-leaderboard">
                <svg viewBox="0 0 448 512" class="svgIcon">
                    <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path>
                </svg>
            </button>

            <div class="game-container">
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Username</th>
                            <th>Simple</th>
                            <th>Medium</th>
                            <th>Complex</th>
                            <th>Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaderboard)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No scores yet. Be the first to play!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leaderboard as $index => $entry): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars((string)($entry['username'] ?? 'Anonymous'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int)($entry['level1'] ?? 0); ?></td>
                                    <td><?php echo (int)($entry['level2'] ?? 0); ?></td>
                                    <td><?php echo (int)($entry['level3'] ?? 0); ?></td>
                                    <td><?php echo (int)($entry['score'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('.clear-leaderboard').addEventListener('click', async () => {
            const password = prompt('Please enter the administrator password(123):');
            if (!password) return;

            try {
                // Modify request address to the current file
                const response = await fetch('leaderboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=clear&password=${encodeURIComponent(password)}`
                });

                // Retain original result handling
                const result = await response.text();
                alert(result === 'success' ? 'Leaderboard cleared successfully' : 'Incorrect password');
                location.reload();
            } catch (error) {
                alert('Operation failed, please check your network connection');
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>