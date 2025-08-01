<?php

$valid_users = [
    'username' => 'password',
];

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    !isset($valid_users[$_SERVER['PHP_AUTH_USER']]) || 
    $valid_users[$_SERVER['PHP_AUTH_USER']] != $_SERVER['PHP_AUTH_PW']) {
    
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo '需要认证才能访问此页面';
    exit;
}





// 启用错误报告
/*ini_set('display_errors', 1);
error_reporting(E_ALL);*/

// 设置当前目录
$baseDir = __DIR__ . DIRECTORY_SEPARATOR;
$currentPath = isset($_GET['path']) ? urldecode($_GET['path']) : '';

// 安全获取真实路径
function getRealPath($base, $path) {
    $realBase = realpath($base);
    $userPath = $base . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    $realUserPath = realpath($userPath);
    
    if ($realUserPath === false || strpos($realUserPath, $realBase) !== 0) {
        return false;
    }
    
    return $realUserPath;
}

// 获取网络接口统计信息
function getNetworkStats() {
    static $prev = [];
    $current = [];
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

    if ($isWindows) {
        // Windows网速监控
        $output = @shell_exec('netstat -e 2>&1');
        if (preg_match('/Bytes\s+(\d+)\s+(\d+)/', $output, $matches)) {
            $current['all'] = [
                'rx_bytes' => $matches[1],
                'tx_bytes' => $matches[2]
            ];
        }
    } else {
        // Linux网速监控
        $network = @file_get_contents('/proc/net/dev');
        if ($network) {
            foreach (explode("\n", $network) as $line) {
                if (preg_match('/^\s*(eth\d|wlan\d|enp\d+s\d+):(.*)$/', $line, $matches)) {
                    $interface = $matches[1];
                    $stats = preg_split('/\s+/', trim($matches[2]));
                    $current[$interface] = [
                        'rx_bytes' => $stats[0],
                        'tx_bytes' => $stats[8]
                    ];
                }
            }
        }
    }
    
    // 计算网速
    $result = [];
    foreach ($current as $interface => $stats) {
        $rx_speed = 0;
        $tx_speed = 0;
        
        if (isset($prev[$interface])) {
            $time_elapsed = 1; // 假设每次调用间隔1秒
            $rx_speed = ($stats['rx_bytes'] - $prev[$interface]['rx_bytes']) / $time_elapsed;
            $tx_speed = ($stats['tx_bytes'] - $prev[$interface]['tx_bytes']) / $time_elapsed;
        }
        
        $result[$interface] = [
            'rx' => formatSpeed($rx_speed),
            'tx' => formatSpeed($tx_speed)
        ];
    }
    
    $prev = $current;
    return $result;
}

function formatSpeed($bytes) {
    if ($bytes >= 1000000) {
        return round($bytes / 1000000, 2) . ' MB/s';
    } elseif ($bytes >= 1000) {
        return round($bytes / 1000, 2) . ' KB/s';
    }
    return round($bytes, 2) . ' B/s';
}

// 获取正确的服务器IP
function getServerIP() {
    // 优先尝试通过外部服务获取（需要allow_url_fopen）
    if ('1'=='1') {
        $externalIP = @file_get_contents('https://ip.3322.net');
        if ($externalIP !== false && filter_var($externalIP, FILTER_VALIDATE_IP)) {
            return $externalIP;
        }
    }
    // 优先尝试获取本地IP   仅外部获取
    if (ini_get('allow_url_fopen')) {
        $_SERVER = @file_get_contents('https://ip.3322.net');
        if ($_SERVER !== false && filter_var($_SERVER, FILTER_VALIDATE_IP)) {
            return $_SERVER;
        }
    }
    /*if (!empty($_SERVER['SERVER_ADDR'])) {
        return $_SERVER['SERVER_ADDR'];
    }*/
    
    // 尝试获取主机名对应的IP
    /*$hostname = gethostname();
    if ($hostname) {
        $ips = gethostbynamel($hostname);
        if ($ips && !empty($ips)) {
            // 排除127.0.0.1
            foreach ($ips as $ip) {
                if ($ip !== '127.0.0.1' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                    return $ip;
                }
            }
            return $ips[0];
        }
    }*/
    
    // 最后尝试通过外部服务获取（需要allow_url_fopen）
    if (ini_get('allow_url_fopen')) {
        $externalIP = @file_get_contents('https://ip.3322.net');
        if ($externalIP !== false) {
            return $externalIP;
        }
    }
    
    return 'N/A';
}

