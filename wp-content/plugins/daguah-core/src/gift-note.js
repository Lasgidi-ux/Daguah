/**
 * Send Home gift note: write it, record it, keep it on this device.
 *
 * The note and the recording are saved in the browser until checkout
 * attaches them to the order. Saving can fail (private windows, full or
 * blocked storage); the page says so and keeps working.
 */
import { __, sprintf } from '@wordpress/i18n';

const STORE = 'daguah-gift-note';

function load() {
	try {
		return JSON.parse( window.localStorage.getItem( STORE ) || 'null' );
	} catch ( error ) {
		return null;
	}
}

function save( note ) {
	try {
		window.localStorage.setItem( STORE, JSON.stringify( note ) );
		return true;
	} catch ( error ) {
		return false;
	}
}

function forget() {
	try {
		window.localStorage.removeItem( STORE );
	} catch ( error ) {
		// Nothing was kept.
	}
}

const clock = ( seconds ) => `${ Math.floor( seconds / 60 ) }:${ String( seconds % 60 ).padStart( 2, '0' ) }`;

function blobToDataUrl( blob ) {
	return new Promise( ( resolve, reject ) => {
		const reader = new window.FileReader();
		reader.onload = () => resolve( reader.result );
		reader.onerror = reject;
		reader.readAsDataURL( blob );
	} );
}

