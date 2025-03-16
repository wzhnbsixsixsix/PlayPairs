<?php
/* When a user logs out, delete their avatar and cookies */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // If avatar cookie exists and the file exists, delete the avatar file securely
    if (isset($_COOKIE['avatar']) && file_exists($_COOKIE['avatar'])) {
        if (isset($_COOKIE['username']) && strpos($_COOKIE['avatar'], $_COOKIE['username']) !== false) {
            unlink($_COOKIE['avatar']); // Delete the avatar file
        }
    }
    // Clear all related cookies by setting their expiration time to the past
    setcookie("username", "", time() - 3600, "/");
    setcookie("avatar", "", time() - 3600, "/");
    setcookie("avatar_type", "", time() - 3600, "/");
    header("Location: registration.php"); // Redirect to the registration page
    exit();
}

$error = ""; // Initialize error message
$username = ""; // Initialize username

// Handle POST request for form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]); // Trim whitespace from the username input

    // Delete old avatar if it exists in cookies
    if (isset($_COOKIE['username']) && isset($_COOKIE['avatar'])) {
        $oldAvatar = $_COOKIE['avatar'];
        if (file_exists($oldAvatar) && strpos($oldAvatar, $_COOKIE['username']) !== false) {
            unlink($oldAvatar); // Remove the old avatar file
        }
    }

    // Validate username for invalid characters
    $invalidChars = array('”', "“", '!', '@', '#', '%', '&', '^', 'ˆ', '*', '(', ')', '+', '=', '{', '}', '[', ']', '-', ';', ':', '"', "’", "'", '<', '>', '?', '/');
    $hasInvalidChar = false;
    foreach ($invalidChars as $char) {
        if (strpos($username, $char) !== false) {
            $hasInvalidChar = true; // Mark as invalid if any forbidden character is found
            break;
        }
    }

    if ($hasInvalidChar) {
        $error = "Username contains invalid characters. Please avoid using ” ! @ # % &ˆ* ( ) + = { } [ ] — ; : “ ’ < > ? /";
    } else {
        // Handle avatar generation based on user selection
        $avatar_type = "";
        $avatar = "";

        // Default avatar generation logic
        if ($_POST['avatar-type'] == 'default') {
            $skin = 'skin1'; // Fixed skin component
            $eyes = 'eyes1'; // Fixed eyes component
            $mouth = 'mouth1'; // Fixed mouth component

            $user_dir = 'user_avatars'; // Directory to store avatars
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true); // Create directory if it doesn't exist
            }

            $default_avatar_filename = $user_dir . '/default_' . $username . '_' . time() . '.png';
            $success = generateAvatar($skin, $eyes, $mouth, $default_avatar_filename); // Generate avatar image

            if ($success) {
                $avatar_type = 'default'; // Set avatar type
                $avatar = $default_avatar_filename; // Set avatar file path
            }
        } else if ($_POST['avatar-type'] == 'custom') {
            $skin = $_POST['skin']; // Custom skin component
            $eyes = $_POST['eyes']; // Custom eyes component
            $mouth = $_POST['mouth']; // Custom mouth component

            $user_dir = 'user_avatars'; // Directory to store avatars
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true); // Create directory if it doesn't exist
            }

            $avatar_filename = $user_dir . '/' . $username . '_' . time() . '.png';
            $success = generateAvatar($skin, $eyes, $mouth, $avatar_filename); // Generate avatar image

            if ($success) {
                $avatar_type = 'custom'; // Set avatar type
                $avatar = $avatar_filename; // Set avatar file path
            } else {
                $avatar_type = 'default'; // Fallback to default avatar type
                $avatar = 'default_avatar.png'; // Fallback to default avatar file
            }
        } else if ($_POST['avatar-type'] == 'random') {
            $skin = $_POST['random_skin']; // Random skin component
            $eyes = $_POST['random_eyes']; // Random eyes component
            $mouth = $_POST['random_mouth']; // Random mouth component

            $user_dir = 'user_avatars'; // Directory to store avatars
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true); // Create directory if it doesn't exist
            }

            $avatar_filename = $user_dir . '/' . $username . '_' . time() . '.png';
            $success = generateAvatar($skin, $eyes, $mouth, $avatar_filename); // Generate avatar image

            if ($success) {
                $avatar_type = 'random'; // Set avatar type
                $avatar = $avatar_filename; // Set avatar file path
            } else {
                $avatar_type = 'default'; // Fallback to default avatar type
                $avatar = 'default_avatar.png'; // Fallback to default avatar file
            }
        }

        // Set cookies and redirect (valid for 30 days)
        setcookie("username", $username, time() + (86400 * 30), "/", "", false, true);
        setcookie("avatar", $avatar, time() + (86400 * 30), "/", "", false, true);
        setcookie("avatar_type", $avatar_type, time() + (86400 * 30), "/", "", false, true);

        header("Location: index.php"); // Redirect to the main page
        exit();
    }
}

