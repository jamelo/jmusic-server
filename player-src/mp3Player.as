/*

JMusic Player
A Flash MP3 player with a dynamic song queue.
Copyright (C) 2012 Jordan Melo.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

import fl.controls.ProgressBar;
import fl.controls.ProgressBarMode;
import fl.events.*;
import AdvSound.*;
import flash.media.SoundMixer;
//import mp3PlayerPackage.*;

var flashParams:Object = LoaderInfo(this.root.loaderInfo).parameters;
var loader:URLLoader
var xml:XML;
var songs:Array;

//Add callback function to handle messages sent from javascript
ExternalInterface.addCallback("sendCommand", commandReceived);

//Setup sound object
var slc:SoundLoaderContext = new SoundLoaderContext(5000);
var snd:AdvancedSound;
//var sc:SoundChannel;
var sndTrans:SoundTransform = new SoundTransform(1.0, 0.0);

//Initialize player state variables
var currentSong:int = 0;
var pausedPoint:Number = 0;
var shuffleState:String = "off";
var playState:String = "stopped";
var repeatState:String = "off";
var shuffleOrder:Array;
var volumeMuted:Boolean = false;
var eqOn:Boolean = false;

songProg.mode = ProgressBarMode.MANUAL;

//Register event listeners
addEventListener(Event.ENTER_FRAME, update);
addEventListener(KeyboardEvent.KEY_UP, this_keyUp);
btnPlayPause.addEventListener(MouseEvent.CLICK, btnPlayPause_click);
btnStop.addEventListener(MouseEvent.CLICK, btnStop_click);
btnPrev.addEventListener(MouseEvent.CLICK, btnPrev_click);
btnNext.addEventListener(MouseEvent.CLICK, btnNext_click);
btnRepeat.addEventListener(MouseEvent.CLICK, btnRepeat_click);
btnShuffle.addEventListener(MouseEvent.CLICK, btnShuffle_click);
playlistBox.addEventListener(ListEvent.ITEM_DOUBLE_CLICK, playlistItem_dblClick);
playlistBox.addEventListener(KeyboardEvent.KEY_DOWN, playlist_keyUp);
songProg.addEventListener(MouseEvent.CLICK, songProg_click);
volumeControl.addEventListener("volumeChanged", volumeControl_volumeChanged);
btnMute.addEventListener(MouseEvent.CLICK, btnMute_click);
btnEq.addEventListener(MouseEvent.CLICK, btnEq_click);


/*
//Initialize equalizer
var eqSliders:Array = new Array(eq1, eq2, eq3, eq4, eq5, eq6, eq7, eq8, eq9, eq10);
var eqLeft:EQ = new EQ();
var eqRight:EQ = new EQ();

eqPreamp.addEventListener("valueChanged", preGainChanged);

var count:int = 0;

//Add event listeners to each EQ slider
for each (var eqi in eqSliders)
{
	eqi.setBand(count);
	eqi.addEventListener("valueChanged", eqChanged);
	count++;
}

//Handlers equalizer slider adjustments
function eqChanged(e:Event):void
{
	eqLeft.setGain(e.target.getBand(), e.target.getValue() * 48 - 24);
	eqRight.setGain(e.target.getBand(), e.target.getValue() * 48 - 24);
}
function preGainChanged(e:Event):void
{
	eqLeft.setPreGain(e.target.getValue() * 48 - 24);
	eqRight.setPreGain(e.target.getValue() * 48 - 24);
}*/

var currentSample:int = 0; //Play position in sound samples

if (flashParams["vsng"] != "")
{
	//Specified song is to be played
	addSongToPlaylist(flashParams["vsng"]);
}
else if (flashParams["vpls"] != "")
{
	//specified playlist is to be played. Load its xml
	loader = new URLLoader();
	loader.addEventListener(Event.COMPLETE, xmlLoadComplete);
	loader.load(new URLRequest(flashParams["vpls"]));
}
else
{
	//Default to loading songs from playlist.xml for debugging purposes
	loader = new URLLoader();
	loader.addEventListener(Event.COMPLETE, xmlLoadComplete);
	loader.load(new URLRequest("playlist.xml"));
}

