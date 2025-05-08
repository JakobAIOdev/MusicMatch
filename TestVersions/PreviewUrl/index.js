require('dotenv').config();
const spotifyPreviewFinder = require('spotify-preview-finder');

async function searchSongs() {
  try {
    const songs = [
      await spotifyPreviewFinder('HBA', 1),
      await spotifyPreviewFinder('Sicko Mode', 1),
      await spotifyPreviewFinder('Type Shit', 1)
    ];

    songs.forEach(result => {
      if (result.success && result.results.length > 0) {
        const song = result.results[0];
        console.log(`\nFound: ${song.name}`);
        console.log(`Spotify URL: ${song.spotifyUrl}`);
        console.log(`Preview URL: ${song.previewUrls[0]}`);
      }
    });
  } catch (error) {
    console.error('Error:', error.message);
  }
}

searchSongs();
