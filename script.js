// Globale Variablen
let currentSongIndex = 0; // Index des aktuellen Songs
let songResults = []; // Array für die Suchergebnisse
let likedSongs = []; // Array für die gelikten Songs

// Funktion: Spotify Access Token holen
document.getElementById('getTokenBtn').addEventListener('click', function () {
    fetch('get_token.php') // Ruft das PHP-Skript auf, das den Token liefert
        .then(response => response.json())
        .then(data => {
            if (data.access_token) {
                document.getElementById('tokenResult').innerText = 'Access Token:\n' + data.access_token;
            } else {
                document.getElementById('tokenResult').innerText = 'Fehler beim Abrufen des Tokens.';
            }
        })
        .catch(error => {
            console.error('Fehler:', error);
            document.getElementById('tokenResult').innerText = 'Fehler beim Abrufen des Tokens.';
        });
});

// Funktion: Suche nach Songs oder Künstlern
document.getElementById('searchBtn').addEventListener('click', function () {
    const query = document.getElementById('searchInput').value;

    if (!query) {
        alert('Bitte gib einen Suchbegriff ein.');
        return;
    }

    fetch('get_token.php') // Hole den Access Token von deinem PHP-Skript
        .then(response => response.json())
        .then(data => {
            if (data.access_token) {
                searchSpotify(data.access_token); // Starte die Suche mit dem Token
            } else {
                document.getElementById('searchResults').innerText = 'Fehler beim Abrufen des Tokens.';
            }
        })
        .catch(error => console.error('Fehler beim Holen des Tokens:', error));
});

// Funktion: Spotify API-Suche durchführen
function searchSpotify(token) {
    const query = encodeURIComponent(document.getElementById('searchInput').value); // Escape den Suchstring
    fetch(`https://api.spotify.com/v1/search?q=${query}&type=track&limit=5`, { // API-Aufruf mit Suchbegriff
        headers: { 'Authorization': 'Bearer ' + token }
    })
        .then(response => response.json())
        .then(data => {
            console.log('API-Ergebnisse:', data); // Debug-Ausgabe der API-Daten
            displayResults(data); // Ergebnisse anzeigen
        })
        .catch(error => console.error('Fehler bei der API-Suche:', error));
}

// Funktion: Ergebnisse verarbeiten und Swipe-Bereich vorbereiten
function displayResults(data) {
    if (data.tracks && data.tracks.items.length > 0) {
        songResults = data.tracks.items; // Speichere die Songs in einem Array
        currentSongIndex = 0; // Setze den Index zurück
        likedSongs = []; // Leere die Liste der gelikten Songs
        document.getElementById('likedList').innerHTML = ''; // Leere die Anzeige der gelikten Songs
        showNextSong(); // Zeige den ersten Song an
    } else {
        document.getElementById('songCard').innerText = 'Keine Songs gefunden.';
    }
}

// Funktion: Zeige den aktuellen Song im Swipe-Bereich an
function showNextSong() {
    const songCard = document.getElementById('songCard');

    if (currentSongIndex < songResults.length) {
        const song = songResults[currentSongIndex];
        songCard.innerHTML = `<strong>${song.name}</strong><br>${song.artists[0].name}`;
    } else {
        songCard.innerHTML = 'Keine weiteren Songs.';
        document.getElementById('likeBtn').disabled = true;
        document.getElementById('dislikeBtn').disabled = true;
    }
}

// Event-Listener: Like-Button (Song hinzufügen)
document.getElementById('likeBtn').addEventListener('click', function () {
    if (currentSongIndex < songResults.length) {
        likedSongs.push(songResults[currentSongIndex]); // Füge den aktuellen Song zur Liste hinzu
        updateLikedList(); // Aktualisiere die Anzeige der gelikten Songs
        currentSongIndex++; // Gehe zum nächsten Song
        showNextSong(); // Zeige den nächsten Song an
    }
});

// Event-Listener: Dislike-Button (Song ablehnen)
document.getElementById('dislikeBtn').addEventListener('click', function () {
    if (currentSongIndex < songResults.length) {
        currentSongIndex++; // Gehe zum nächsten Song
        showNextSong(); // Zeige den nächsten Song an
    }
});

// Funktion: Gelikte Songs anzeigen/aktualisieren
function updateLikedList() {
    const likedList = document.getElementById('likedList');
    likedList.innerHTML = ''; // Leere die aktuelle Liste

    likedSongs.forEach(song => { // Füge jeden gelikten Song zur Liste hinzu
        const li = document.createElement('li');
        li.textContent = `${song.name} - ${song.artists[0].name}`;
        likedList.appendChild(li);
    });
}