//Playlist XML loaded event handler
function xmlLoadComplete(e:Event):void
{
	e.target.data;
	xml = new XML(e.target.data);
	var songList:XMLList = xml.song;
	var formerSongCount:int = playlistBox.length;
	
	songs = new Array(songList.length());
	
	for (var i:int = 0; i < songList.length(); i++)
	{
		songs[i] = songList.text()[i];
		addSongToPlaylist(songs[i]);
	}
	
	playlistBox.selectedIndex = formerSongCount;
	playlistBox.scrollToIndex(formerSongCount);
}

//Song ended event handler
function soundCompleteEventHandler(event:Event) : void
{
	if (repeatState == "off")
	{
		currentSong++;
	}
	else if (repeatState == "playlist")
	{
		currentSong++;

		if (currentSong >= playlistBox.length)
		{
			currentSong = 0;
		}
	}

	if (currentSong >= playlistBox.length)
	{
		stopPlayback();
		return;
	}
	
	playSong(currentSong);
}


function playSong(songNumber:int) : void
{
	if (shuffleState == "on")
	{
		songNumber = shuffleOrder[songNumber];
	}

	if (snd != null)
	{
		try { snd.stop(); }
		catch (error) { }
	}

	loadSong(songNumber);
	songProg.indeterminate = false;
	songProg.source = snd;

	try
	{
		snd.play(0, 0, sndTrans);
		//snd.soundTransform = sndTrans;
		snd.addEventListener("soundDonePlaying", soundCompleteEventHandler);
		//snd.setEq(eqLeft, eqRight, eqOn);
		playState = "playing";
	}
	catch (error)
	{
		trace(error);
		songProg.indeterminate = true;
		songProg.source = null;
	}
	
}

function loadSong(songNumber:int)
{
	if (snd == null || snd.url != playlistBox.getItemAt(songNumber).data)
	{
		if (snd != null)
		{
			try { snd.close(); }
			catch (error) { }
		}
	
		snd = new AdvancedSound();
		//snd.setEq(eqLeft, eqRight, eqOn);
		snd.addEventListener(IOErrorEvent.IO_ERROR, IOErrorEventListener);
		snd.addEventListener(Event.OPEN, songLoadSuccess);
		progBar.source = snd;
	
		snd.load(new URLRequest(playlistBox.getItemAt(songNumber).data), slc);
	
		songProg.maximum = snd.length;
		playlistBox.selectedIndex = songNumber;
		playlistBox.scrollToIndex(songNumber);
		setTickerText(playlistBox.getItemAt(songNumber).label);
	}
}

//Static vars for retrying failed loadSong attempts
var loadFailures:int = 0;
var tryAgainInterval:uint;

//Event handler for failed loadSong attempts
function IOErrorEventListener(e:IOErrorEvent):void
{
	loadFailures++;
	
	if (loadFailures >= 4)
	{
		loadFailures = 0;
		currentSong++;
		
		if (playState != "stopped")
		{
			if (currentSong >= playlistBox.length)
			{
				if (repeatState != "playlist")
				{
					currentSong--;
				}
				else
				{
					currentSong = 0;
					playSong(currentSong);
				}
			}
			else
			{
				playSong(currentSong);
			}
		}
	}
	else
	{
		progBar.source = null;
		songProg.indeterminate = true;
		songProg.source = null;
		//Wait 500 ms then try loading song again
		tryAgainInterval = setInterval(tryLoadAgain, 500);
	}
}

function tryLoadAgain():void
{
	clearInterval(tryAgainInterval);
	
	if (playState == "stopped")
	{
		
		if (shuffleState == "on")
		{
			loadSong(shuffleOrder[currentSong]);
		}
		else
		{
			loadSong(currentSong);
		}
	}
	else
	{
		playSong(currentSong);
	}
}

function songLoadSuccess(e:Event):void
{
	if (snd.bytesLoaded > 0)
	{
		loadFailures = 0;
	}
}


