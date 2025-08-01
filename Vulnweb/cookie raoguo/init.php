<?php
// init.php - 模拟初始化文件
session_start();

// 模拟用户数据库
$users = [
    'admin' => [
        'password' => 'admin123', // 实际中应该存储哈希值
        'role' => 'admin'
    ],
    'user1' => [
        'password' => '123456',
        'role' => 'user'
    ]
];

// 处理登录逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 漏洞1：直接比较明文密码
    if (isset($users[$username]) && $users[$username]['password'] === $password) {
        // 漏洞2：直接设置未加密的cookie
        setcookie('username', $username, time() + 3600, '/');
        setcookie('role', $users[$username]['role'], time() + 3600, '/');
        $_SESSION['username'] = $username;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// 漏洞3：完全信任cookie值
if (isset($_COOKIE['username'])) {
    $_SESSION['username'] = $_COOKIE['username'];
    // 漏洞4：没有验证cookie中的角色是否合法
    if (isset($_COOKIE['role'])) {
        $_SESSION['role'] = $_COOKIE['role'];
    }
}
?>