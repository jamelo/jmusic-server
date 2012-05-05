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

package AdvSound
{
	import flash.events.Event;
	import flash.events.SampleDataEvent;
	import flash.media.Sound;
	import flash.media.SoundChannel;
	import flash.media.SoundLoaderContext;
	import flash.media.SoundTransform;
	import flash.net.URLRequest;
	import flash.utils.ByteArray;
	
	public class AdvancedSound extends Sound
	{
		private var _outputSound:Sound;
		private var _sampleRate:Number = 44.1;
		private var _state:String = "stopped";
		private var _currentSample:uint = 0;
		private var _bufferTime:Number = 5000;
		private var _soundChannel:SoundChannel;
		//private var _eqL:EQ = null;
		//private var _eqR:EQ = null;
		//private var _eqOn:Boolean = false;
		private var _bufferLength:Number = BUFFER_SIZE / _sampleRate;
		
		private static const BUFFER_SIZE:uint = 8192;
		private static const BUFFER_SIZE_EQ:uint = 8192;
		
		public function AdvancedSound(stream:URLRequest = null, context:SoundLoaderContext = null)
		{
			super(stream, context);
			_outputSound = new Sound();
			_outputSound.addEventListener(SampleDataEvent.SAMPLE_DATA, outputSampleData);
			
			if (context != null)
			{
				_bufferTime = context.bufferTime;
			}
		}
		
		override public function play(startTime:Number = 0, loops:int = 0, sndTransform:SoundTransform = null):SoundChannel
		{
			_state = "buffering";
			_currentSample = startTime * _sampleRate;
			_soundChannel = _outputSound.play();
			_soundChannel.soundTransform = sndTransform;
			_soundChannel.addEventListener(Event.SOUND_COMPLETE, outputSoundComplete);
			this.addEventListener(Event.SOUND_COMPLETE, outputSoundComplete);
			return _soundChannel;
		}
		
		override public function load(stream:URLRequest, context:SoundLoaderContext = null):void
		{
			if (context != null)
			{
				_bufferTime = context.bufferTime;
			}
			
			super.load(stream, context);
		}
		
		public function stop():void
		{
			_state = "stopped";
			_soundChannel.stop();
		}
		
		/*public function setEq(left:EQ, right:EQ, eqon:Boolean)
		{
			_eqL = left;
			_eqR = right;
			_eqOn = _eqL != null && _eqR != null && eqon;
			
			if (_eqOn)
			{
				_bufferLength = BUFFER_SIZE_EQ / _sampleRate;
			}
			else
			{
				_bufferLength = BUFFER_SIZE / _sampleRate;
			}
		}*/
		
		public function get soundTransform():SoundTransform { return _soundChannel.soundTransform; }
		public function set soundTransform(val:SoundTransform):void { _soundChannel.soundTransform = val; }
		public function get position():Number { return _currentSample / _sampleRate; }
		
		private function outputSampleData(e:SampleDataEvent)
		{
			if (_state == "playing" && this.position + _bufferLength > this.length && this.bytesLoaded < this.bytesTotal)
			{
				_state = "buffering";
			}
			
			if (_state == "buffering")
			{
				if (this.length > this.position + _bufferTime || this.bytesLoaded == this.bytesTotal && this.bytesTotal != 0)
				{
					_state = "playing";
				}
				else
				{
					for (var i:uint = 0; i < BUFFER_SIZE; i++)
					{
						e.data.writeFloat(0);
						e.data.writeFloat(0);
					}
				}
			}
			
			if (_state == "playing")
			{
				var sampleArray:ByteArray = new ByteArray();
				
				/*if (_eqOn)
				{
					this.extract(sampleArray, BUFFER_SIZE_EQ, _currentSample);
					sampleArray.position = 0;
					
					while (sampleArray.bytesAvailable)
					{
						_currentSample++;
						e.data.writeFloat(_eqL.compute(sampleArray.readFloat()));
						e.data.writeFloat(_eqR.compute(sampleArray.readFloat()));
					}
				}
				else
				{*/
					this.extract(sampleArray, BUFFER_SIZE, _currentSample);
					sampleArray.position = 0;
					
					while (sampleArray.bytesAvailable)
					{
						_currentSample++;
						e.data.writeFloat(sampleArray.readFloat());
						e.data.writeFloat(sampleArray.readFloat());
					}
				//}
			}
		}
		
		private function outputSoundComplete(e:Event)
		{
			if (this.bytesLoaded < this.bytesTotal)
			{
				//Just in case
				this.play();
			}
			else
			{
				_state = "stopped";
				this.dispatchEvent(new Event("soundDonePlaying"));
			}
		}
		
		public function computeSpectrum():Object
		{
			var res:int = 512;
			var bytes:ByteArray = new ByteArray();
			var leftr:Array;
			var rightr:Array;
			var samples:int;
			
			samples = this.extract(bytes, res, Math.max(0, Math.min(this.length * _sampleRate - res, _currentSample)));
			bytes.position = 0;
			leftr = new Array(samples);
			rightr = new Array(samples);
			
			var x:int = 0;
			
			while (bytes.bytesAvailable)
			{
				leftr[x] = bytes.readFloat();
				rightr[x] = bytes.readFloat();
				x++;
			}
			
			//Begin Fast Forier Transform
			
			var tr:Number;
			var ti:Number;
			var j:Number = samples / 2;
			var k:Number;
			var m:int = Math.log(samples) / Math.LN2;
			
			for (var i:int = 1; i < samples - 1; i++)
			{
				if (i < j)
				{
					tr = leftr[j];
					leftr[j] = leftr[i];
					leftr [i] = tr;
					tr = rightr[j];
					rightr[j] = rightr[i];
					rightr[i] = tr;
				}
				
				k = res * 0.5;
				
				while (k <= j)
				{
					j -= k;
					k *= 0.5;
				}
				
				j += k;
			}
			
			var le:Number;
			var le2:Number;
			var uil:Number, url:Number, uir:Number, urr:Number;
			var sr:Number, si:Number;
			var ip:Number;
			var trl:Number, til:Number, trr:Number, tir:Number;
			var righti = new Array(samples);
			var lefti = new Array(samples);
			
			for (var l:int = 1; l <= m; l++)
			{
				le = Math.pow(2, l);
				le2 = le * 0.5;
				url = 1;
				uil = 0;
				urr = 1;
				uir = 0;
				
				sr = Math.cos(Math.PI / le2);
				si = -Math.sin(Math.PI / le2);
				
				for (j = 1; j <= le2; j++)
				{
					for (var i = j - 1; j < samples; j+= le)
					{
						ip = i + le2;
						
						trl = leftr[ip] * url - lefti[ip] * uil;
						til = leftr[ip] * uil + lefti[ip] * url;
						
						trr = rightr[ip] * urr - righti[ip] * uir;
						tir = rightr[ip] * uir + righti[ip] * urr;
						
						leftr[ip] = leftr[i] - trl;
						lefti[ip] = lefti[i] - til;
						leftr[i] = leftr[i] + trl;
						lefti[i] = lefti[i] + til;
						
						rightr[ip] = rightr[i] - trr;
						righti[ip] = righti[i] - tir;
						rightr[i] = rightr[i] + trr;
						righti[i] = righti[i] + tir;
					}
					
					trl = url;
					url = trl * sr - uil * si;
					uil = trl * si + uil * sr;
					
					trr = urr;
					urr = trr * sr - uir * si;
					uir = trr * si + uir * sr;
				}
			}
			
			return { left: leftr, right: rightr };
		}
	}
}