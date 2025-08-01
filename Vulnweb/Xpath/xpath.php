<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XPath注入漏洞测试平台</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background: #5cb85c;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background: #4cae4c;
        }
        .message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .hint {
            margin-top: 20px;
            padding: 15px;
            background: #fff3e0;
            border-left: 4px solid #ffa000;
        }
        code {
            background: #f5f5f5;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>XPath注入测试平台</h1>
        <form method="POST">
            <div class="form-group">
                <label for="username">用户名：</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">密码：</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="submit" name="submit" value="登录">
        </form>

        <?php
        if(file_exists('xpath_user.xml')){
            $xml=simplexml_load_file('xpath_user.xml');
            if($_POST['submit']){
                $username = $_POST['username'];
                $password = $_POST['password'];
                $sql="//user[@username='{$username}' and @password='{$password}']";
                $resulit = $xml->xpath($sql);
                if(count($resulit)==0){
                    echo '<div class="message error">登录失败，请检查用户名和密码。</div>';
                } else {
                    echo '<div class="message success">登录成功！欢迎您，'.htmlspecialchars($username).'！</div>';
                }
            }
        } else {
            echo '<div class="message error">错误：未找到用户数据库文件(xpath_user.xml)</div>';
        }
        ?>

        <div class="hint">
            <h3>XPath注入测试提示：</h3>
            <p>在用户名字段尝试以下注入语句：</p>
            <ul>
                <li><code>' or '1'='1</code> - 基础绕过语句</li>
                <li><code>' or 1=1 or ''='</code> - 替代绕过语句</li>
                <li><code>admin' or '1'='1</code> - 指定用户绕过</li>
                <li><code>'] | //user | user[foo='</code> - 提取所有用户数据</li>
            </ul>
            <p>测试注入时密码字段可留空或随意填写</p>
            
            <h3>测试账户：</h3>
            <ul>
                <li>用户名：<code>admin</code> 密码：<code>admin123</code></li>
                <li>用户名：<code>user1</code> 密码：<code>123456</code></li>
            </ul>
        </div>
    </div>
</body>
</html>