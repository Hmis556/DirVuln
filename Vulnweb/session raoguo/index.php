<?php
// 设置 session 保存路径
$save_session = __DIR__ . '/tmp';

// 确保目录存在且有写入权限
if (!file_exists($save_session)) {
    mkdir($save_session, 0777, true);
}

session_save_path($save_session);
session_start();

// 定义用户凭证
$username = 'admin';
$password = 'admin';

// 设置默认时区
date_default_timezone_set('Asia/Shanghai');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户登录系统</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Microsoft YaHei', sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        h1 {
            color: #4a6baf;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .success {
            background-color: #e6f7ee;
            color: #28a745;
            border: 1px solid #b1dfbb;
        }
        
        .error {
            background-color: #f8e1e1;
            color: #dc3545;
            border: 1px solid #f5c6cb;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        
        .form-group {
            text-align: left;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #4a6baf;
            outline: none;
        }
        
        button {
            background-color: #4a6baf;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 0.5rem;
        }
        
        button:hover {
            background-color: #3a5a9f;
        }
        
        a {
            color: #4a6baf;
            text-decoration: none;
            font-weight: 500;
        }
        
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>用户登录</h1>
        
        <?php
        // 检查是否提交了登录表单
        $action = $_GET['c'] ?? '';
        
        if($action == 'login') {
            // 判断session中是否已有用户名
            if(isset($_SESSION['username']) && $_SESSION['username'] == $username) {
                echo '<div class="message success">欢迎回来! '.htmlspecialchars($_SESSION['username']).'</div>';
            } else {
                // 验证登录凭证
                $input_username = $_POST['username'] ?? '';
                $input_password = $_POST['password'] ?? '';
                
                if($input_username == $username && $input_password == $password) {
                    $_SESSION['username'] = $username;
                    
                    // 设置session ID cookie
                    $session_id = session_id();
                    setcookie('PHPSESSID', $session_id, time()+24 * 3600, '/');
                    
                    echo '<div class="message success">登录成功 '.htmlspecialchars($_SESSION['username']).'</div>';
                } else {
                    echo '<div class="message error">帐号或者密码出错 <a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'">返回</a></div>';
                }    
            }    
        } else {
            // 显示登录表单
            echo '<form method="post" action="?c=login" class="login-form">';
            echo '<div class="form-group">';
            echo '<label for="username">帐号：</label>';
            echo '<input type="text" id="username" name="username" placeholder="请输入用户名" required>';
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label for="password">密码：</label>';
            echo '<input type="password" id="password" name="password" placeholder="请输入密码" required>';
            echo '</div>';
            echo '<button type="submit" name="submit">登录</button>';
            echo '</form>';
        }
        ?>
    </div>
</body>
</html>