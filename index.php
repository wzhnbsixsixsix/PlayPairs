<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set character encoding and viewport for responsive design -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Page title -->
    <title>Pairs Game</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include custom CSS files -->
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/ClickHere.css">
</head>
<body>
    <!-- Include navigation bar -->
    <?php include 'navbar.php'; ?>

    <!-- Main content container -->
    <div id="main" class="container-fluid d-flex align-items-center justify-content-center text-center">
        <div class="content-box p-4 rounded">
            <!-- Check if the user is logged in by checking the 'username' cookie -->
            <?php if (isset($_COOKIE['username'])): ?>
                <!-- Display welcome message with the username -->
                <h1 class="welcome-text">Welcome to Pairs, <?php echo htmlspecialchars($_COOKIE['username']); ?></h1>
                <!-- Button to start the game -->
                <button class="btn1">
                    <a href="pairs.php" class="text-white text-decoration-none">Click here to play</a>
                </button>
            <?php else: ?>
                <!-- Display message to register if the user is not logged in -->
                <p class="lead">You're not using a registered session? <a href="registration.php">Register now</a></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Include Bootstrap JavaScript bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>