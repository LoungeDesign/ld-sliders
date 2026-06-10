/* LD Sliders — Admin JS v1.1.0
   Lounge Design (loungedesign.co.uk) */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {

		/* ── Conditional sub-fields ─────────────── */
		function bindSubField( triggerSel, wrapSel ) {
			var trigger = document.querySelector( triggerSel );
			var wrap    = document.querySelector( wrapSel );
			if ( !trigger || !wrap ) return;
			function update() { wrap.classList.toggle( 'is-visible', trigger.checked ); }
			trigger.addEventListener( 'change', update );
			update();
		}

		bindSubField( '#ld_autoplay',   '#autoplay-speed-wrap' );
		bindSubField( '#ld_groupcells', '#groupcells-count-wrap' );
		bindSubField( '#ld_lazyload',   '#lazyload-count-wrap' );

		// Arrow shape custom field
		var arrowShape = document.querySelector( '#ld_arrowshape' );
		var arrowWrap  = document.querySelector( '#arrowshape-custom-wrap' );
		if ( arrowShape && arrowWrap ) {
			function updateArrow() { arrowWrap.classList.toggle( 'is-visible', arrowShape.value === 'custom' ); }
			arrowShape.addEventListener( 'change', updateArrow );
			updateArrow();
		}

		// Overlay fields
		document.querySelectorAll( '.ld-overlay-trigger' ).forEach( function(trigger) {
			var side   = trigger.id.replace('_toggle','') + '_fields';
			var fields = document.getElementById( side );
			if ( !fields ) return;
			function updateOverlay() { fields.style.display = trigger.checked ? 'block' : 'none'; }
			trigger.addEventListener( 'change', updateOverlay );
			updateOverlay();
		});

		/* ── Copy to clipboard ──────────────────── */
		document.querySelectorAll( '.ld-copyable' ).forEach( function(el) {
			el.addEventListener( 'click', function() {
				var text = el.textContent.trim();
				if ( navigator.clipboard ) {
					navigator.clipboard.writeText( text ).then( function(){ flash(el); } );
				} else {
					var ta = document.createElement('textarea');
					ta.value = text;
					ta.style.cssText = 'position:fixed;left:-9999px';
					document.body.appendChild(ta);
					ta.select();
					try { document.execCommand('copy'); } catch(e){}
					document.body.removeChild(ta);
					flash(el);
				}
			});
		});

		function flash(el) {
			var orig = el.dataset.origText || el.textContent;
			if ( !el.dataset.origText ) el.dataset.origText = orig;
			el.textContent = '✓ Copied!';
			el.classList.add('ld-copied');
			setTimeout(function(){
				el.textContent = orig;
				el.classList.remove('ld-copied');
			}, 1500);
		}

	});
}());
