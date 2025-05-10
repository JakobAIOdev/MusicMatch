<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - MusicMatch' : 'MusicMatch'; ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo $BASE_URL; ?>assets/img/icons/musicmatch-logo.svg">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/fonts.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/header.css">
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>assets/css/footer.css">
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>

<body>
    <header class="site-header">
        <div class="header-container">
            <div class="site-logo">
                <a href="<?php echo $BASE_URL; ?>index.php">
                    <img src="<?php echo $BASE_URL; ?>assets/img/icons/musicmatch-logo.svg" alt="MusicMatch Logo">
                    <span>MusicMatch</span>
                </a>
            </div>
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle menu">
                <span class="burger-menu">
                    <span class="burger-line"></span>
                    <span class="burger-line"></span>
                    <span class="burger-line"></span>
                </span>
            </button>
            <nav class="site-nav" id="site-nav">
                <ul class="nav-menu">
                    <li><a href="<?php echo $BASE_URL; ?>index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo $BASE_URL; ?>features.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'features.php') ? 'active' : ''; ?>">Features</a></li>
                    <li><a href="<?php echo $BASE_URL; ?>favorites.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'favorites.php') ? 'active' : ''; ?>">Favorites</a></li>
                    <li><a href="<?php echo $BASE_URL; ?>swiper.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'swiper.php') ? 'active' : ''; ?>">Swiper</a></li>
                </ul>
            </nav>
            <div class="auth-buttons">
                <?php
                if (isset($_SESSION['userData']) && !empty($_SESSION['userData'])):
                    //error_log('User data found in session: ' . print_r($_SESSION['userData'], true));
                ?>
                    <div class="user-profile">
                        <a href="<?php echo $BASE_URL; ?>profile.php" class="user-profile-link">
                            <div class="user-profile">
                                <?php
                                $profileImage = $BASE_URL . 'assets/img/default-avatar.png';

                                if (isset($_SESSION['userData']['images'][0]->url)) {
                                    $profileImage = htmlspecialchars($_SESSION['userData']['images'][0]->url);
                                }

                                $displayName = isset($_SESSION['userData']['display_name']) ?
                                    htmlspecialchars($_SESSION['userData']['display_name']) : 'User';
                                ?>
                                <img src="<?php echo $profileImage; ?>" alt="Profile Picture">
                                <span><?php echo $displayName; ?></span>
                            </div>
                        </a>
                    </div>
                    <a href="javascript:void(0);" onclick="performLogout();" class="btn btn-outline header-auth-btn">Logout</a>
                <?php else:
                    error_log('No user data in session');
                ?>
                    <a href="<?php echo $BASE_URL; ?>auth/login.php" class="btn btn-outline header-auth-btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main>