<?php
// 注销处理：通过 GET 参数 action=logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_COOKIE['avatar']) && file_exists($_COOKIE['avatar'])) {
        // 安全验证：确保文件名包含用户名
        if (isset($_COOKIE['username']) && strpos($_COOKIE['avatar'], $_COOKIE['username']) !== false) {
            unlink($_COOKIE['avatar']);
        }
    }
    // 清空所有相关 cookie（设置过期时间为过去）
    setcookie("username", "", time() - 3600, "/");
    setcookie("avatar", "", time() - 3600, "/");
    setcookie("avatar_type", "", time() - 3600, "/");
    header("Location: registration.php");
    exit();
}

$error = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);

    // 新增：删除旧头像（改为从 cookie 中读取）
    if (isset($_COOKIE['username']) && isset($_COOKIE['avatar'])) {
        $oldAvatar = $_COOKIE['avatar'];
        if (file_exists($oldAvatar) && strpos($oldAvatar, $_COOKIE['username']) !== false) {
            unlink($oldAvatar);
        }
    }
    // 检查非法字符
    $invalidChars = array('”', "“", '!', '@', '#', '%', '&', '^', 'ˆ', '*', '(', ')', '+', '=', '{', '}', '[', ']', '-', ';', ':', '"', "’", "'", '<', '>', '?', '/');
    $hasInvalidChar = false;
    foreach ($invalidChars as $char) {
        if (strpos($username, $char) !== false) {
            $hasInvalidChar = true;
            break;
        }
    }

    if ($hasInvalidChar) {
        $error = "Username contains invalid characters. Please avoid using ” ! @ # % &ˆ* ( ) + = { } [ ] — ; : “ ’ < > ? /";
    } else {
        // 处理头像生成，存储结果到局部变量，稍后写入 Cookie
        $avatar_type = "";
        $avatar = "";
        
        // 根据头像类型处理
        if ($_POST['avatar-type'] == 'default') {
            // 默认头像固定使用以下组件
            $skin = 'skin1';
            $eyes = 'eyes1';
            $mouth = 'mouth1';

            $user_dir = 'user_avatars';
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true);
            }

            $default_avatar_filename = $user_dir . '/default_' . $username . '_' . time() . '.png';
            $success = generateAvatar($skin, $eyes, $mouth, $default_avatar_filename);

            if ($success) {
                $avatar_type = 'default';
                $avatar = $default_avatar_filename;
            }
        } else if ($_POST['avatar-type'] == 'custom') {
            // 自定义头像：从表单获取组件（自定义区内的 radio 按钮）
            $skin = $_POST['skin'];
            $eyes = $_POST['eyes'];
            $mouth = $_POST['mouth'];

            $user_dir = 'user_avatars';
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true);
            }

            $avatar_filename = $user_dir . '/' . $username . '_' . time() . '.png';
            $success = generateAvatar($skin, $eyes, $mouth, $avatar_filename);

            if ($success) {
                $avatar_type = 'custom';
                $avatar = $avatar_filename;
            } else {
                $avatar_type = 'default';
                $avatar = 'default_avatar.png';
            }
        } else if ($_POST['avatar-type'] == 'random') {
            // 随机头像：从隐藏输入中获取候选头像的组合
            $skin = $_POST['random_skin'];
            $eyes = $_POST['random_eyes'];
            $mouth = $_POST['random_mouth'];

            $user_dir = 'user_avatars';
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true);
            }

            $avatar_filename = $user_dir . '/' . $username . '_' . time() . '.png';
            $success = generateAvatar($skin, $eyes, $mouth, $avatar_filename);

            if ($success) {
                $avatar_type = 'random';
                $avatar = $avatar_filename;
            } else {
                $avatar_type = 'default';
                $avatar = 'default_avatar.png';
            }
        }

        // 设置 Cookie 并重定向（有效期设置为 30 天）
        setcookie("username", $username, time() + (86400 * 30), "/", "", false, true);
        setcookie("avatar", $avatar, time() + (86400 * 30), "/", "", false, true);
        setcookie("avatar_type", $avatar_type, time() + (86400 * 30), "/", "", false, true);

        header("Location: index.php");
        exit();
    }
}

/**
 * 生成合成头像函数  
 * 所有组件均从指定目录加载：  
 * 皮肤：emoji_assets/skin  
 * 眼睛：emoji_assets/eyes  
 * 嘴巴：emoji_assets/mouth
 */