// Windows专用CPU检测
function get_cpu_status($isWindows) {
    $cpu = [
        'load_1min' => 'N/A',
        'load_5min' => 'N/A',
        'load_15min' => 'N/A',
        'usage' => 'N/A',
        'cores' => 'N/A'
    ];

    if ($isWindows) {
        // Windows CPU使用率
        $wmic = @shell_exec('wmic cpu get loadpercentage,numberofcores 2>&1');
        if (preg_match_all('/\d+/', $wmic, $matches)) {
            if (count($matches[0]) > 1) {
                $cpu['usage'] = (int)$matches[0][0];
                $cpu['cores'] = (int)$matches[0][1];
                $cpu['load_1min'] = round($cpu['usage'] / 100, 2); // 模拟负载值
            } elseif (count($matches[0]) > 0) {
                $cpu['usage'] = (int)$matches[0][0];
                $cpu['load_1min'] = round($cpu['usage'] / 100, 2);
            }
        }
    } 
    // Linux CPU检测
    elseif (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $cpu = [
            'load_1min' => round($load[0], 2),
            'load_5min' => round($load[1], 2),
            'load_15min' => round($load[2], 2),
            'usage' => 'N/A',
            'cores' => (int)@shell_exec('nproc') ?: 'N/A'
        ];
        
        if (is_numeric($cpu['cores'])) {
            $cpu['usage'] = round(min($load[0] * 100 / $cpu['cores'], 100), 2);
        }
    }

    return $cpu;
}

// Windows专用内存检测
function get_memory_status($isWindows) {
    $memory = [
        'total' => 'N/A',
        'used' => 'N/A', 
        'free' => 'N/A',
        'usage' => 'N/A'
    ];

    if ($isWindows) {
        $wmic = @shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /Value 2>&1');
        if (preg_match('/TotalVisibleMemorySize=(\d+)/', $wmic, $total) && 
            preg_match('/FreePhysicalMemory=(\d+)/', $wmic, $free)) {
            
            $totalMB = round($total[1] / 1024, 2);
            $freeMB = round($free[1] / 1024, 2);
            $usedMB = $totalMB - $freeMB;
            
            $memory = [
                'total' => $totalMB . ' MB',
                'used' => $usedMB . ' MB',
                'free' => $freeMB . ' MB',
                'usage' => round($usedMB / $totalMB * 100, 2)
            ];
        }
    } 
    // Linux内存检测
    elseif (is_readable('/proc/meminfo')) {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match_all('/^(MemTotal|MemFree|Buffers|Cached):\s+(\d+)\s+kB$/m', $meminfo, $matches);
        
        if (count($matches[1]) > 0) {
            $mem = array_combine($matches[1], $matches[2]);
            $used = $mem['MemTotal'] - $mem['MemFree'] - $mem['Buffers'] - $mem['Cached'];
            $memory = [
                'total' => round($mem['MemTotal'] / 1024, 2) . ' MB',
                'used' => round($used / 1024, 2) . ' MB',
                'free' => round($mem['MemFree'] / 1024, 2) . ' MB',
                'usage' => round($used / $mem['MemTotal'] * 100, 2)
            ];
        }
    }

    return $memory;
}

