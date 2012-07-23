JMusic Server

Copyright (c) 2012 Jordan Melo.
Licensed under GNU General Public License

Contact: jmelo@uwaterloo.ca

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see &lt;http://www.gnu.org/licenses/>.

---------------------------------------------------------------------------

JMusic Server is a PHP web application that allows you to remotely access
your music collection.

Functionality:
-------------------------

- Drag and drop playlists
- Selection of multiple files using Ctrl and Shift modifier keys
- Built in Flash MP3 player with playback queue
- Can enqueue songs in the player without interrupting playback (hold down
  the Alt key while clicking on a song)
 

Installation:
-------------------------

1) run these queries on your database:
   <pre>
   CREATE TABLE `ms_users` (
     `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
     `username` varchar(20) NOT NULL,
     `password` varchar(32) NOT NULL,
     `email` varchar(48) DEFAULT NULL,
     PRIMARY KEY (`user_id`),
     UNIQUE KEY `username` (`username`)
   ) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;
   
   CREATE TABLE `ms_playlists` (
     `listID` int(12) unsigned NOT NULL AUTO_INCREMENT,
     `name` varchar(64) NOT NULL,
     `owner` varchar(32) NOT NULL,
     `public` tinyint(1) NOT NULL DEFAULT '0',
     `songs` int(12) unsigned NOT NULL DEFAULT '0',
     PRIMARY KEY (`listID`)
   ) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1;
   
   CREATE TABLE `ms_playlist_songs` (
     `songID` int(16) unsigned NOT NULL AUTO_INCREMENT,
     `playlistID` int(20) unsigned NOT NULL,
     `track` int(12) unsigned NOT NULL DEFAULT '1',
     `path` varchar(256) NOT NULL,
     `filename` varchar(128) NOT NULL,
     `title` varchar(256) NOT NULL,
     PRIMARY KEY (`songID`)
   ) ENGINE=InnoDB AUTO_INCREMENT=252 DEFAULT CHARSET=latin1;
   </pre>

2) Change settings in inc/config.php

3) Register an account using register.php


Known Issues and Annoyances:
--------------------------------

- There is currently no way to rename playlists, or even specify a name for them
- Clicking and draging a song in a playlist to the last position results in it being placed in the second last position instead
- Clicking the move up and move down buttons doesn't work as expected
- When streaming a playlist to a media player, there is no ability to seek
- .wav files don't work with the player
- Pausing and unpausing the player advances the playhead by aproximately one second
- The player's equalizer is non functional