<?php
session_start();

// 验证会话与Cookie一致性
if (isset($_COOKIE['username']) && empty($_SESSION['username'])) {
    $_SESSION['username'] = $_COOKIE['username'];
    $_SESSION['avatar'] = $_COOKIE['avatar'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pairs Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/ClickHere.css">
</head>

<body>

    <?php include 'navbar.php'; ?>

    <div id="main" class="container-fluid d-flex align-items-center justify-content-center text-center">
        <div class="content-box p-4 rounded">
            <?php if (isset($_SESSION['username'])): ?>
                <h1 class="welcome-text">Welcome to Pairs, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                <button class="btn1">
                    <a href="pairs.php" class="text-white text-decoration-none" ">Click here to play</a>
                </button>

            <?php else: ?>
                <p class="lead">You're not using a registered session? <a href="registration.php">Register now</a></p>
            <?php endif; ?>
        </div>
    </div>








    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>