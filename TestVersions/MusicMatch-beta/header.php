<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - MusicMatch' : 'MusicMatch'; ?></title>
    <link rel="icon" type="image/x-icon" href="./img/musicmatch.svg">
    <link rel="stylesheet" href="./styles/style.css">
    <link rel="stylesheet" href="./styles/header.css">
    <link rel="stylesheet" href="./styles/footer.css"> <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>

<body>
    <header class="site-header">
        <div class="container header-container">
            <div class="site-logo"> <a href="index.php"> <img src="img/musicmatch.svg" alt="MusicMatch Logo"> <span>MusicMatch</span> </a> </div>
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle menu">
                <div class="burger-menu">
                    <span class="burger-line"></span>
                    <span class="burger-line"></span>
                    <span class="burger-line"></span>
                </div>
            </button>

            <nav class="site-nav" id="site-nav">
                <ul class="nav-menu">
                    <li><a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="index.php#features" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php#features') ? 'active' : ''; ?>">Features</a></li>
                    <li><a href="favorites.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'favorites.php') ? 'active' : ''; ?>">Favorites</a></li>
                    <li><a href="swiper.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'swiper.php') ? 'active' : ''; ?>">Music Swiper</a></li>
                    <li><a href="about.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : ''; ?>">About</a></li>
                </ul>

                <div class="auth-buttons">
                    <?php
                    // Debug information
                    if (isset($_SESSION)) {
                        error_log('SESSION exists in header: ' . print_r($_SESSION, true));
                    } else {
                        error_log('No SESSION in header');
                    }
                    
                    if (isset($_SESSION['userData']) && !empty($_SESSION['userData'])): 
                        error_log('User data found in session: ' . print_r($_SESSION['userData'], true));
                    ?>
                        <div class="user-profile">
                            <?php
                            $profileImage = 'img/default-avatar.png';
                            
                            // Properly handle different structures of images data
                            if (!empty($_SESSION['userData']['images'])) {
                                if (is_array($_SESSION['userData']['images'])) {
                                    if (isset($_SESSION['userData']['images'][0]->url)) {
                                        $profileImage = htmlspecialchars($_SESSION['userData']['images'][0]->url);
                                    } elseif (isset($_SESSION['userData']['images'][0]['url'])) {
                                        $profileImage = htmlspecialchars($_SESSION['userData']['images'][0]['url']);
                                    }
                                } elseif (is_object($_SESSION['userData']['images']) && isset($_SESSION['userData']['images']->url)) {
                                    $profileImage = htmlspecialchars($_SESSION['userData']['images']->url);
                                }
                            }
                            
                            $displayName = isset($_SESSION['userData']['display_name']) ? 
                                           htmlspecialchars($_SESSION['userData']['display_name']) : 'User';
                            ?>
                            <img src="<?php echo $profileImage; ?>" alt="Profile Picture">
                            <span><?php echo "<a href='./profile.php'>$displayName</a>"; ?></span>
                        </div>
                        <a href="logout.php" class="btn btn-outline">Logout</a>
                    <?php else: 
                        error_log('No user data in session');
                    ?>
                        <a href="./login.php" class="btn btn-outline">Login</a>
                        <a href="signup.php" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main>