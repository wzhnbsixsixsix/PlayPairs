<?php
// Check if the user is logged in by verifying the presence of the 'username' cookie
// If not logged in, redirect to the registration page
if (!isset($_COOKIE['username'])) {
    header("Location: registration.php");
    exit();
}

// Initialize leaderboard data from cookie or create empty array
$leaderboard = [];
if (file_exists('leaderboard_data.json')) {
    // Read stored leaderboard data from JSON file
    $leaderboard = json_decode(file_get_contents('leaderboard_data.json'), true);

    // Filter out invalid entries that are not arrays or lack 'username' field
    $leaderboard = array_filter((array)$leaderboard, function ($entry) {
        return is_array($entry) && isset($entry['username']);
    });

    // Ensure $leaderboard remains an array even if corrupted data was stored
    if (!is_array($leaderboard)) {
        $leaderboard = [];
    }
}

// Process POST requests (score submissions or leaderboard clearing)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submittedLevel = null;
    $submittedScore = 0;

    // Handle leaderboard clearing request from admin
    if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        $adminPassword = '123'; // Hardcoded admin password
        // Securely compare input password with stored hash
        if (hash_equals($adminPassword, $_POST['password'] ?? '')) {
            // Invalidate leaderboard cookie
            // Clear leaderboard data
            file_put_contents('leaderboard_data.json', json_encode([]));
            setcookie('leaderboard', '', time() - 3600, '/');
            echo 'success';
            exit();
        } else {
            http_response_code(401); // Unauthorized
            echo 'invalid_password';
            exit();
        }
    }

    // Determine which level score was submitted
    foreach (['1', '2', '3'] as $level) {
        if (isset($_POST["level_$level"])) {
            $submittedLevel = $level;
            $submittedScore = intval($_POST["level_$level"]);
            break;
        }
    }

    // Get username from cookie or use default
    $username = $_COOKIE['username'] ?? 'Anonymous';
    $userExists = false;

    // Update existing user's score if applicable
    foreach ($leaderboard as &$user) {
        if ($user['username'] === $username) {
            $userExists = true;

            // Initialize missing level fields to prevent undefined index errors
            $user += ['level1' => 0, 'level2' => 0, 'level3' => 0];

            // Update score if new score is higher for the submitted level
            $currentLevel = "level$submittedLevel";
            if ($submittedScore > ($user[$currentLevel] ?? 0)) {
                $user[$currentLevel] = $submittedScore;
                // Recalculate total score as sum of all levels
                $user['score'] = ($user['level1'] ?? 0) +
                                ($user['level2'] ?? 0) +
                                ($user['level3'] ?? 0);
            }
            break;
        }
    }

    // Create new entry for first-time users
    if (!$userExists) {
        $leaderboard[] = [
            'username' => $username,
            'score'    => $submittedScore,
            'level1'   => ($submittedLevel == 1) ? $submittedScore : 0,
            'level2'   => ($submittedLevel == 2) ? $submittedScore : 0,
            'level3'   => ($submittedLevel == 3) ? $submittedScore : 0
        ];
    }

    // Persist updated leaderboard to JSON file
    file_put_contents('leaderboard_data.json', json_encode($leaderboard));
    echo "success";
    exit();
}

// Prepare leaderboard for display
$leaderboard = array_filter($leaderboard, function ($entry) {
    return is_array($entry) && isset($entry['score']);
});

// Ensure $leaderboard remains an array even if corrupted data was stored
if (!is_array($leaderboard)) {
    $leaderboard = [];
}

// Sort leaderboard by total score in descending order
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
    <!-- Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div id="main" class="container-fluid d-flex align-items-center justify-content-center">
        <div class="content-box p-4 rounded">
            <h2 class="mb-4 text-center">Leaderboard</h2>

            <!-- Admin clear button with trash can icon -->
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
                                    <!-- Sanitize username output to prevent XSS -->
                                    <td><?php echo htmlspecialchars((string)($entry['username'] ?? 'Anonymous'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <!-- Cast scores to integers for safety -->
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
    <!-- Clear leaderboard functionality -->
    <script>
        document.querySelector('.clear-leaderboard').addEventListener('click', async () => {
            const password = prompt('Please enter the administrator password(123):');
            if (!password) return;

            try {
                // Send POST request to clear leaderboard
                const response = await fetch('leaderboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=clear&password=${encodeURIComponent(password)}`
                });

                // Handle server response
                const result = await response.text();
                alert(result === 'success' ? 'Leaderboard cleared successfully' : 'Incorrect password');
                location.reload(); // Refresh to show updated leaderboard
            } catch (error) {
                alert('Operation failed, please check your network connection');
            }
        });
    </script>
    <!-- Bootstrap JS for interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