//Update screen on every frame
function update(event:Event) : void
{
	if (playState == "playing")
	{
		if (snd != null)
		{
			if (snd.length > 0)
			{
				songProg.indeterminate = false;
			}
			
			songProg.maximum = snd.length * (snd.bytesTotal / snd.bytesLoaded);
			songProg.setProgress(snd.position, songProg.maximum);
			txtPosition.text = makeTimeString(snd.position, false);
			txtLength.text = makeTimeString(songProg.maximum, false);
		}

		btnPlayPause.gotoAndStop("pauseframe");
	}
	else if (playState == "stopped")
	{
		btnPlayPause.gotoAndStop("playframe");
		songProg.setProgress(0, songProg.maximum);
		txtPosition.text = "0:00";
	}
	else if (playState == "paused")
	{
		txtPosition.text = makeTimeString(pausedPoint, false);
		btnPlayPause.gotoAndStop("playframe");
		songProg.setProgress(pausedPoint, songProg.maximum);
	}

	if (shuffleState == "off")
	{
		btnShuffle.gotoAndStop("shuffle_off_frame");
	}
	else
	{
		btnShuffle.gotoAndStop("shuffle_on_frame");
	}

	if (repeatState == "song")
	{
		btnRepeat.gotoAndStop("repeat_song_frame");
	}
	else if (repeatState == "playlist")
	{
		btnRepeat.gotoAndStop("repeat_playlist_frame");
	}
	else
	{
		btnRepeat.gotoAndStop("repeat_off_frame");
	}
}

function commandReceived(command:String) : void
{
	var args:Array = command.split(" ");

	switch (args[0])
	{
		case "load":
			playlistBox.removeAll();
			//Fall through
		case "enqueue":
			if (args[1] == "song")
			{
				addSongToPlaylist(args[2]);
			}
			else if (args[1] == "playlist") loadPlaylist(args[2]);
			else trace("Unknown object type \"" + args[1] + "\".");
			break;
		default:
			trace("The command \"" + args[0] + "\" does not exist.");
	}
}

function loadPlaylist(path:String) : void
{
	loader = new URLLoader();
	loader.addEventListener(Event.COMPLETE, xmlLoadComplete);
	loader.load(new URLRequest(path));
}

/* * * * * * * * * * * * * * * * * * * * 
 *				Song Ticker			   *
 * * * * * * * * * * * * * * * * * * * */

var tickerSize:int = 50;
var tickerPos:int = 0;
var intervalId:Number;
var tickerText:String = "";

function setTickerText(t:String):void
{
	if (t != tickerText)
	{
		tickerText = t;
		tickerPos = 0;
		clearInterval(intervalId);
		intervalId = setInterval(tickerScroll, 300);
		ticker.text = tickerText.slice(0, tickerSize);
	}
}

function tickerScroll():void
{
	if (tickerText.length > tickerSize)
	{
		var temp:String = tickerText + "  --  ";
		
		tickerPos ++;
		
		if (tickerPos >= temp.length)
		{
			tickerPos = 0;
		}
		
		var textOut:String = temp.slice(tickerPos, tickerPos + tickerSize);

		if (textOut.length > tickerSize)
		{
			textOut.slice(0, tickerSize);
		}
		else if (textOut.length < tickerSize)
		{
			textOut += tickerText.slice(0, tickerSize - textOut.length);
		}
		
		ticker.text = textOut;
	}
}

/* * * * * * * * * * * * * * * * * * * * 
 *			  Play Functions		   *
 * * * * * * * * * * * * * * * * * * * */

function pausePlayback() : void
{
	pausedPoint = snd.position;
	
	try { snd.stop(); }
	catch (error) { }

	playState = "paused";
}

function resumePlayback() : void
{
	snd.play(pausedPoint, 0, sndTrans);
	snd.addEventListener("soundDonePlaying", soundCompleteEventHandler);
	playState = "playing";
}

function stopPlayback() : void
{
	try { snd.stop(); }
	catch (error) { }

	songProg.setProgress(0, songProg.maximum);
	txtPosition.text = "0:00";
	playState = "stopped";
}

/* * * * * * * * * * * * * * * * * * * * 
 *			 Button Handlers		   *
 * * * * * * * * * * * * * * * * * * * */