// Windows运行时间
function get_windows_uptime() {
    $boottime = @shell_exec('wmic os get lastbootuptime 2>&1');
    if (preg_match('/\d{14}/', $boottime, $matches)) {
        $boot = DateTime::createFromFormat('YmdHis', $matches[0]);
        $diff = $boot->diff(new DateTime());
        return format_time_diff($diff);
    }
    return 'N/A';
}

// Linux运行时间
function get_linux_uptime() {
    if (is_readable('/proc/uptime')) {
        $uptime = (float)file_get_contents('/proc/uptime');
        return format_seconds($uptime);
    }
    $uptime = @shell_exec('uptime -p');
    return $uptime ? trim($uptime) : 'N/A';
}

function format_seconds($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $result = [];
    if ($days > 0) $result[] = $days . '天';
    if ($hours > 0) $result[] = $hours . '小时';
    $result[] = $minutes . '分钟';
    
    return implode(' ', $result);
}

function format_time_diff($diff) {
    $result = [];
    if ($diff->d > 0) $result[] = $diff->d . '天';
    if ($diff->h > 0) $result[] = $diff->h . '小时';
    if ($diff->i > 0) $result[] = $diff->i . '分钟';
    
    return $result ? implode(' ', $result) : '不到1分钟';
}

function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $iconMap = [
        'pdf' => 'file-pdf',
        'doc' => 'file-word', 'docx' => 'file-word',
        'xls' => 'file-excel', 'xlsx' => 'file-excel',
        'ppt' => 'file-powerpoint', 'pptx' => 'file-powerpoint',
        'jpg' => 'file-image', 'jpeg' => 'file-image', 'png' => 'file-image', 
        'gif' => 'file-image', 'svg' => 'file-image',
        'mp3' => 'file-audio', 'wav' => 'file-audio',
        'mp4' => 'file-video', 'avi' => 'file-video', 'mov' => 'file-video',
        'zip' => 'file-archive', 'rar' => 'file-archive', '7z' => 'file-archive',
        'php' => 'file-code', 'html' => 'file-code', 'css' => 'file-code', 
        'js' => 'file-code', 'json' => 'file-code',
        'txt' => 'file-alt', 'md' => 'file-alt',
    ];
    
    return isset($iconMap[$extension]) ? '<i class="far fa-'.$iconMap[$extension].'"></i>' : '<i class="far fa-file"></i>';
}

function getProgressClass($percentage) {
    if ($percentage === 'N/A') return '';
    $percentage = (float)$percentage;
    if ($percentage < 60) return 'progress-safe';
    if ($percentage < 85) return 'progress-warning';
    return 'progress-danger';
}

// 增强型服务器状态检测（完全兼容Windows和Linux）
function getServerStatus() {
    $status = [];
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

    // CPU状态检测
    $status['cpu'] = get_cpu_status($isWindows);
    
    // 内存状态检测
    $status['memory'] = get_memory_status($isWindows);
    
    // 磁盘空间
    $diskTotal = @disk_total_space(__DIR__);
    $diskFree = @disk_free_space(__DIR__);
    $status['disk'] = [
        'total' => $diskTotal ? round($diskTotal / (1024 * 1024 * 1024), 2) . ' GB' : 'N/A',
        'free' => $diskFree ? round($diskFree / (1024 * 1024 * 1024), 2) . ' GB' : 'N/A',
        'usage' => ($diskTotal && $diskFree) ? round(($diskTotal - $diskFree) / $diskTotal * 100, 2) : 'N/A'
    ];

    // 网络信息
    $status['network'] = getNetworkStats();
    $status['network']['ip'] = getServerIP();

    // 系统信息
    $status['system'] = [
        'os' => php_uname('s') . ' ' . php_uname('r'),
        'hostname' => @gethostname() ?: 'N/A',
        'php' => phpversion(),
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'uptime' => $isWindows ? get_windows_uptime() : get_linux_uptime()
    ];

    return $status;
}



