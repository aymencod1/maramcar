<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// معالجة تغيير اللغة
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// تحديد اللغة الافتراضية
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ar';
$translations = include 'lang/' . $lang . '.php';
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaramCAR - <?= $translations['car_rental'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold: #FFD700;
            --dark: #121212;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .navbar {
            background: var(--dark);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            border-bottom: 2px solid var(--gold);
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--gold) !important;
        }
        
        .logo-img {
            height: 40px;
            margin-right: 10px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 600;
            margin: 0 8px;
            padding: 8px 15px !important;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--gold) !important;
            background: rgba(255, 215, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-weight: bold;
            margin-left: 10px;
        }
        
        .btn-outline-light {
            border-radius: 12px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
            border-color: var(--gold);
            color: var(--gold);
        }
        
        .btn-outline-light:hover {
            background: var(--gold);
            color: var(--dark) !important;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
        }
        
        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.2);
            padding: 10px;
            background: var(--dark);
        }
        
        .dropdown-item {
            border-radius: 8px;
            padding: 8px 15px;
            margin: 3px 0;
            transition: all 0.2s;
            color: rgba(255, 255, 255, 0.85);
        }
        
        .dropdown-item:hover {
            background: rgba(255, 215, 0, 0.1);
            color: var(--gold);
        }
        
        .lang-switcher {
            margin-left: 15px;
        }
        
        .lang-flag {
            width: 30px;
            height: 20px;
            object-fit: cover;
            border-radius: 3px;
            margin-right: 5px;
        }
        
        @media (max-width: 991px) {
            .navbar-collapse {
                background: var(--dark);
                padding: 15px;
                border-radius: 15px;
                margin-top: 15px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 215, 0, 0.2);
            }
            
            .nav-link {
                margin: 5px 0;
                padding: 10px 15px !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <!-- إضافة لوجو MaramCAR هنا -->
                <img src="assets/images/maramcar.png.jpg" alt="MaramCAR Logo" class="logo-img">
                MaramCAR
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-home me-1"></i> <?= $translations['home'] ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'active' : '' ?>" href="cars.php">
                            <i class="fas fa-car me-1"></i> <?= $translations['cars'] ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : '' ?>" href="services.php">
                            <i class="fas fa-concierge-bell me-1"></i> <?= $translations['services'] ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : '' ?>" href="about.php">
                            <i class="fas fa-info-circle me-1"></i> <?= $translations['about'] ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : '' ?>" href="contact.php">
                            <i class="fas fa-phone-alt me-1"></i> <?= $translations['contact'] ?>
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <!-- مبدل اللغة -->
                    <li class="nav-item lang-switcher">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                                <img src="assets/images/<?= $lang ?>-flag.png" class="lang-flag" alt="<?= $lang ?>">
                                <?= strtoupper($lang) ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?lang=ar">
                                    <img src="assets/images/ar-flag.png" class="lang-flag" alt="ar"> العربية
                                </a></li>
                                <li><a class="dropdown-item" href="?lang=fr">
                                    <img src="assets/images/fr-flag.png" class="lang-flag" alt="fr"> Français
                                </a></li>
                            </ul>
                        </div>
                    </li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar">
                                    <?= mb_substr($_SESSION['user_name'], 0, 1) ?>
                                </div>
                                <span class="ms-2"><?= $_SESSION['user_name'] ?></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2"></i> <?= $translations['profile'] ?>
                                </a></li>
                                <li><a class="dropdown-item" href="bookings.php">
                                    <i class="fas fa-calendar-check me-2"></i> <?= $translations['bookings'] ?>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> <?= $translations['logout'] ?>
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">
                                <i class="fas fa-user-plus me-1"></i> <?= $translations['register'] ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> <?= $translations['login'] ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>