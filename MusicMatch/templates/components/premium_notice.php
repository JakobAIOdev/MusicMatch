<?php
function showPremiumNotice($featureName = "Feature") {
?>
    <div class="premium-notice">
        <div class="premium-notice-content">
            <div class="premium-notice-icon">
                <i class="fas fa-crown"></i>
            </div>
            <div class="premium-notice-text">
                <h3>Premium-Feature</h3>
                <p><?php echo htmlspecialchars($featureName); ?> is exclusive for Spotify Premium Users.</p>
                <p class="premium-notice-subtext">Upgrade to Premium, to gain access to this feature.</p>
            </div>
            <div class="premium-notice-actions">
                <a href="https://open.spotify.com/premium" class="spotify-button">
                    <img class="spotify-icon" src="./assets/img/icons/spotify-primary-white.svg" alt="Spotify">
                    <span>Upgrade to Premium</span>
                </a>
                <a href="/index.php" class="btn btn-primary" id="close-btn">Close</a>
            </div>
        </div>
    </div>

    <style>
        .premium-notice {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .premium-notice-content {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 500px;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        #close-btn:hover{
            color: var(--gray);
        }
        
        .premium-notice-icon {
            margin-bottom: 15px;
        }
        
        .premium-notice-text h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .premium-notice-text p {
            margin-bottom: 10px;
            color: #555;
        }
        
        .premium-notice-subtext {
            font-size: 14px;
            color: #777;
        }
        
        .premium-notice-actions {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        @media (max-width: 576px) {
            .premium-notice-content {
                padding: 20px;
            }
            
            .premium-notice-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .premium-notice-icon i {
                font-size: 36px;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const closeButton = document.querySelector('.premium-notice-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    const noticeElement = document.querySelector('.premium-notice');
                    if (noticeElement) {
                        noticeElement.style.display = 'none';
                    }
                });
            }
        });
    </script>
<?php
}
?>