// 获取当前日期和时间的格式化字符串
$current_date = date("Y-m-d H:i:s");
//测试用 echo $current_date;
$currentDir = getRealPath($baseDir, $currentPath) ?: $baseDir;
$items = is_dir($currentDir) ? array_diff(scandir($currentDir), ['.', '..']) : [];
$serverStatus = getServerStatus();
?>
<!DOCTYPE html>
<html lang="zh-CN" class="dark-theme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服务器监控与目录索引</title>
 <style>
        :root {
            --primary-color: #6b8cff;
            --secondary-color: #8da6ff;
            --accent-color: #ff7e5f;
            --bg-color: #121212;
            --text-color: #e0e0e0;
            --card-bg: #1e1e1e;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --border-color: #333;
            --hover-bg: rgba(107, 140, 255, 0.1);
        }

        .light-theme {
            --primary-color: #4a6fa5;
            --secondary-color: #6b8cae;
            --accent-color: #ff7e5f;
            --bg-color: #f5f7fa;
            --text-color: #333;
            --card-bg: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --border-color: #e0e0e0;
            --hover-bg: rgba(74, 111, 165, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .status-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-weight: 600;
        }

        .status-card h3 i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }

        .status-label {
            font-weight: 500;
            color: var(--secondary-color);
        }

        .status-value {
            font-weight: 600;
        }

        .progress-container {
            height: 8px;
            background-color: var(--border-color);
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
            width: 0%;
        }

        .progress-safe {
            background-color: var(--success-color);
        }

        .progress-warning {
            background-color: var(--warning-color);
        }

        .progress-danger {
            background-color: var(--danger-color);
        }

        /* Directory tree styles */
        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            padding: 0 10px;
            display: flex;
            align-items: center;
        }

        .breadcrumb a:hover {
            color: var(--accent-color);
        }

        .breadcrumb a::after {
            content: '›';
            position: absolute;
            right: -5px;
            color: var(--secondary-color);
        }

        .breadcrumb a:last-child::after {
            display: none;
        }

        .breadcrumb i {
            margin-right: 5px;
        }

        .directory-tree {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 20px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .directory-list {
            list-style: none;
            padding-left: 0;
        }

        .directory-item {
            margin: 5px 0;
            position: relative;
        }

        .directory-toggle {
            color: var(--text-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            padding: 8px 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .directory-toggle:hover {
            background-color: var(--hover-bg);
        }

        .directory-toggle::before {
            content: '▶';
            margin-right: 8px;
            font-size: 0.8em;
            transition: transform 0.3s ease;
            color: var(--secondary-color);
        }

        .directory-item.open > .directory-toggle::before {
            transform: rotate(90deg);
        }

        .directory-icon, .file-icon {
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }

        .subdirectory {
            padding-left: 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
            list-style: none;
        }

        .directory-item.open > .subdirectory {
            max-height: 5000px;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .file-item:hover {
            background-color: var(--hover-bg);
            transform: translateX(5px);
        }

        .empty-folder {
            padding: 10px 15px;
            color: var(--secondary-color);
            font-style: italic;
        }

        /* Network speed styles */
        .network-interface {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--border-color);
        }
        
        .network-interface:last-child {
            border-bottom: none;
        }
        
        .network-speed {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }
        
        .speed-direction {
            display: flex;
            align-items: center;
        }
        
        .speed-direction i {
            margin-right: 5px;
        }
        
        .download-speed {
            color: var(--success-color);
        }
        
        .upload-speed {
            color: var(--warning-color);
        }

        /* Theme toggle button */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 100;
            border: none;
            outline: none;
        }

        .theme-toggle i {
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .status-cards {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .theme-toggle {
                bottom: 10px;
                right: 10px;
                width: 40px;
                height: 40px;
            }
        }
    </style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-server"></i> 服务器监控与目录索引</h1>
            <p>实时服务器状态与文件目录浏览</p>
        </header>

        <!-- Server status cards -->
        <div class="status-cards">
            <div class="status-card">
                <h3><i class="fas fa-microchip"></i> CPU 状态</h3>
                <div class="status-item">
                    <span class="status-label">1分钟负载:</span>
                    <span class="status-value"><?= $serverStatus['cpu']['load_1min'] ?></span>
                </div>
<!--                <div class="status-item">
                    <span class="status-label"></span>
                    <a class="status-value" href="exit.php">退出登陆</a>
                </div>-->
                <div class="status-item">
                    <span class="status-label">当前时间:</span>
                    <span class="status-value"><?= $current_date ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">使用率:</span>
                    <span class="status-value"><?= $serverStatus['cpu']['usage'] === 'N/A' ? 'N/A' : $serverStatus['cpu']['usage'].'%' ?></span>
                </div>
                <?php if ($serverStatus['cpu']['usage'] !== 'N/A'): ?>
                <div class="progress-container">
                    <div class="progress-bar <?= getProgressClass($serverStatus['cpu']['usage']) ?>" 
                         style="width: <?= min($serverStatus['cpu']['usage'], 100) ?>%"></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="status-card">
                <h3><i class="fas fa-memory"></i> 内存状态</h3>
                <div class="status-item">
                    <span class="status-label">总内存:</span>
                    <span class="status-value"><?= $serverStatus['memory']['total'] ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">已用内存:</span>
                    <span class="status-value"><?= $serverStatus['memory']['used'] ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">空闲内存:</span>
                    <span class="status-value"><?= $serverStatus['memory']['free'] ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">使用率:</span>
                    <span class="status-value"><?= $serverStatus['memory']['usage'] === 'N/A' ? 'N/A' : $serverStatus['memory']['usage'].'%' ?></span>
                </div>
                <?php if ($serverStatus['memory']['usage'] !== 'N/A'): ?>
                <div class="progress-container">
                    <div class="progress-bar <?= getProgressClass($serverStatus['memory']['usage']) ?>" 
                         style="width: <?= min($serverStatus['memory']['usage'], 100) ?>%"></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="status-card">
                <h3><i class="fas fa-hdd"></i> 磁盘状态</h3>
                <div class="status-item">
                    <span class="status-label">总空间:</span>
                    <span class="status-value"><?= $serverStatus['disk']['total'] ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">可用空间:</span>
                    <span class="status-value"><?= $serverStatus['disk']['free'] ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">使用率:</span>
                    <span class="status-value"><?= $serverStatus['disk']['usage'] === 'N/A' ? 'N/A' : $serverStatus['disk']['usage'].'%' ?></span>
                </div>
                <?php if ($serverStatus['disk']['usage'] !== 'N/A'): ?>
                <div class="progress-container">
                    <div class="progress-bar <?= getProgressClass($serverStatus['disk']['usage']) ?>" 
                         style="width: <?= $serverStatus['disk']['usage'] ?>%"></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="status-card">
                <h3><i class="fas fa-network-wired"></i> 网络信息</h3>
                <div class="status-item">
                    <span class="status-label">服务器IP:</span>
                    <span class="status-value"><?= $serverStatus['network']['ip'] ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">主机名:</span>
                    <span class="status-value"><?= $serverStatus['system']['hostname'] ?></span>
                </div>                
                <!-- 实时网速监控 -->
                <?php foreach ($serverStatus['network'] as $interface => $data): ?>
                    <?php if (is_array($data) && isset($data['rx']) && isset($data['tx'])): ?>
                        <div class="network-interface">
                            <div class="status-item">
                                <span class="status-label"><?= ($interface === 'all' ? '总' : '接口 '.$interface) ?>网速:</span>
                            </div>
                            <div class="network-speed">
                                <div class="speed-direction">
                                    <i class="fas fa-arrow-down download-speed"></i>
                                    <span class="download-speed"><?= $data['rx'] ?></span>
                                </div>
                                <div class="speed-direction">
                                    <i class="fas fa-arrow-up upload-speed"></i>
                                    <span class="upload-speed"><?= $data['tx'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="status-item">
                    <span class="status-label">运行时间:</span>
                    <span class="status-value"><?= $serverStatus['system']['uptime'] ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label"></span>
                    <a class="status-value" href="exit.php">退出登陆</a>
                </div>
            </div>
        </div>

        <!-- 目录树内容 -->
        <div class="breadcrumb">
            <a href="?path="><i class="fas fa-home"></i> 根目录</a>
            <?php
            $pathParts = explode('/', trim($currentPath, '/'));
            $accumulatedPath = '';
            foreach ($pathParts as $part) {
                if (!empty($part)) {
                    $accumulatedPath .= '/' . $part;
                    echo '<a href="?path=' . urlencode($accumulatedPath) . '"><i class="fas fa-folder"></i> ' . htmlspecialchars($part) . '</a>';
                }
            }
            ?>
        </div>

        <div class="directory-tree">
            <?php if (!is_dir($currentDir)): ?>
                <div class="empty-folder">目录不存在或无法访问</div>
            <?php elseif (empty($items)): ?>
                <div class="empty-folder">空文件夹</div>
            <?php else: ?>
            <ul class="directory-list">
                <?php
                foreach ($items as $item) {
                    $itemPath = $currentDir . DIRECTORY_SEPARATOR . $item;
                    $relativePath = ltrim($currentPath . '/' . $item, '/');
                    
                    if (is_dir($itemPath)) {
                        $subItems = @array_diff(scandir($itemPath), ['.', '..']);
                        $hasChildren = !empty($subItems);
                        ?>
                        <li class="directory-item <?= $hasChildren ? 'has-children' : '' ?>">
                            <div class="directory-toggle">
                                <span class="directory-icon"><i class="far fa-folder"></i></span>
                                <span class="directory-name"><?= htmlspecialchars($item) ?></span>
                            </div>
                            <?php if ($hasChildren): ?>
                            <ul class="subdirectory">
                                <?php foreach ($subItems as $subItem): ?>
                                    <?php
                                    $subItemPath = $itemPath . DIRECTORY_SEPARATOR . $subItem;
                                    $subRelativePath = $relativePath . '/' . $subItem;
                                    
                                    if (is_dir($subItemPath)) {
                                        ?>
                                        <li class="directory-item">
                                            <a href="?path=<?= urlencode($subRelativePath) ?>" class="directory-toggle">
                                                <span class="directory-icon"><i class="far fa-folder"></i></span>
                                                <span class="directory-name"><?= htmlspecialchars($subItem) ?></span>
                                            </a>
                                        </li>
                                        <?php
                                    } else {
                                        ?>
                                        <li>
                                            <a href="<?= htmlspecialchars($subRelativePath) ?>" class="file-item" target="_blank">
                                                <span class="file-icon"><?= getFileIcon($subItem) ?></span>
                                                <span><?= htmlspecialchars($subItem) ?></span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </li>
                        <?php
                    } else {
                        ?>
                        <li>
                            <a href="<?= htmlspecialchars($relativePath) ?>" class="file-item" target="_blank">
                                <span class="file-icon"><?= getFileIcon($item) ?></span>
                                <span><?= htmlspecialchars($item) ?></span>
                            </a>
                        </li>
                        <?php
                    }
                }
                ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>

    <script>
        // Detect system color scheme preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const themeToggle = document.getElementById('themeToggle');
        
        // Initialize theme based on system preference
        if (prefersDark) {
            document.documentElement.classList.add('dark-theme');
        } else {
            document.documentElement.classList.add('light-theme');
        }
        
        // Update theme icon
        function updateThemeIcon() {
            const isDark = document.documentElement.classList.contains('dark-theme');
            themeToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        }
        
        // Toggle theme
        themeToggle.addEventListener('click', () => {
            if (document.documentElement.classList.contains('dark-theme')) {
                document.documentElement.classList.remove('dark-theme');
                document.documentElement.classList.add('light-theme');
            } else {
                document.documentElement.classList.remove('light-theme');
                document.documentElement.classList.add('dark-theme');
            }
            updateThemeIcon();
        });
        
        // Initialize
        updateThemeIcon();
        document.addEventListener('DOMContentLoaded', function() {
            // 处理目录项的点击事件
            document.querySelectorAll('.directory-item.has-children > .directory-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'A') { // 防止阻止链接跳转
                        e.preventDefault();
                        this.parentElement.classList.toggle('open');
                    }
                });
            });
            
            // 为文件项添加悬停动画
            document.querySelectorAll('.file-item').forEach(file => {
                file.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                file.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
            
            // 自动刷新数据（每2秒）
            setInterval(function() {
                fetch(window.location.pathname + '?ajax=1&path=<?= urlencode($currentPath) ?>')
                    .then(response => response.json())
                    .then(data => {
                        updateStatus(data);
                        updateNetworkSpeed(data.network);
                    })
                    .catch(error => console.error('刷新失败:', error));
            }, 2000);
        });
        
        // 更新网速显示
        function updateNetworkSpeed(networkData) {
            for (const [interface, stats] of Object.entries(networkData)) {
                if (typeof stats === 'object' && stats.rx && stats.tx) {
                    const container = document.querySelector(`.network-interface:has(.status-label:contains("${interface === 'all' ? '总' : '接口 '+interface}"))`);
                    if (container) {
                        container.querySelector('.download-speed').textContent = stats.rx;
                        container.querySelector('.upload-speed').textContent = stats.tx;
                    }
                }
            }
        }
        
        // 更新状态卡片
        function updateStatus(data) {
            if (!data) return;
            
            // 更新CPU状态
            updateCardData('.status-card:nth-child(1)', data.cpu);
            // 更新内存状态
            updateCardData('.status-card:nth-child(2)', data.memory);
            // 更新磁盘状态
            updateCardData('.status-card:nth-child(3)', data.disk);
            // 更新系统信息
            updateTextContent('.status-card:nth-child(4) .status-item:nth-child(2) .status-value', data.system.hostname);
            updateTextContent('.status-card:nth-child(4) .status-item:nth-child(4) .status-value', data.system.uptime);
        }
        
        function updateCardData(selector, data) {
            if (!data) return;
            
            const card = document.querySelector(selector);
            if (!card) return;
            
            // 更新所有状态项
            let index = 1;
            for (const [key, value] of Object.entries(data)) {
                if (key === 'cores') continue; // 跳过核心数显示
                
                const element = card.querySelector(`.status-item:nth-child(${index}) .status-value`);
                if (element) {
                    element.textContent = value === 'N/A' ? 'N/A' : 
                        (typeof value === 'number' ? (key === 'usage' ? value + '%' : value) : value);
                }
                index++;
            }
            
            // 更新进度条
            if (data.usage && data.usage !== 'N/A') {
                const progressBar = card.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = Math.min(data.usage, 100) + '%';
                    progressBar.className = 'progress-bar ' + getProgressClass(data.usage);
                }
            }
        }
        
        function updateTextContent(selector, text) {
            const element = document.querySelector(selector);
            if (element) element.textContent = text;
        }
        
        function getProgressClass(percentage) {
            if (percentage === 'N/A') return '';
            percentage = parseFloat(percentage);
            if (percentage < 60) return 'progress-safe';
            if (percentage < 85) return 'progress-warning';
            return 'progress-danger';
        }
    </script>
</body>
</html>

<?php
// 处理AJAX请求
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode(getServerStatus());
    exit;
}
?>
