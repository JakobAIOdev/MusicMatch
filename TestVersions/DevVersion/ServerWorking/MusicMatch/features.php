<?php
$pageTitle = "MusicMatch - Features";
$additionalCSS = '<link rel="stylesheet" href="./assets/css/features.css">';

require_once './includes/session_handler.php';
include "./templates/header.php";
require_once './vendor/autoload.php';
include_once "./includes/config.php";
require_once './includes/spotify_utils.php';
?>

<section class="hero-section">
    <div class="container">
        <h1>MusicMatch <span>Features</span></h1>
        <p class="intro-text">Discover how MusicMatch can enhance your music discovery experience with our powerful features, designed for personalized music exploration and playlist creation.</p>
    </div>
    <div class="down-arrow-container">
        <a href="#music-swiper" class="down-arrow">
            <img src="./assets/img/icons/arrow-down.svg" alt="">
        </a>
    </div>
</section>

<section id="music-swiper" class="feature-section">
    <div class="container">
        <div class="feature-header">
            <div class="feature-icon music-swiper-icon animate-on-scroll">
                <img src="./assets/img/icons/swipe.svg" alt="MusicSwiper Icon">
            </div>
            <h2>MusicSwiper</h2>
            <p>Discover new music through an intuitive swipe interface.<br> Like what you hear? Swipe right. Not your style? Swipe left.</p>
        </div>

        <div class="feature-content">
            <div class="feature-detail">
                <h3>How to Use MusicSwiper</h3>
                <div class="notice-container animate-on-scroll">
                    <span class="step-number">!</span>
                    <div class="notice-text">
                        <p>This Feature is only avaible to Spotify Premium Users</p>
                    </div>
                </div>
                <ol class="feature-steps">
                    <li class="animate-on-scroll">
                        <span class="step-number">1</span>
                        <div>
                            <h4>Select a Swipe Method</h4>
                            <p>Choose how you want to discover music:</p>
                            <ul>
                                <li><strong>Random:</strong> Discover tracks based on your listening history</li>
                                <li><strong>Billboard Hot 100:</strong> Explore current trending tracks</li>
                                <li><strong>Favorites (4-Weeks/6-Months/All-Time):</strong> Rediscover your top tracks from different time periods</li>
                                <li><strong>Playlist:</strong> Use a specific Spotify playlist as your source</li>
                                <li><strong>LastFM:</strong> Get recommendations based on your LastFM profile</li>
                            </ul>
                        </div>
                    </li>
                    <li class="animate-on-scroll">
                        <span class="step-number">2</span>
                        <div>
                            <h4>Preview Tracks</h4>
                            <p>Each card shows a 30-second preview of the track. Use the player controls to:</p>
                            <ul>
                                <li>Play/Pause the preview</li>
                                <li>Adjust volume</li>
                                <li>Track your progress through the preview</li>
                            </ul>
                        </div>
                    </li>
                    <li class="animate-on-scroll">
                        <span class="step-number">3</span>
                        <div>
                            <h4>Make Your Choice</h4>
                            <p>Three ways to like or dislike a track:</p>
                            <div class="interaction-methods">
                                <div class="method">
                                    <img src="./assets/img/icons/swipe.svg" alt="Swipe Gesture">
                                    <span>Swipe right (like) or left (dislike)</span>
                                </div>
                                <?php /*
                                    <div class="method">
                                        <img src="./assets/img/icons/arrows.svg" alt="Arrow Keys">
                                        <span>Press → (like) or ← (dislike) keys</span>
                                    </div>
                                    */ ?>
                                <div class="method">
                                    <img src="./assets/img/icons/like-blue.svg" alt="Buttons">
                                    <span>Use the like/dislike buttons</span>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li id="feature-steps-last" class="animate-on-scroll">
                        <span class="step-number">4</span>
                        <div>
                            <h4>Manage Your Liked Songs</h4>
                            <p>After liking songs, you can:</p>
                            <ul>
                                <li>Create a new playlist with liked songs</li>
                                <li>Add liked songs to an existing playlist</li>
                                <li>Reset your liked songs and start fresh</li>
                            </ul>
                        </div>
                    </li>
                </ol>
            </div>
            <div class="feature-showcase" id="swiper-preview">
                <div class="swiper-demo animate-on-scroll">
                    <div class="demo-card" id="demo-swiper-card">
                        <div class="swipe-overlay like-overlay"></div>
                        <div class="swipe-overlay dislike-overlay"></div>

                        <div class="demo-card-image"></div>

                        <div class="demo-card-content">
                            <h4 class="demo-card-title">Song Name</h4>
                            <p class="demo-card-artist">Artist</p>
                            <p class="demo-card-album">Album</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="favorites" class="feature-section alt-bg">
    <div class="container">
        <div class="feature-header">
            <div class="feature-icon favorites-icon animate-on-scroll">
                <img src="./assets/img/icons/discover.svg" alt="Favorites Icon">
            </div>
            <h2>Favorites</h2>
            <p>Access, manage, and organize tracks you've liked during your swiping sessions.</p>
        </div>

        <div class="feature-content reverse">
            <div class="feature-detail">
                <h3>Managing Your Favorites</h3>
                <ul class="feature-steps">
                    <li class="animate-on-scroll">
                        <span class="step-number">1</span>
                        <div>
                            <h4>Discover Your Most Listened Songs & Artists</h4>
                            <p>View your top tracks and artists, sorted by three different time periods:</p>
                            <ul>
                                <li><b>Short Term</b> (last 4 weeks)</li>
                                <li><b>Mid Term</b> (last 6 months)</li>
                                <li><b>Long Term</b> (overall)</li>
                            </ul>
                        </div>
                    </li>
                    <li class="animate-on-scroll">
                        <span class="step-number">2</span>
                        <div>
                            <h4>Play Your Favorites</h4>
                            <p>Click the Spotify button next to any song or Artist to listen to your favorites.</p>
                        </div>
                    </li>
                    <li class="animate-on-scroll">
                        <span class="step-number">3</span>
                        <div>
                            <h4>Create Playlists</h4>
                            <p>Turn your collection of liked songs into a Spotify playlist:</p>
                            <ul>
                                <li>Click "Create Playlist" button</li>
                                <li>Name your playlist</li>
                                <li>Enjoy your favorite</li>
                            </ul>
                        </div>
                    </li>
                    <li class="animate-on-scroll">
                        <span class="step-number">4</span>
                        <div>
                            <h4>Add to Existing Playlists</h4>
                            <p>Add your liked songs to playlists you've already created:</p>
                            <ul>
                                <li>Click "Add to Playlist" button</li>
                                <li>Select the target playlist from the dropdown</li>
                                <li>Confirm your selection</li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="feature-showcase">
                <div class="favorites-demo animate-on-scroll">
                    <div class="liked-songs-header">
                        <h4>Your Top Tracks on Spotify</h4>
                    </div>

                    <div class="liked-song">
                        <div class="song-image"></div>
                        <div class="song-info">
                            <h4>Song Name</h4>
                            <p>Artist</p>
                        </div>
                        <div class="song-actions">
                            <button class="action-button play-button">▶</button>
                        </div>
                    </div>

                    <div class="liked-song">
                        <div class="song-image second-image"></div>
                        <div class="song-info">
                            <h4>Song Name</h4>
                            <p>Artist</p>
                        </div>
                        <div class="song-actions">
                            <button class="action-button play-button">▶</button>
                        </div>
                    </div>

                    <div class="liked-song">
                        <div class="song-image second-image"></div>
                        <div class="song-info">
                            <h4>Song Name</h4>
                            <p>Artist</p>
                        </div>
                        <div class="song-actions">
                            <button class="action-button play-button">▶</button>
                        </div>
                    </div>

                    <div class="playlist-actions">
                        <button class="btn btn-primary btn-sm">Create Playlist</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Start Discovering?</h2>
        <p class="cta-description">Jump right into MusicSwiper and find your next favorite song.</p>
        <div class="cta-buttons">
            <?php if (!isset($_SESSION['spotify_access_token'])): ?>
                <a href="/auth/login.php" class="btn spotify-button">
                    <img class="spotify-icon" src="./assets/img/icons/spotify-primary-white.svg" alt="Spotify Logo">
                    <span>Login with Spotify</span>
                </a>
            <?php else: ?>
                <a href="swiper.php" class="btn btn-primary">
                    <img src="./assets/img/icons/swipe.svg" alt="Swipe Icon" class="spotify-icon">
                    <span>Go to Swiper</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="./assets/js/animations.js"></script>

<?php include './templates/footer.php'; ?>