function generateAvatar($skin, $eyes, $mouth, $output_file)
{
    if (!extension_loaded('gd')) {
        return false;
    }

    $skin_img = imagecreatefrompng("emoji_assets/skin/{$skin}.png");
    $eyes_img = imagecreatefrompng("emoji_assets/eyes/{$eyes}.png");
    $mouth_img = imagecreatefrompng("emoji_assets/mouth/{$mouth}.png");

    if (!$skin_img || !$eyes_img || !$mouth_img) {
        return false;
    }

    $width = imagesx($skin_img);
    $height = imagesy($skin_img);

    $avatar = imagecreatetruecolor($width, $height);
    imagesavealpha($avatar, true);
    $transparent = imagecolorallocatealpha($avatar, 0, 0, 0, 127);
    imagefill($avatar, 0, 0, $transparent);

    // 依次叠加图层
    imagecopy($avatar, $skin_img, 0, 0, 0, 0, $width, $height);
    imagecopy($avatar, $eyes_img, 0, 0, 0, 0, $width, $height);
    imagecopy($avatar, $mouth_img, 0, 0, 0, 0, $width, $height);

    $result = imagepng($avatar, $output_file);

    imagedestroy($skin_img);
    imagedestroy($eyes_img);
    imagedestroy($mouth_img);
    imagedestroy($avatar);

    return $result;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Pairs Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div id="main" class="container-fluid d-flex align-items-center justify-content-center">
        <div class="content-box p-4 rounded">
            <h2 class="mb-4">Register a Profile</h2>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="container py-5">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-10 col-lg-8">
                            <div class="card shadow-lg">
                                <div class="card-body p-4 p-md-5">
                                    <h2 class="card-title text-center mb-4">Register a Profile</h2>

                                    <!-- 用户名输入 -->
                                    <div class="mb-4">
                                        <label class="form-label">Username/Nickname</label>
                                        <input type="text"
                                            class="form-control form-control-lg"
                                            id="username"
                                            name="username"
                                            value="<?php echo htmlspecialchars($username); ?>"
                                            required>
                                        <?php if (!empty($error)): ?>
                                            <div class="alert alert-danger mt-2"><?php echo $error; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- 头像类型选择 -->
                                    <div class="mb-4">
                                        <label class="form-label">Avatar Selection</label>
                                        <div class="vstack gap-3">
                                            <!-- 默认头像选项 -->
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

                                            <!-- 随机头像选项 -->
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
                                                        <!-- 动态生成的候选头像 -->
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- 自定义头像选项 -->
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

                                                    <!-- 自定义组件选择 -->
                                                    <div id="custom-options-container">
                                                        <div class="row g-3">
                                                            <?php foreach (['skin' => 3, 'eyes' => 6, 'mouth' => 6] as $type => $count): ?>
                                                                <div class="col-12 col-md-4">
                                                                    <div class="card h-100">
                                                                        <div class="card-body">
                                                                            <h6 class="card-title text-capitalize"><?= $type ?></h6>
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

                                    <!-- 隐藏输入 -->
                                    <input type="hidden" name="random_skin" id="random-skin">
                                    <input type="hidden" name="random_eyes" id="random-eyes">
                                    <input type="hidden" name="random_mouth" id="random-mouth">

                                    <!-- 提交按钮 -->
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
        // 默认预览初始化
        function setDefaultPreview() {
            document.getElementById('default-skin-layer').style.backgroundImage = "url('emoji_assets/skin/skin1.png')";
            document.getElementById('default-eyes-layer').style.backgroundImage = "url('emoji_assets/eyes/eyes1.png')";
            document.getElementById('default-mouth-layer').style.backgroundImage = "url('emoji_assets/mouth/mouth1.png')";
        }

        // 自定义预览更新函数
        function updateCustomPreview() {
            const selectedSkin = document.querySelector('input[name="skin"]:checked')?.value || 'skin1';
            const selectedEyes = document.querySelector('input[name="eyes"]:checked')?.value || 'eyes1';
            const selectedMouth = document.querySelector('input[name="mouth"]:checked')?.value || 'mouth1';
            document.getElementById('custom-skin-layer').style.backgroundImage = `url('emoji_assets/skin/${selectedSkin}.png')`;
            document.getElementById('custom-eyes-layer').style.backgroundImage = `url('emoji_assets/eyes/${selectedEyes}.png')`;
            document.getElementById('custom-mouth-layer').style.backgroundImage = `url('emoji_assets/mouth/${selectedMouth}.png')`;
        }

        // 生成随机候选头像
        function generateRandomCandidates() {
            const container = document.getElementById("random-candidates");
            container.innerHTML = "";
            for (let i = 0; i < 4; i++) {
                // 随机生成1～3、1～6
                const skinNum = Math.floor(Math.random() * 3) + 1; // 1～3
                const eyesNum = Math.floor(Math.random() * 6) + 1; // 1～6
                const mouthNum = Math.floor(Math.random() * 6) + 1; // 1～6
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

        // 修改单选框切换事件处理
        document.getElementById('default-avatar').addEventListener('change', function() {
            setDefaultPreview();
            document.getElementById('custom-options-container').style.display = 'none';
            document.getElementById('random-candidates').innerHTML = '';
        });

        document.getElementById('custom-avatar').addEventListener('change', function(event) {
            event.stopPropagation();
            updateCustomPreview();
            document.getElementById('custom-options-container').style.display = 'block';
            document.querySelector('#random-candidates').innerHTML = '';
        });

        document.getElementById('random-avatar').addEventListener('change', function(event) {
            event.stopPropagation();
            generateRandomCandidates();
            document.getElementById('custom-options-container').style.display = 'none';
        });

        document.getElementById('random-avatar').addEventListener('change', function() {
            document.querySelector('#random-avatar').closest('.card')
                .querySelector('.avatar-preview-container').style.transform = 'none';
            generateRandomCandidates();
        });

        function setupOptionHandlers(options) {
            options.forEach(option => {
                option.addEventListener('click', function() {
                    options.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    updateCustomPreview();
                });
                const radio = option.querySelector('input[type="radio"]');
                if (radio.checked) {
                    option.classList.add('selected');
                }
            });
        }
        const skinOptions = document.querySelectorAll('.skin-option');
        const eyesOptions = document.querySelectorAll('.eyes-option');
        const mouthOptions = document.querySelectorAll('.mouth-option');
        setupOptionHandlers(skinOptions);
        setupOptionHandlers(eyesOptions);
        setupOptionHandlers(mouthOptions);

        window.addEventListener('load', function() {
            setDefaultPreview();
            updateCustomPreview();
            document.getElementById('custom-options-container').style.display = 'none';
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