function setup( root ) {
	const form = root.querySelector( '.daguah-note__fields' );

	if ( ! form ) {
		return;
	}

	form.hidden = false;

	const limit = Number( root.getAttribute( 'data-limit' ) ) || 300;
	const maxSeconds = Number( root.getAttribute( 'data-seconds' ) ) || 60;
	const fields = {
		to: form.elements.to,
		message: form.elements.message,
		from: form.elements.from,
	};
	const count = root.querySelector( '#daguah-note-count' );
	const status = root.querySelector( '.daguah-note__status' );
	const voiceLine = root.querySelector( '.daguah-note__voice' );

	const recorderBox = root.querySelector( '.daguah-recorder' );
	const recordButton = root.querySelector( '.daguah-recorder__record' );
	const stopButton = root.querySelector( '.daguah-recorder__stop' );
	const deleteButton = root.querySelector( '.daguah-recorder__delete' );
	const time = root.querySelector( '.daguah-recorder__time' );
	const audio = root.querySelector( '.daguah-recorder__audio' );
	const recorderMessage = root.querySelector( '.daguah-recorder__message' );

	let voice = '';
	let recorder = null;
	let stream = null;
	let ticker = null;

	const preview = () => {
		root.querySelectorAll( '[data-preview]' ).forEach( ( node ) => {
			const value = fields[ node.getAttribute( 'data-preview' ) ].value.trim();
			const prefix = node.getAttribute( 'data-prefix' );

			if ( value ) {
				node.textContent = prefix ? `${ prefix } ${ value }` : value;
			} else {
				node.textContent = node.getAttribute( 'data-empty' );
			}
		} );

		/* translators: %d: characters left */
		count.textContent = sprintf( __( '%d characters left', 'daguah' ), limit - fields.message.value.length );

		voiceLine.hidden = ! voice;
	};

	const showRecording = () => {
		audio.hidden = ! voice;
		deleteButton.hidden = ! voice;
		recordButton.hidden = !! voice || ! recordButton.dataset.ready;

		if ( voice ) {
			audio.src = voice;
		} else {
			audio.removeAttribute( 'src' );
			time.textContent = clock( 0 );
		}

		recorderBox.setAttribute( 'data-state', voice ? 'recorded' : 'idle' );
		preview();
	};

	const stopStream = () => {
		if ( stream ) {
			stream.getTracks().forEach( ( track ) => track.stop() );
			stream = null;
		}
		window.clearInterval( ticker );
	};

	// Bring back a saved note.
	const saved = load();
	if ( saved ) {
		fields.to.value = saved.to || '';
		fields.message.value = saved.message || '';
		fields.from.value = saved.from || '';
		voice = typeof saved.voice === 'string' && saved.voice.startsWith( 'data:audio/' ) ? saved.voice : '';
		status.textContent = __( 'Your saved note is back where you left it.', 'daguah' );
	}

	Object.values( fields ).forEach( ( field ) => field.addEventListener( 'input', preview ) );

	const canRecord = window.MediaRecorder && navigator.mediaDevices && navigator.mediaDevices.getUserMedia;

	if ( ! canRecord ) {
		recorderMessage.textContent = __( 'This browser cannot record audio. Try a recent Chrome, Safari, Edge or Firefox to add your voice.', 'daguah' );
	} else {
		recordButton.dataset.ready = '1';

		recordButton.addEventListener( 'click', async () => {
			recorderMessage.textContent = '';

			try {
				stream = await navigator.mediaDevices.getUserMedia( { audio: true } );
			} catch ( error ) {
				recorderMessage.textContent = __( 'The microphone is blocked. Allow it from the browser address bar, then press Record again.', 'daguah' );
				return;
			}

			const chunks = [];
			const started = Date.now();

			recorder = new window.MediaRecorder( stream );
			recorder.addEventListener( 'dataavailable', ( event ) => {
				if ( event.data.size ) {
					chunks.push( event.data );
				}
			} );
			recorder.addEventListener( 'stop', async () => {
				stopStream();
				stopButton.hidden = true;

				const blob = new window.Blob( chunks, { type: recorder.mimeType || 'audio/webm' } );
				try {
					voice = await blobToDataUrl( blob );
				} catch ( error ) {
					voice = '';
				}
				showRecording();
				( voice ? deleteButton : recordButton ).focus();
			} );

			recorder.start();
			recordButton.hidden = true;
			stopButton.hidden = false;
			stopButton.focus();
			recorderBox.setAttribute( 'data-state', 'recording' );
			recorderMessage.textContent = __( 'Recording. Press Stop when you are done.', 'daguah' );

			ticker = window.setInterval( () => {
				const seconds = Math.floor( ( Date.now() - started ) / 1000 );
				time.textContent = clock( Math.min( seconds, maxSeconds ) );

				if ( seconds >= maxSeconds && recorder.state === 'recording' ) {
					recorder.stop();
					recorderMessage.textContent = __( 'That is the full minute. Listen back below.', 'daguah' );
				}
			}, 250 );
		} );

		stopButton.addEventListener( 'click', () => {
			if ( recorder && recorder.state === 'recording' ) {
				recorder.stop();
				recorderMessage.textContent = '';
			}
		} );
	}

	deleteButton.addEventListener( 'click', () => {
		voice = '';
		showRecording();
		recordButton.focus();
	} );

	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();

		const note = {
			to: fields.to.value.trim(),
			message: fields.message.value.trim().slice( 0, limit ),
			from: fields.from.value.trim(),
			voice,
			saved: new Date().toISOString(),
		};

		if ( ! note.message && ! note.voice ) {
			status.textContent = __( 'Write a note or record a message first.', 'daguah' );
			fields.message.focus();
			return;
		}

		status.textContent = save( note )
			? __( 'Kept on this device. Checkout will attach it to your order.', 'daguah' )
			: __( 'This browser would not keep the note, perhaps because of a private window. Copy your words somewhere safe for now.', 'daguah' );
	} );

	root.querySelector( '.daguah-note__clear' ).addEventListener( 'click', () => {
		forget();
		form.reset();
		voice = '';
		showRecording();
		status.textContent = __( 'Cleared. Start your note again whenever you are ready.', 'daguah' );
		fields.to.focus();
	} );

	window.addEventListener( 'pagehide', stopStream );

	showRecording();
}

function init() {
	document.querySelectorAll( '.wp-block-daguah-gift-note' ).forEach( setup );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
