# MusicMatch

**MusicMatch** is a modern web application for discovering new music and building playlists using an intuitive swipe-based interface. By connecting your Spotify account, you can preview 30-second clips from various sources, swipe right to like or left to skip, and easily create or update Spotify playlists with your favorite tracks.


<img width="11330" height="7329" alt="1-5" src="https://github.com/user-attachments/assets/303f1a82-8986-4110-896e-bf3ab04ddff4" />

---

## Table of Contents

- [Features](#features)
- [How It Works](#how-it-works)
- [Screenshots](#screenshots)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Setup & Installation](#setup--installation)
- [Usage](#usage)
- [Security & Privacy](#security--privacy)
- [License](#license)

---

## Features

- **Swipe-Based Discovery:** Browse music recommendations using a card-swiping interface.
- **Multiple Sources:** Discover songs from your Spotify favorites, curated playlists, artist discographies, or LastFM recommendations.
- **Spotify Integration:** Securely connect your Spotify account via OAuth 2.0 to access your library and manage playlists.
- **Audio Previews:** Listen to 30-second previews of each track before making a decision.
- **Playlist Management:** Instantly create new playlists or add liked songs to existing ones on Spotify.
- **Favorites & Insights:** View your top tracks and artists over different time periods and generate playlists from them.
- **Responsive Design:** Enjoy a seamless experience on desktop, tablet, and mobile devices.
- **Custom Playlist Covers:** Automatically add a custom cover image to playlists created with MusicMatch.
- **Session & Local Storage:** Liked tracks are saved persistently and can be managed across sessions.

---

## How It Works

1. **Login with Spotify:** Authenticate securely using OAuth 2.0. Your credentials are never shared with MusicMatch.
2. **Choose a Discovery Mode:** Select from personal favorites, curated playlists, artist deep dives, or LastFM recommendations.
3. **Swipe to Discover:** Preview tracks and swipe right to like or left to skip. Liked tracks are saved for later.
4. **Manage Playlists:** Create new Spotify playlists or add to existing ones with your liked songs.
5. **Explore Favorites:** Analyze your listening habits and generate playlists from your top tracks and artists.

---

## Screenshots

### Main Swiping Interface
<img src="https://github.com/user-attachments/assets/20661967-05f3-460d-808f-5ffe3fc4c09e" alt="MusicMatch Swiping Interface" width="600"/>

### Liked Songs
<img src="https://github.com/user-attachments/assets/344bb0d7-3559-4570-8d2a-443260719810" alt="Create Playlist from Liked Songs" width="600"/>

### Playlist on Spotify
<img width="600" alt="Playlist exported to Spotify" src="https://github.com/user-attachments/assets/0a20ebb6-2e2c-4a62-ac19-45afc65f388c" />

### Favorites & Insights
<img src="https://github.com/user-attachments/assets/5c9a6b37-c9c3-4edc-87db-c33f67253ab9" alt="Favorites and User Insights" width="600"/>

### Responsive Mobile View
<img src="https://github.com/user-attachments/assets/f0bf67a3-8728-47e3-af8e-8e4a216b2ebb" alt="MusicMatch on Mobile" width="300"/>

### Different Swiping Modes
<img width="600" alt="Modes to Swipe" src="https://github.com/user-attachments/assets/b3bcb171-caf0-4867-b8cf-8d09f418ca5a" />

### Video of Swiping Interface
https://github.com/user-attachments/assets/0aa1c9f4-fc02-470e-9fee-b2ab2e259b4a


---

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), [HammerJS](https://hammerjs.github.io/) (for touch gestures)
- **Backend:** PHP 7.4+, Composer
- **APIs:** [Spotify Web API](https://developer.spotify.com/documentation/web-api/) (via [jwilsson/spotify-web-api-php](https://github.com/jwilsson/spotify-web-api-php)), [LastFM API](https://www.last.fm/api)
- **Authentication:** OAuth 2.0 with secure session handling

---

## Project Structure
```
├── assets/
│   ├── css/
│   │   ├── auth.css                   # Authentication styling
│   │   ├── favorites.css              # Favorites page styling
│   │   ├── features.css               # Features page styling
│   │   ├── fonts.css                  # Font declarations
│   │   ├── footer.css                 # Footer component styling
│   │   ├── header.css                 # Header/navigation styling
│   │   ├── impressum.css              # Legal page styling
│   │   ├── index.css                  # Homepage styling
│   │   └── style.css                  # Global styling and variables
│   ├── fonts/
│   │   └── geist/                     # Geist font files
│   ├── img/
│   │   ├── icons/                     # SVG and other icons
│   │   ├── MusicMatchCover.jpg
│   │   ├── MusicMatchBackground.webp
│   │   └── MusicMatchSongCoverFeature.png
│   └── js/
│       ├── animations.js              # UI animation logic
│       ├── burger-menu.js             # Burger menu functionality
│       ├── favorites.js               # Favorites page interactions
│       ├── login-cleanup.js           # Authentication utilities
│       ├── logout.js                  # Logout functionality
│       ├── notifications.js           # Toast notification system
│       └── swiper.js                  # Main swipe interface logic
├── includes/
│   ├── config.php                     # Application configuration
│   ├── create_playlist.php            # Playlist creation API endpoint
│   ├── session_handler.php            # User session management
│   └── spotify_utils.php              # Spotify API utilities
├── templates/
│   ├── components/
│   │   └── premium_notice.php         # Premium feature notice component
│   ├── footer.php                     # Footer template
│   └── header.php                     # Header template
├── vendor/                            # Dependencies (Composer packages)
├── favorites.php                      # Favorites page
├── features.php                       # Features page
├── impressum.php                      # Legal information page
├── index.php                          # Index (Homepage)
├── privacy-policy.php                 # Privacy policy page
└── swiper.php                         # Main Music Swiper interface
```

## Setup & Installation

### Requirements

- PHP 7.4 or higher
- Composer
- Spotify Developer Account

### Installation Steps

### 1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/musicmatch.git
   cd musicmatch
  ```


### 2. Install PHP dependencies:
  ```bash
  composer install
  ```

### 3. Configure environment:

Copy includes/config-example.php to includes/config.php
Add your Spotify API credentials and callback URL in config.php
Set up your Spotify Developer Dashboard with the correct redirect URI

### 4. Run locally:

Use a local web server (e.g., XAMPP, MAMP, or PHP's built-in server)
Access the app in your browser


## Usage
+ Login: Click "Login with Spotify" to authenticate.
+ Swipe: Use the Music Swiper to discover and like new tracks.
+ Playlist Creation: Create or update Spotify playlists with your liked songs.
+ Favorites: View and manage your top tracks and artists.


## Security & Privacy
OAuth 2.0: Secure authentication with Spotify; your credentials are never stored.
Session Security: Secure session handling with CSRF protection and secure cookies.
Data Storage: Only essential user data is stored, and you can clear your liked tracks at any time.
Privacy Policy: See privacy-policy.php for full details.


## License
This project is for educational purposes. See LICENSE for more information.

MusicMatch – Discover your next favorite song, one swipe at a time!