/**
 * Generate a composite avatar function  
 * All components are loaded from the specified directory:  
 * Skin: emoji_assets/skin  
 * Eyes: emoji_assets/eyes  
 * Mouth: emoji_assets/mouth
 */
function generateAvatar($skin, $eyes, $mouth, $output_file)
{
    if (!extension_loaded('gd')) {
        return false; // Return false if GD library is not enabled
    }

    // Load individual components (skin, eyes, mouth) as PNG images
    $skin_img = imagecreatefrompng("emoji_assets/skin/{$skin}.png");
    $eyes_img = imagecreatefrompng("emoji_assets/eyes/{$eyes}.png");
    $mouth_img = imagecreatefrompng("emoji_assets/mouth/{$mouth}.png");

    if (!$skin_img || !$eyes_img || !$mouth_img) {
        return false; // Return false if any component fails to load
    }

    $width = imagesx($skin_img); // Get image width
    $height = imagesy($skin_img); // Get image height

    $avatar = imagecreatetruecolor($width, $height); // Create a new true color image
    imagesavealpha($avatar, true); // Save alpha channel for transparency
    $transparent = imagecolorallocatealpha($avatar, 0, 0, 0, 127); // Allocate transparent color
    imagefill($avatar, 0, 0, $transparent); // Fill the image with transparency

    // Overlay layers in sequence: skin, eyes, mouth
    imagecopy($avatar, $skin_img, 0, 0, 0, 0, $width, $height);
    imagecopy($avatar, $eyes_img, 0, 0, 0, 0, $width, $height);
    imagecopy($avatar, $mouth_img, 0, 0, 0, 0, $width, $height);

    $result = imagepng($avatar, $output_file); // Save the final avatar as a PNG file

    imagedestroy($skin_img); // Free memory for skin image
    imagedestroy($eyes_img); // Free memory for eyes image
    imagedestroy($mouth_img); // Free memory for mouth image
    imagedestroy($avatar); // Free memory for final avatar image

    return $result; // Return success status
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"> <!-- Set character encoding -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Optimize for mobile devices -->
    <title>Register - Pairs Game</title> <!-- Page title -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/styles.css"> <!-- Custom styles -->
</head>

<body>
    <?php include 'navbar.php'; ?> <!-- Include navigation bar -->

    <div id="main" class="container-fluid d-flex align-items-center justify-content-center">
        <div class="content-box p-4 rounded">
            <h2 class="mb-4">Register a Profile</h2> <!-- Registration heading -->

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <!-- Form container -->
                <div class="container py-5">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-10 col-lg-8">
                            <div class="card shadow-lg">
                                <div class="card-body p-4 p-md-5">
                                    <h2 class="card-title text-center mb-4">Register a Profile</h2> <!-- Card title -->

                                    <!-- Username input field -->
                                    <div class="mb-4">
                                        <label class="form-label">Username/Nickname</label>
                                        <input type="text"
                                            class="form-control form-control-lg"
                                            id="username"
                                            name="username"
                                            value="<?php echo htmlspecialchars($username); ?>"
                                            required>
                                        <?php if (!empty($error)): ?>
                                            <div class="alert alert-danger mt-2"><?php echo $error; ?></div> <!-- Error message display -->
                                        <?php endif; ?>
                                    </div>

                                    <!-- Avatar type selection -->
                                    <div class="mb-4">
                                        <label class="form-label">Avatar Selection</label>
                                        <div class="vstack gap-3">
                                            <!-- Default avatar option -->
                                            <div class="form-check card">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <input class="form-check-input"
                                                            type="radio"
                                                            name="avatar-type"
                                                            id="default-avatar"
                                                            value="default"
                                                            checked>
                                                        <label class="form-check-label flex-grow-1" for="default-avatar">
                                                            Default Avatar
                                                        </label>
                                                        <div class="avatar-preview-container">
                                                            <div class="avatar-layer" id="default-skin-layer"></div>
                                                            <div class="avatar-layer" id="default-eyes-layer"></div>
                                                            <div class="avatar-layer" id="default-mouth-layer"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Random avatar option -->
                                            <div class="form-check card">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center gap-3 mb-3">
                                                        <input class="form-check-input"
                                                            type="radio"
                                                            name="avatar-type"
                                                            id="random-avatar"
                                                            value="random">
                                                        <label class="form-check-label flex-grow-1" for="random-avatar">
                                                            Random Avatar
                                                        </label>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary"
                                                            onclick="generateRandomCandidates()">
                                                            Refresh
                                                        </button>
                                                    </div>
                                                    <div class="row row-cols-2 row-cols-md-4 g-2" id="random-candidates">
                                                        <!-- Dynamically generated candidate avatars -->
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Custom avatar option -->
                                            <div class="form-check card">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center gap-3 ">
                                                        <input class="form-check-input"
                                                            type="radio"
                                                            name="avatar-type"
                                                            id="custom-avatar"
                                                            value="custom">
                                                        <label class="form-check-label flex-grow-1" for="custom-avatar">
                                                            Custom Avatar
                                                        </label>
                                                        <div class="avatar-preview-container">
                                                            <div class="avatar-layer" id="custom-skin-layer"></div>
                                                            <div class="avatar-layer" id="custom-eyes-layer"></div>
                                                            <div class="avatar-layer" id="custom-mouth-layer"></div>
                                                        </div>
                                                    </div>

                                                    <!-- Custom component selection -->
                                                    <div id="custom-options-container">
                                                        <div class="row g-3">
                                                            <?php foreach (['skin' => 3, 'eyes' => 6, 'mouth' => 6] as $type => $count): ?>
                                                                <div class="col-12 col-md-4">
                                                                    <div class="card h-100">
                                                                        <div class="card-body">
                                                                            <h6 class="card-title text-capitalize"><?= ucfirst($type) ?></h6>
                                                                            <div class="d-flex flex-wrap gap-2">
                                                                                <?php for ($i = 1; $i <= $count; $i++): ?>
                                                                                    <div class="avatar-option <?= $type ?>-option"
                                                                                        data-value="<?= $type . $i ?>">
                                                                                        <img src="emoji_assets/<?= $type ?>/<?= $type . $i ?>.png"
                                                                                            alt="<?= ucfirst($type) ?> <?= $i ?>"
                                                                                            class="img-fluid rounded"
                                                                                            width="40"
                                                                                            height="40">
                                                                                        <input type="radio"
                                                                                            name="<?= $type ?>"
                                                                                            value="<?= $type . $i ?>"
                                                                                            <?= $i == 1 ? 'checked' : '' ?>
                                                                                            class="d-none">
                                                                                    </div>
                                                                                <?php endfor; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden inputs for random avatar -->
                                    <input type="hidden" name="random_skin" id="random-skin">
                                    <input type="hidden" name="random_eyes" id="random-eyes">
                                    <input type="hidden" name="random_mouth" id="random-mouth">

                                    <!-- Submit button -->
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg py-3">
                                            Create Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize default avatar preview
        function setDefaultPreview() {
            document.getElementById('default-skin-layer').style.backgroundImage = "url('emoji_assets/skin/skin1.png')";
            document.getElementById('default-eyes-layer').style.backgroundImage = "url('emoji_assets/eyes/eyes1.png')";
            document.getElementById('default-mouth-layer').style.backgroundImage = "url('emoji_assets/mouth/mouth1.png')";
        }

        // Update custom avatar preview based on selected components
        function updateCustomPreview() {
            const selectedSkin = document.querySelector('input[name="skin"]:checked')?.value || 'skin1';
            const selectedEyes = document.querySelector('input[name="eyes"]:checked')?.value || 'eyes1';
            const selectedMouth = document.querySelector('input[name="mouth"]:checked')?.value || 'mouth1';
            document.getElementById('custom-skin-layer').style.backgroundImage = `url('emoji_assets/skin/${selectedSkin}.png')`;
            document.getElementById('custom-eyes-layer').style.backgroundImage = `url('emoji_assets/eyes/${selectedEyes}.png')`;
            document.getElementById('custom-mouth-layer').style.backgroundImage = `url('emoji_assets/mouth/${selectedMouth}.png')`;
        }

        // Generate random avatar candidates dynamically
        function generateRandomCandidates() {
            const container = document.getElementById("random-candidates");
            container.innerHTML = "";
            for (let i = 0; i < 4; i++) {
                const skinNum = Math.floor(Math.random() * 3) + 1; // Random skin number (1-3)
                const eyesNum = Math.floor(Math.random() * 6) + 1; // Random eyes number (1-6)
                const mouthNum = Math.floor(Math.random() * 6) + 1; // Random mouth number (1-6)
                const candidate = document.createElement("div");
                candidate.classList.add("avatar-preview-container", "avatar-candidate");
                candidate.setAttribute("data-skin", "skin" + skinNum);
                candidate.setAttribute("data-eyes", "eyes" + eyesNum);
                candidate.setAttribute("data-mouth", "mouth" + mouthNum);
                candidate.innerHTML = `
          <div class="avatar-layer" style="background-image: url('emoji_assets/skin/skin${skinNum}.png');"></div>
          <div class="avatar-layer" style="background-image: url('emoji_assets/eyes/eyes${eyesNum}.png');"></div>
          <div class="avatar-layer" style="background-image: url('emoji_assets/mouth/mouth${mouthNum}.png');"></div>
        `;
                candidate.addEventListener("click", function() {
                    document.querySelectorAll(".avatar-candidate").forEach(c => {
                        c.classList.remove("selected");
                        c.style.transform = "none";
                    });
                    candidate.classList.add("selected");
                    document.getElementById("random-skin").value = candidate.getAttribute("data-skin");
                    document.getElementById("random-eyes").value = candidate.getAttribute("data-eyes");
                    document.getElementById("random-mouth").value = candidate.getAttribute("data-mouth");
                });
                container.appendChild(candidate);
            }
        }

        // Event listeners for avatar type radio buttons
        //The event triggered when the default avatar is selected.
        document.getElementById('default-avatar').addEventListener('change', function() {
            setDefaultPreview(); // Update default preview
            document.getElementById('custom-options-container').style.display = 'none'; // Hide custom options
            document.getElementById('random-candidates').innerHTML = ''; // Clear random candidates
        });

        //The event triggered when the customavatar is selected.
        document.getElementById('custom-avatar').addEventListener('change', function(event) {
            event.stopPropagation();
            updateCustomPreview(); // Update custom preview
            document.getElementById('custom-options-container').style.display = 'block'; // Show custom options
            document.querySelector('#random-candidates').innerHTML = ''; // Clear random candidates
        });

        //The event triggered when the random avatar is selected.
        document.getElementById('random-avatar').addEventListener('change', function(event) {
            event.stopPropagation();
            generateRandomCandidates(); // Generate random candidates
            document.getElementById('custom-options-container').style.display = 'none'; // Hide custom options
        });

        // Setup click handlers for avatar component options
        function setupOptionHandlers(options) {
            options.forEach(option => {
                option.addEventListener('click', function() {
                    options.forEach(opt => opt.classList.remove('selected')); // Deselect other options
                    this.classList.add('selected'); // Select current option
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true; // Mark the corresponding radio button as checked
                    updateCustomPreview(); // Update custom preview
                });
                const radio = option.querySelector('input[type="radio"]');
                if (radio.checked) {
                    option.classList.add('selected'); // Pre-select if radio is already checked
                }
            });
        }
        // Setup click handlers for avatar component options
        const skinOptions = document.querySelectorAll('.skin-option');
        const eyesOptions = document.querySelectorAll('.eyes-option');
        const mouthOptions = document.querySelectorAll('.mouth-option');
        setupOptionHandlers(skinOptions);
        setupOptionHandlers(eyesOptions);
        setupOptionHandlers(mouthOptions);

        // Initialize default preview and custom preview
        window.addEventListener('load', function() {
            setDefaultPreview(); // Initialize default preview
            updateCustomPreview(); // Initialize custom preview
            document.getElementById('custom-options-container').style.display = 'none'; // Hide custom options initially
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap JS -->
</body>

</html>