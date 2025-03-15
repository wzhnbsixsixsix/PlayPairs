<?php
// 在 leaderboard.php 顶部添加严格的会话检查
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: registration.php");
    exit();
}

// 初始化排行榜数据结构
if (!isset($_SESSION['leaderboard'])) {
    $_SESSION['leaderboard'] = [];
}

// 处理来自 pairs.php 的表单提交
// 处理来自 pairs.php 的表单提交
// 处理来自 pairs.php 的表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 添加调试信息
    error_log("收到的 POST 数据: " . print_r($_POST, true));

    if (!isset($_SESSION['username'])) {
        error_log("用户未登录，无法提交分数");
        echo "error: 未登录";
        exit();
    }

    $username = $_SESSION['username'];
    // $totalScore = isset($_POST['total_score']) ? intval($_POST['total_score']) : 0;
    // $level1Score = isset($_POST['level_1']) ? intval($_POST['level_1']) : 0;
    // $level2Score = isset($_POST['level_2']) ? intval($_POST['level_2']) : 0;
    // $level3Score = isset($_POST['level_3']) ? intval($_POST['level_3']) : 0;

    // 修改后的分数处理逻辑
    $level1Score = isset($_POST['level_1']) ? intval($_POST['level_1']) : 0;
    $level2Score = isset($_POST['level_2']) ? intval($_POST['level_2']) : 0;
    $level3Score = isset($_POST['level_3']) ? intval($_POST['level_3']) : 0;

    // 新增：处理单一模式提交（当只有1个非零关卡分时）
    if (($level1Score > 0) + ($level2Score > 0) + ($level3Score > 0) === 1) {
        $totalScore = max($level1Score, $level2Score, $level3Score);
    } else {
        $totalScore = $level1Score + $level2Score + $level3Score;
    }
    // 检查是否已有该用户的数据
    $userKey = null;
    foreach ($_SESSION['leaderboard'] as $key => $entry) {
        if ($entry['username'] === $username) {
            $userKey = $key;
            break;
        }
    }

    // 更新或添加用户数据
    if ($userKey !== null) {
        // 保留各个关卡的歷史最高分
        $_SESSION['leaderboard'][$userKey]['level1'] = max(
            $_SESSION['leaderboard'][$userKey]['level1'],
            $level1Score
        );
        $_SESSION['leaderboard'][$userKey]['level2'] = max(
            $_SESSION['leaderboard'][$userKey]['level2'],
            $level2Score
        );
        $_SESSION['leaderboard'][$userKey]['level3'] = max(
            $_SESSION['leaderboard'][$userKey]['level3'],
            $level3Score
        );

        // 自动计算总分（三个关卡最高分之和）
        $_SESSION['leaderboard'][$userKey]['score'] =
            $_SESSION['leaderboard'][$userKey]['level1'] +
            $_SESSION['leaderboard'][$userKey]['level2'] +
            $_SESSION['leaderboard'][$userKey]['level3'];
    } else {
        // 新用户直接计算总分
        $_SESSION['leaderboard'][] = [
            'username' => $username,
            'score' => $level1Score + $level2Score + $level3Score,
            'level1' => $level1Score,
            'level2' => $level2Score,
            'level3' => $level3Score
        ];
    }

    // 确保返回 success 响应
    echo "success";
    exit();
}
// 对排行榜按总分降序排序
usort($_SESSION['leaderboard'], function ($a, $b) {
    return $b['score'] - $a['score'];
});
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.3.0/dist/turbo.es2017-umd.js"></script> -->

    <title>Leaderboard - Pairs Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include 'navbar.php'; ?>


    <div id="main" class="container-fluid d-flex align-items-center justify-content-center">
        <div class="content-box p-4 rounded">
            <h2 class="mb-4 text-center">Leaderboard</h2>

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
                        <?php if (empty($_SESSION['leaderboard'])): ?>
                            <tr>
                                <td colspan="6" class="text-center">No scores yet. Be the first to play!</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($_SESSION['leaderboard'] as $index => $entry): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($entry['username']); ?></td>
                                    <td><?php echo $entry['level1']; ?></td>
                                    <td><?php echo $entry['level2']; ?></td>
                                    <td><?php echo $entry['level3']; ?></td>
                                    <td><?php echo $entry['score']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>


            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>