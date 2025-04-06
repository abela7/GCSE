<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#cdaf56">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Do It!">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Favicon/Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon/favicon.ico">
    
    <!-- Mobile-friendly CSS -->
    <style>
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        
        body {
            -webkit-text-size-adjust: 100%;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }
        
        button, a {
            touch-action: manipulation;
        }
    </style>
    
    <title>GCSE Tracker<?php echo isset($page_title) ? ' - ' . $page_title : ''; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="/assets/css/reports.css" rel="stylesheet">
    
    <?php
    // --- Conditional CSS Loading ---
    // Get the directory of the currently running script
    $current_page_directory = basename(dirname($_SERVER['PHP_SELF'])); // Gets the last folder name

    if ($current_page_directory === 'EnglishPractice') {
        // Construct the relative path from the includes folder to the EnglishPractice CSS
        echo '<link rel="stylesheet" href="/pages/EnglishPractice/style.css">'; // USE ABSOLUTE PATH FROM WEB ROOT
        // Add JavaScript for English Practice
        echo '<script src="/pages/EnglishPractice/script.js"></script>';
    } elseif ($current_page_directory === 'tasks') {
         // Example for task specific CSS
         echo '<link rel="stylesheet" href="/assets/css/tasks.css">'; // Or /pages/tasks/style.css if you move that too
    }
    // Add more elseif conditions for other feature-specific CSS files
    // --- End Conditional CSS ---
    ?>
    
    <style>
        :root {
            --primary-color: #cdaf56;
            --primary-hover: #b89a45;
            --text-color: #000000;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            padding: 0;
            min-height: 48px;
        }
        
        .navbar .container-fluid {
            padding: 0.25rem 0.75rem;
        }
        
        .navbar-brand {
            color: var(--text-color) !important;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.35rem 0.7rem;
            border-radius: 6px;
            transition: background-color 0.3s;
            margin-right: 0.5rem;
        }
        
        .navbar-brand i {
            font-size: 0.9rem;
        }
        
        .nav-link {
            color: var(--text-color) !important;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.35rem 0.7rem !important;
            margin: 0 0.1rem;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            white-space: nowrap;
            height: 32px;
        }
        
        .nav-link i {
            font-size: 0.85rem;
            margin-right: 0.3rem;
        }
        
        .nav-link:hover {
            background-color: rgba(0,0,0,0.05);
            transform: translateY(-1px);
        }
        
        .nav-link.active {
            background-color: rgba(0,0,0,0.1);
            font-weight: 600;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 6px;
            padding: 0.3rem;
            min-width: 160px;
            margin-top: 0.25rem;
        }
        
        .dropdown-item {
            color: var(--text-color);
            padding: 0.35rem 0.7rem;
            border-radius: 4px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: rgba(0,0,0,0.05);
            transform: translateX(3px);
        }
        
        .navbar-toggler {
            border: none;
            padding: 0.35rem;
            border-radius: 6px;
            transition: background-color 0.2s;
            font-size: 0.9rem;
        }
        
        .form-control {
            border: none;
            background-color: rgba(0,0,0,0.05);
            border-radius: 16px;
            padding: 0.35rem 0.8rem;
            font-size: 0.85rem;
            width: 180px;
            transition: all 0.2s;
            height: 32px;
        }
        
        .form-control:focus {
            background-color: rgba(0,0,0,0.1);
            box-shadow: none;
            width: 220px;
        }
        
        .btn-outline-light {
            color: var(--text-color);
            border: none;
            background-color: rgba(0,0,0,0.05);
            border-radius: 16px;
            padding: 0.35rem 0.7rem;
            font-size: 0.85rem;
            transition: all 0.2s;
            height: 32px;
            display: flex;
            align-items: center;
        }
        
        .btn-outline-light:hover {
            background-color: rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background-color: var(--primary-color);
                padding: 0.6rem;
                border-radius: 8px;
                margin-top: 0.4rem;
            }
            
            .nav-link {
                margin: 0.15rem 0;
            }
            
            .form-control {
                width: 100%;
                margin-bottom: 0.4rem;
            }
            
            .form-control:focus {
                width: 100%;
            }
        }

        /* Install Banner Styles */
        .install-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            animation: slideUp 0.3s ease-out;
        }

        .btn-install {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        }

        .btn-install:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    
    <!-- HTMX Settings -->
    <script>
        htmx.config.useTemplateFragments = true;
        document.body.addEventListener('htmx:configRequest', (event) => {
            event.detail.headers['X-Requested-With'] = 'XMLHttpRequest';
        });
    </script>

    <!-- Test script loading -->
    <script>
        console.log('Header loaded, about to load notifications.js');
    </script>

    <!-- Notifications -->
    <script src="/assets/js/notifications.js"></script>

    <!-- Test notifications loaded -->
    <script>
        console.log('Notifications.js should be loaded now');
    </script>

    <script>
        // Request notification permission
        if ('Notification' in window) {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }

        // Service Worker Registration with notification support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    console.log('ServiceWorker registration successful');
                    
                    // Request notification permission after SW registration
                    if ('Notification' in window) {
                        const permission = await Notification.requestPermission();
                        if (permission === 'granted') {
                            console.log('Notification permission granted');
                        }
                    }
                } catch (err) {
                    console.log('ServiceWorker registration failed: ', err);
                }
            });
        }
    </script>

    <!-- PWA Install Script -->
    <script src="/assets/js/install.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Top Navigation Bar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="/index.php">
                    <i class="fas fa-home me-2"></i>Home
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a href="/pages/exam_countdown.php" class="nav-link <?php echo $current_page == 'exam_countdown.php' ? 'active' : ''; ?>">
                                <i class="fas fa-clock me-1"></i> Exams
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/pages/EnglishPractice/index.php" class="nav-link <?php echo $current_page_directory == 'EnglishPractice' ? 'active' : ''; ?>">
                                <i class="fas fa-language me-1"></i> English Practice
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-book me-1"></i> Subjects
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/pages/subjects/math.php">Mathematics</a></li>
                                <li><a class="dropdown-item" href="/pages/subjects/english.php">English</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/pages/EnglishPractice/daily_entry.php">
                                    <i class="fas fa-pen me-2"></i>English Practice
                                </a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-graduation-cap me-1"></i> Access to HE
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/pages/assignments.php">Assignments</a></li>
                                <li><a class="dropdown-item" href="/pages/access_progress.php">Progress</a></li>
                                <li><a class="dropdown-item" href="/pages/access_resources.php">Resources</a></li>
                                <li><a class="dropdown-item" href="/pages/access_schedule.php">Schedule</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-clock me-1"></i> Time
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/pages/habits/index.php">Habits</a></li>
                                <li><a class="dropdown-item" href="/pages/tasks/index.php">Tasks</a></li>
                                <!-- More time-related items will be added here -->
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page == 'resources.php' ? 'active' : ''; ?>" href="/pages/resources.php">
                                <i class="fas fa-folder me-1"></i> Resources
                            </a>
                        </li>
                    </ul>
                    <form class="d-flex">
                        <input class="form-control me-2" type="search" placeholder="Search topics..." aria-label="Search">
                        <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
        </nav>
        
        <!-- Main Content Container -->
        <div class="container-fluid mt-4">
            <!-- Breadcrumb navigation -->
            <?php if (isset($breadcrumbs)): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
                    <?php foreach ($breadcrumbs as $label => $url): ?>
                        <?php if ($url): ?>
                            <li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo $label; ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $label; ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <?php endif; ?>
            
            <!-- Page Title -->
            <?php if (isset($page_actions)): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="page-actions">
                    <?php echo $page_actions; ?>
                </div>
            </div>
            <?php endif; ?>