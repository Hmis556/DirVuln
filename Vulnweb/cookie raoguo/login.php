<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie认证绕过漏洞测试</title>
    <style>
        :root {
            --primary-color: #4285f4;
            --error-color: #ea4335;
            --success-color: #34a853;
            --warning-color: #fbbc05;
            --text-color: #202124;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
        }
        
        body {
            font-family: 'Segoe UI', 'Microsoft YaHei', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        label {
            font-weight: 500;
            color: var(--text-color);
        }
        
        input[type="text"],
        input[type="password"] {
            padding: 0.8rem;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #3367d6;
        }
        
        .status-message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .success-message {
            background-color: rgba(52, 168, 83, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .hint-panel {
            margin-top: 2rem;
            padding: 1rem;
            background-color: rgba(251, 188, 5, 0.1);
            border: 1px solid var(--warning-color);
            border-radius: 4px;
        }
        
        .hint-panel h3 {
            color: var(--warning-color);
            margin-top: 0;
        }
        
        .hint-panel ol {
            padding-left: 1.2rem;
        }
        
        .hint-panel li {
            margin-bottom: 0.5rem;
        }
        
        code {
            background-color: rgba(0, 0, 0, 0.05);
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Cookie认证测试</h1>
            
            <?php 
            include 'init.php';
            if(isset($_COOKIE['username'])): ?>
                <div class="status-message success-message">
                    登录成功！欢迎您，<?php echo htmlspecialchars($_COOKIE['username']); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="login-form">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" placeholder="输入用户名" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" placeholder="输入密码" required>
                </div>
                
                <button type="submit">登录系统</button>
            </form>
            
            <div class="hint-panel">
                <h3>漏洞测试提示</h3>
                <ol>
                    <li>使用正常账号登录后，查看浏览器Cookie</li>
                    <li>尝试修改或添加<code>username</code> Cookie值</li>
                    <li>直接在控制台执行：<br><code>document.cookie = "username=admin";</code></li>
                    <li>刷新页面观察认证结果</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>