<?php
function showLoginNotice()
{
?>
    <div class="login-notice">
        <div class="login-notice-content">
            <div class="login-notice-icon">
            </div>
            <div class="login-notice-text">
                <h3>Login-Needed</h3>
                <p>You have to be logged in.</p>
                <p class="login-notice-subtext">Login with Spotify, to gain access to this feature.</p>
            </div>
            <div class="login-notice-actions">
                <a href="/auth/login.php" class="spotify-button">
                    <img class="spotify-icon" src="./assets/img/icons/spotify-primary-white.svg" alt="Spotify">
                    <span>Login with Spotify</span>
                </a>
                <a href="/index.php" class="btn btn-primary" id="close-btn">Close</a>
            </div>
        </div>
    </div>

    <style>
        .login-notice {
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

        .login-notice-content {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 90%;
            max-width: 500px;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        #close-btn:hover {
            color: var(--gray);
        }

        .login-notice-icon {
            margin-bottom: 15px;
        }

        .login-notice-text h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #333;
        }

        .login-notice-text p {
            margin-bottom: 10px;
            color: #555;
        }

        .login-notice-subtext {
            font-size: 14px;
            color: #777;
        }

        .login-notice-actions {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        #close-btn:hover {
            background-color: var(--primary-dark);
            color: var(--white);
        }

        @media (max-width: 576px) {
            .login-notice-content {
                padding: 20px;
            }

            .login-notice-actions {
                flex-direction: column;
                gap: 15px;
            }

            .login-notice-icon i {
                font-size: 36px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const closeButton = document.querySelector('.login-notice-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    const noticeElement = document.querySelector('.login-notice');
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