function btnPlayPause_click(event:MouseEvent) : void
{
	if (playState == "stopped")
	{
 		playSong(currentSong);
	}
	else if (playState == "paused")
	{
		resumePlayback();
	}
	else if (playState == "playing")
	{
		pausePlayback();
	}
}

function btnStop_click(event:MouseEvent) : void
{
	stopPlayback();
}

function btnPrev_click(event:MouseEvent) : void
{
	if (currentSong > 0 || repeatState == "playlist")
	{
		currentSong--;
		
		if (currentSong < 0)
		{
			currentSong += playlistBox.length;
		}

		if (playState == "playing" || playState == "paused")
		{
			playSong(currentSong);
		}
		else
		{
			if (shuffleState == "on")
			{
				loadSong(shuffleOrder[currentSong]);
			}
			else
			{
				loadSong(currentSong);
			}
		}
	}
}

function btnNext_click(event:MouseEvent) : void
{
	if (currentSong < (playlistBox.length - 1) || repeatState == "playlist")
	{
		currentSong = ++currentSong % playlistBox.length;

		if (playState == "playing" || playState == "paused")
		{
			playSong(currentSong);
		}
		else
		{
			if (shuffleState == "on")
			{
				loadSong(shuffleOrder[currentSong]);
			}
			else
			{
				loadSong(currentSong);
			}
		}
	}
}

function btnRepeat_click(event:MouseEvent) : void
{
	if (repeatState == "off")
	{
		repeatState = "playlist";
	}
	else if (repeatState == "playlist")
	{
		repeatState = "song";
	}
	else
	{
		repeatState = "off";
	}
}

function btnShuffle_click(event:MouseEvent) : void
{
	if (shuffleState == "off")
	{
		shuffleState = "on";
		shuffleOrder = new Array(playlistBox.length);

		for (var i:int = 0; i < shuffleOrder.length; i++)
		{
					
			shuffleOrder[i] = i;
		}

		shuffleOrder = shuffleArray(shuffleOrder);
		currentSong = 0;
	}
	else
	{
		shuffleState = "off";
		currentSong = shuffleOrder[currentSong];
	}
}

function btnMute_click(e:MouseEvent):void
{
	if (volumeMuted)
	{
		volumeMuted = false;
		
		var base:Number = 10;
		var newVolume:Number = (Math.pow(base, volumeControl.getValue() * 0.01) - 1) / (base - 1);
		sndTrans = new SoundTransform(newVolume, 0.0);
		btnMute.gotoAndStop("mute_off");
	}
	else
	{
		volumeMuted = true;
		sndTrans = new SoundTransform(0.0, 0.0);
		btnMute.gotoAndStop("mute_on");
	}
	
	if (snd != null)
	{
		snd.soundTransform = sndTrans;
	}
}

function btnEq_click(e:MouseEvent):void
{
	//eqOn = !eqOn;
	//snd.setEq(eqLeft, eqRight, eqOn);
	
	if (eqOn)
	{
		btnEq.gotoAndStop("eq_on");
	}
	else
	{
		btnEq.gotoAndStop("eq_off");
	}
}

/* * * * * * * * * * * * * * * * * * * * 
 *			  Other Controls		   *
 * * * * * * * * * * * * * * * * * * * */

function playlistItem_dblClick(event:ListEvent) : void
{
	currentSong = event.index;

	if (shuffleState == "on")
	{
		shuffleOrder = shuffleArray(shuffleOrder);
		currentSong = 0;
	}

	playSong(currentSong);
}

function playlist_keyUp(e:KeyboardEvent):void
{
	if (e.keyCode == Keyboard.ENTER)
	{
		currentSong = playlistBox.selectedIndex;
		
		if (shuffleState == "on")
		{
			shuffleOrder = shuffleArray(shuffleOrder);
			currentSong = 0;
		}
	
		playSong(currentSong);
	}
}

function songProg_click(e:MouseEvent):void
{
	var position:Number = ((e.stageX - songProg.x) / songProg.width) * snd.length * (snd.bytesTotal / snd.bytesLoaded);
	
	if (position <= snd.length)
	{
		if (playState == "playing")
		{
			snd.stop();
			snd.play(position, 0, sndTrans);
			snd.addEventListener("soundDonePlaying", soundCompleteEventHandler);
		}
		else if (playState == "paused")
		{
			pausedPoint = position;
		}
	}
	
	if (snd.bytesLoaded >= snd.bytesTotal && position >= snd.length)
	{
		snd.dispatchEvent(new Event("soundDonePlaying"));
	}
}

function this_keyUp(e:KeyboardEvent):void
{
	if (e.keyCode == Keyboard.SPACE)
	{
		if (playState == "stopped")
		{
			playSong(currentSong);
		}
		else if (playState == "paused")
		{
			resumePlayback();
		}
		else if (playState == "playing")
		{
			pausePlayback();
		}
	}
}

function addSongToPlaylist(url:String):void
{
	var formerSongCount:int = playlistBox.length;
	var listItem:Object = new Object();
	listItem.label = extractSongNameFromUrl(url);
	listItem.data = url;
	playlistBox.addItem(listItem);	
	
	if (formerSongCount <= 0)
	{
		currentSong = 0;
		playSong(0);
	}
}

function volumeControl_volumeChanged(e:Event)
{
	var base:Number = 10;
	var newVolume:Number = (Math.pow(base, volumeControl.getValue() * 0.01) - 1) / (base - 1);
	sndTrans = new SoundTransform(newVolume, 0.0);
	btnMute.gotoAndStop("mute_off");
	volumeMuted = false;
	
	if (snd != null)
	{
		snd.soundTransform = sndTrans;
	}
}

/* * * * * * * * * * * * * * * * * * * * 
 *			  Misc Functions		   *
 * * * * * * * * * * * * * * * * * * * */

function extractSongNameFromUrl(url:String):String
{
	var tstr:String = url;
	
	if (tstr.indexOf("file=") != -1)
	{
		tstr = tstr.slice(tstr.indexOf("file=") + 5, tstr.length-1);
	}

	if (tstr.indexOf("&") != -1)
	{
		tstr = tstr.slice(0, tstr.indexOf("&"));
	}

	while(tstr.indexOf("+") != -1)
	{
		tstr = tstr.replace("+", " ");
	}
	
	tstr = unescape(tstr);
	
	if (tstr.indexOf("/") != -1)
	{
		tstr = tstr.slice(tstr.lastIndexOf("/")+1, tstr.lastIndexOf("."));
	}
	else
	{
		tstr = tstr.slice(0, tstr.lastIndexOf("."));
	}
	
	return tstr;
}

function shuffleArray(array:Array) : Array
{
	var randIdx:Number;
	var temp:Object = null;
	var len:int = array.length;
	var shuffled:Array = array.slice();

	for (var j:int = 0; j < 2; j++)
	{
		for (var i:int = 0; i < len; i++)
		{
			temp = shuffled[i];
			randIdx = Math.floor(Math.random() * len);
			shuffled[i] = shuffled[randIdx];
			shuffled[randIdx] = temp;
		}
	}

	currentSong = shuffled.indexOf(currentSong);
	temp = shuffled[0];
	shuffled[0] = shuffled[currentSong];
	shuffled[currentSong] = temp;
	currentSong = 0;
	
	return shuffled;
}

function makeTimeString(ms:Number, includeMs:Boolean):String
{
	var hours:Number = Math.abs(Math.floor(ms / 3600000));
	ms -= hours * 3600000;
	var minutes:Number = Math.abs(Math.floor(ms / 60000));
	ms -= minutes * 60000;
	var seconds:Number = Math.abs(Math.floor(ms * 0.001));
	ms -= seconds * 1000;
	var milliseconds:Number = Math.abs(Math.round(ms));
	
	var output:String;
	
	if (hours != 0)
	{
		output = hours + ":" + padWithZeros(minutes, 2) + ":" +  padWithZeros(seconds, 2);
	}
	else
	{
		output = minutes + ":" +  padWithZeros(seconds, 2);
	}
	
	if (includeMs)
	{
		output += ":" +  padWithZeros(milliseconds, 3);
	}
	
	return output;
}

function padWithZeros(n:Number, w:Number):String
{
	var output:String = n.toString();
	
	while (output.length < w)
	{
		output = "0" + output;
	}
	
	return output;
}