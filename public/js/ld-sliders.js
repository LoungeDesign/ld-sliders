/**
 * LD Sliders — core carousel engine v1.2.3
 * Original implementation by Lounge Design (loungedesign.co.uk)
 * No third-party dependencies.
 */
( function () {
	'use strict';

	/* ─── Utilities ────────────────────────────────────────── */

	function clamp( val, min, max ) {
		return Math.min( Math.max( val, min ), max );
	}

	function setTransform( el, val ) {
		el.style.transform       = val;
		el.style.webkitTransform = val;
	}

	function setTransition( el, val ) {
		el.style.transition       = val;
		el.style.webkitTransition = val;
	}

	function hexToRgb( hex ) {
		hex = hex.replace( '#', '' );
		if ( hex.length === 3 ) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
		return [
			parseInt( hex.substring(0,2), 16 ),
			parseInt( hex.substring(2,4), 16 ),
			parseInt( hex.substring(4,6), 16 )
		];
	}

	function getGroupCells( config ) {
		var w = window.innerWidth;
		if ( w <= 767  && config._mobileGroupCells ) return config._mobileGroupCells;
		if ( w <= 1024 && config._tabletGroupCells ) return config._tabletGroupCells;
		return config.groupCells || 1;
	}

	/* ─── imagesLoaded ─────────────────────────────────────── */

	function waitForImages( container, cb ) {
		var imgs  = container.querySelectorAll( 'img' );
		var total = imgs.length;
		if ( !total ) { cb(); return; }
		var done  = 0;
		function onLoad() { if ( ++done >= total ) cb(); }
		imgs.forEach( function(img) {
			if ( img.complete ) { onLoad(); }
			else {
				img.addEventListener( 'load',  onLoad );
				img.addEventListener( 'error', onLoad );
			}
		});
	}

	/* ─── LDCarousel ───────────────────────────────────────── */

	function LDCarousel( wrapper, config ) {
		this.wrapper    = wrapper;
		this.config     = config;
		this.track      = null;
		this.cells      = [];
		this.index      = 0;
		this.isDragging = false;
		this.startX     = 0;
		this.startY     = 0;
		this.dragOriginTranslate = 0;
		this.velocity   = 0;
		this.lastX      = 0;
		this.lastTime   = 0;
		this.autoTimer  = null;
		this.isTouch    = false;
		this.btnPrev    = null;
		this.btnNext    = null;
		this.dotsWrap   = null;

		// Store cell offsets from natural DOM positions BEFORE any transform
		// so goTo always calculates from ground truth, not current translate.
		this._cellNaturalOffsets = [];

		this._setup();
	}

	LDCarousel.prototype._setup = function () {
		var self = this;

		if ( this.config.watchCSS ) {
			var after = window.getComputedStyle( this.wrapper, ':after' ).content;
			if ( after !== '"flickity"' && after !== "'flickity'" ) return;
		}

		if ( window.getComputedStyle( this.wrapper ).position === 'static' ) {
			this.wrapper.style.position = 'relative';
		}

		// Overflow
		if ( this.config.overflowVisible ) {
			this.wrapper.style.overflow       = 'visible';
			this.wrapper.style.clipPath       = 'none';
			this.wrapper.style.webkitClipPath = 'none';
			// Force all ancestor elements up to the section to allow overflow
			this._forceParentOverflow();
		} else {
			this.wrapper.style.overflow = 'hidden';
		}

		// Build track
		this.track = this.wrapper.querySelector( '.ld-carousel' );
		if ( !this.track ) {
			this.track = document.createElement( 'div' );
			this.track.className = 'ld-carousel';
			var cells = Array.prototype.slice.call(
				this.wrapper.querySelectorAll( ':scope > .ld-carousel-cell' )
			);
			cells.forEach( function(c) { self.track.appendChild(c); } );
			this.wrapper.insertBefore( this.track, this.wrapper.firstChild );
		}

		this.cells = Array.prototype.slice.call(
			this.track.querySelectorAll( '.ld-carousel-cell' )
		);

		if ( !this.cells.length ) return;

		this._buildNav();
		this._buildOverlays();
		this._bindDrag();
		this._bindKeyboard();
		if ( this.config.resize !== false ) this._bindResize();
		if ( this.config.asNavFor ) this._bindAsNavFor();

		var go = function() {
			// Reset track so we can measure natural offsets cleanly
			setTransition( self.track, 'none' );
			setTransform(  self.track, 'translateX(0px)' );

			// Let the browser paint, then measure and go to initial index
			requestAnimationFrame( function() {
				self._measureCells();
				self.goTo( self.config.initialIndex || 0, false );
				if ( self.config.lazyLoad ) self._lazyLoadVisible();
				if ( self.config.autoPlay ) {
					self._startAutoPlay();
					if ( self.config.pauseAutoPlayOnHover ) {
						self.wrapper.addEventListener( 'mouseenter', self._stopAutoPlay.bind(self) );
						self.wrapper.addEventListener( 'mouseleave', self._startAutoPlay.bind(self) );
					}
				}
			});
		};

		if ( this.config.imagesLoaded ) {
			waitForImages( this.track, go );
		} else {
			go();
		}
	};

	/**
	 * Measure and store each cell's natural left offset from the track.
	 * Called once on init and again on resize.
	 * This is the ground truth — never affected by current translate value.
	 */
	LDCarousel.prototype._measureCells = function () {
		var trackLeft = this.track.getBoundingClientRect().left;
		this._cellNaturalOffsets = this.cells.map( function(cell) {
			return cell.getBoundingClientRect().left - trackLeft;
		});
	};

	/* ─── Nav ──────────────────────────────────────────────── */

	LDCarousel.prototype._buildNav = function () {
		var self = this;

		if ( this.config.prevNextButtons ) {
			this.btnPrev = document.createElement( 'button' );
			this.btnNext = document.createElement( 'button' );
			this.btnPrev.className = 'ld-slider-btn ld-slider-btn--prev';
			this.btnNext.className = 'ld-slider-btn ld-slider-btn--next';
			this.btnPrev.setAttribute( 'aria-label', 'Previous' );
			this.btnNext.setAttribute( 'aria-label', 'Next' );
			this.btnPrev.innerHTML = this._arrowSVG( 'prev' );
			this.btnNext.innerHTML = this._arrowSVG( 'next' );
			this.btnPrev.type = 'button';
			this.btnNext.type = 'button';

			this.btnPrev.addEventListener( 'click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				self.previous();
			});
			this.btnNext.addEventListener( 'click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				self.next();
			});

			this.wrapper.appendChild( this.btnPrev );
			this.wrapper.appendChild( this.btnNext );
		}

		if ( this.config.pageDots ) {
			this.dotsWrap = document.createElement( 'div' );
			this.dotsWrap.className = 'ld-slider-dots';
			if ( this.wrapper.parentNode ) {
				this.wrapper.parentNode.insertBefore( this.dotsWrap, this.wrapper.nextSibling );
			} else {
				this.wrapper.appendChild( this.dotsWrap );
			}
			this._buildDots();
		}
	};

	LDCarousel.prototype._arrowSVG = function ( dir ) {
		if ( this.config.arrowShape && this.config.arrowShape !== 'default' ) {
			return '<svg viewBox="0 0 100 100" fill="currentColor"><path d="' + this.config.arrowShape + '"/></svg>';
		}
		return dir === 'prev'
			? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>'
			: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>';
	};

	LDCarousel.prototype._buildDots = function () {
		var self   = this;
		var groups = this._groupCount();
		this.dotsWrap.innerHTML = '';
		for ( var i = 0; i < groups; i++ ) {
			( function(idx) {
				var dot = document.createElement( 'button' );
				dot.type      = 'button';
				dot.className = 'ld-slider-dot';
				dot.setAttribute( 'aria-label', 'Go to slide ' + (idx+1) );
				dot.addEventListener( 'click', function() { self.goTo(idx); } );
				self.dotsWrap.appendChild( dot );
			})( i );
		}
		this._updateDots();
	};

	/* ─── Overlays ─────────────────────────────────────────── */

	LDCarousel.prototype._buildOverlays = function () {
		var c = this.config;
		if ( c.overlayLeft )  this._buildOverlay( 'left',  c.overlayLeftColor,  c.overlayLeftOpacity,  c.overlayLeftWidth );
		if ( c.overlayRight ) this._buildOverlay( 'right', c.overlayRightColor, c.overlayRightOpacity, c.overlayRightWidth );
	};

	LDCarousel.prototype._buildOverlay = function ( side, color, opacity, width ) {
		var rgb    = hexToRgb( color || '#ffffff' );
		var a      = ( opacity != null ? opacity : 100 ) / 100;
		var w      = width || 120;
		var solid  = 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + a + ')';
		var clear  = 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',0)';
		var gradient = side === 'left'
			? 'linear-gradient(to right,' + solid + ' 0%,' + clear + ' 100%)'
			: 'linear-gradient(to left,'  + solid + ' 0%,' + clear + ' 100%)';

		var el = document.createElement( 'div' );
		el.className = 'ld-overlay ld-overlay--' + side;
		el.setAttribute( 'aria-hidden', 'true' );
		el.style.cssText = [
			'position:absolute',
			'top:0',
			( side === 'left' ? 'left:0' : 'right:0' ),
			'bottom:0',
			'width:' + w + 'px',
			'pointer-events:none',
			'z-index:5',
			'background:' + gradient
		].join(';');
		this.wrapper.appendChild( el );
	};

	/**
	 * Walk up the DOM and force overflow:visible on Breakdance column/section
	 * wrappers so the slider can bleed past its container edges.
	 * Stops at the body element.
	 */
	LDCarousel.prototype._forceParentOverflow = function () {
		var el     = this.wrapper.parentNode;
		var safety = 0;
		while ( el && el !== document.body && safety < 8 ) {
			var tag = el.tagName ? el.tagName.toLowerCase() : '';
			if ( tag === 'section' || tag === 'main' || tag === 'article' ) break;
			var cls = el.className || '';
			// Only touch Breakdance column/row wrappers — leave everything else alone
			if (
				cls.indexOf('bde-column') !== -1 ||
				cls.indexOf('bde-columns') !== -1 ||
				cls.indexOf('bde-section') !== -1 ||
				cls.indexOf('bde-div') !== -1
			) {
				el.style.overflow       = 'visible';
				el.style.clipPath       = 'none';
				el.style.webkitClipPath = 'none';
			}
			el = el.parentNode;
			safety++;
		}
	};

	/* ─── Positioning ──────────────────────────────────────── */

	LDCarousel.prototype._groupCount = function () {
		var g = getGroupCells( this.config );
		return ( g > 1 ) ? Math.ceil( this.cells.length / g ) : this.cells.length;
	};

	/**
	 * Calculate offset using stored natural positions — never reads current
	 * transform, so prev/next always works regardless of current slide.
	 */
	LDCarousel.prototype._offsetForCell = function ( cellIdx ) {
		if ( !this.cells[ cellIdx ] ) return 0;

		// Use stored natural offset (measured when track was at translateX(0))
		var raw = this._cellNaturalOffsets[ cellIdx ] || 0;

		if ( this.config.cellAlign === 'center' ) {
			raw -= ( this.wrapper.offsetWidth - this.cells[cellIdx].offsetWidth ) / 2;
		} else if ( this.config.cellAlign === 'right' ) {
			raw -= ( this.wrapper.offsetWidth - this.cells[cellIdx].offsetWidth );
		}

		if ( this.config.contain ) {
			var maxScroll = Math.max( 0, this.track.scrollWidth - this.wrapper.offsetWidth );
			raw = clamp( raw, 0, maxScroll );
		}

		return raw;
	};

	LDCarousel.prototype.goTo = function ( idx, animate ) {
		var groups  = this._groupCount();
		var g       = getGroupCells( this.config );

		if ( this.config.wrapAround ) {
			idx = ( ( idx % groups ) + groups ) % groups;
		} else {
			idx = clamp( idx, 0, groups - 1 );
		}

		this.index  = idx;
		var cellIdx = g > 1 ? idx * g : idx;
		var offset  = this._offsetForCell( cellIdx );

		setTransition( this.track, animate === false
			? 'none'
			: 'transform 0.4s cubic-bezier(0.23,1,0.32,1)'
		);
		setTransform( this.track, 'translateX(' + ( -offset ) + 'px)' );

		this._updateDots();
		this._updateButtons();
		this._announce();
		this._lazyLoadVisible();

		if ( this._navTarget ) this._navTarget.goTo( idx );
	};

	LDCarousel.prototype.next = function () {
		this.goTo( this.index + 1 );
	};

	LDCarousel.prototype.previous = function () {
		this.goTo( this.index - 1 );
	};

	LDCarousel.prototype._updateDots = function () {
		if ( !this.dotsWrap ) return;
		var idx = this.index;
		this.dotsWrap.querySelectorAll( '.ld-slider-dot' ).forEach( function(d,i) {
			d.classList.toggle( 'is-selected', i === idx );
		});
	};

	LDCarousel.prototype._updateButtons = function () {
		if ( !this.btnPrev || !this.btnNext ) return;
		if ( this.config.wrapAround ) {
			this.btnPrev.disabled = false;
			this.btnNext.disabled = false;
			return;
		}
		this.btnPrev.disabled = ( this.index <= 0 );
		this.btnNext.disabled = ( this.index >= this._groupCount() - 1 );
	};

	LDCarousel.prototype._announce = function () {
		if ( !this.config.accessibility ) return;
		if ( !this._live ) {
			this._live = document.createElement( 'div' );
			this._live.setAttribute( 'aria-live', 'polite' );
			this._live.setAttribute( 'aria-atomic', 'true' );
			this._live.className = 'ld-sr-only';
			this.wrapper.appendChild( this._live );
		}
		this._live.textContent = 'Slide ' + (this.index+1) + ' of ' + this._groupCount();
	};

	/* ─── Lazy Load ────────────────────────────────────────── */

	LDCarousel.prototype._lazyLoadVisible = function () {
		if ( !this.config.lazyLoad ) return;
		var ahead = typeof this.config.lazyLoad === 'number' ? this.config.lazyLoad : 1;
		var g     = getGroupCells( this.config );
		var start = this.index * g;
		var end   = Math.min( this.cells.length, start + g + ahead );
		for ( var i = start; i < end; i++ ) {
			if ( this.cells[i] ) {
				this.cells[i].querySelectorAll( 'img[data-src]' ).forEach( function(img) {
					if ( img.src !== img.dataset.src ) img.src = img.dataset.src;
				});
			}
		}
	};

	/* ─── asNavFor ─────────────────────────────────────────── */

	LDCarousel.prototype._bindAsNavFor = function () {
		var self = this;
		setTimeout( function() {
			var target = document.querySelector( self.config.asNavFor );
			if ( target && target._ldSlider ) {
				self._navTarget = target._ldSlider;
			}
		}, 200 );
	};

	/* ─── Drag ─────────────────────────────────────────────── */

	LDCarousel.prototype._currentTranslate = function () {
		var m = window.getComputedStyle( this.track ).transform;
		if ( !m || m === 'none' ) return 0;
		var parts = m.match( /matrix.*\((.+)\)/ );
		return parts ? parseFloat( parts[1].split(',')[4] ) : 0;
	};

	LDCarousel.prototype._bindDrag = function () {
		var self = this;
		var el   = this.track;

		function start( x, y, touch ) {
			self.isTouch             = touch;
			self.isDragging          = true;
			self.startX              = x;
			self.startY              = y;
			self.dragOriginTranslate = self._currentTranslate();
			self.lastX               = x;
			self.lastTime            = Date.now();
			self.velocity            = 0;
			setTransition( el, 'none' );
			self._stopAutoPlay();
		}

		function move( x, y ) {
			if ( !self.isDragging ) return;
			var dx  = x - self.startX;
			var dy  = y - self.startY;
			var now = Date.now();
			var dt  = ( now - self.lastTime ) || 1;
			self.velocity = ( x - self.lastX ) / dt;
			self.lastX    = x;
			self.lastTime = now;

			if ( Math.abs(dy) > Math.abs(dx) && Math.abs(dx) < ( self.config.dragThreshold || 3 ) ) {
				self.isDragging = false;
				return;
			}

			var newX    = self.dragOriginTranslate + dx;
			var maxLeft = -( Math.max( 0, self.track.scrollWidth - self.wrapper.offsetWidth ) );
			newX = clamp( newX, maxLeft, 0 );
			setTransform( el, 'translateX(' + newX + 'px)' );
		}

		function end( x ) {
			if ( !self.isDragging ) return;
			self.isDragging = false;
			var dx = x - self.startX;

			if ( self.config.freeScroll ) {
				var dest    = self.dragOriginTranslate + dx + self.velocity * 150;
				var maxLeft = -( Math.max( 0, self.track.scrollWidth - self.wrapper.offsetWidth ) );
				dest = clamp( dest, maxLeft, 0 );
				setTransition( el, 'transform 0.5s cubic-bezier(0.23,1,0.32,1)' );
				setTransform(  el, 'translateX(' + dest + 'px)' );
				return;
			}

			if ( Math.abs(dx) > ( self.config.dragThreshold || 3 ) || Math.abs(self.velocity) > 0.3 ) {
				dx < 0 ? self.next() : self.previous();
			} else {
				self.goTo( self.index );
			}

			if ( self.config.autoPlay ) self._startAutoPlay();
		}

		el.addEventListener( 'mousedown', function(e) { start( e.clientX, e.clientY, false ); } );
		window.addEventListener( 'mousemove', function(e) { if ( self.isDragging && !self.isTouch ) move( e.clientX, e.clientY ); } );
		window.addEventListener( 'mouseup',   function(e) { if ( !self.isTouch ) end( e.clientX ); } );

		el.addEventListener( 'touchstart', function(e) {
			var t = e.touches[0]; start( t.clientX, t.clientY, true );
		}, { passive: true } );
		el.addEventListener( 'touchmove', function(e) {
			var t = e.touches[0]; move( t.clientX, t.clientY );
		}, { passive: true } );
		el.addEventListener( 'touchend', function(e) {
			var t = e.changedTouches[0]; end( t.clientX );
		} );

		el.addEventListener( 'dragstart', function(e) { e.preventDefault(); } );
	};

	/* ─── Keyboard ─────────────────────────────────────────── */

	LDCarousel.prototype._bindKeyboard = function () {
		var self = this;
		if ( !this.config.accessibility ) return;
		this.wrapper.setAttribute( 'tabindex', '0' );
		this.wrapper.addEventListener( 'keydown', function(e) {
			if ( e.key === 'ArrowLeft'  || e.key === 'ArrowUp'   ) { self.previous(); e.preventDefault(); }
			if ( e.key === 'ArrowRight' || e.key === 'ArrowDown' ) { self.next();     e.preventDefault(); }
		});
	};

	/* ─── Resize ───────────────────────────────────────────── */

	LDCarousel.prototype._bindResize = function () {
		var self = this;
		var t    = null;
		window.addEventListener( 'resize', function() {
			clearTimeout(t);
			t = setTimeout( function() {
				// Remeasure cells from scratch on resize
				setTransition( self.track, 'none' );
				setTransform(  self.track, 'translateX(0px)' );
				requestAnimationFrame( function() {
					self._measureCells();
					self.goTo( self.index, false );
					if ( self.dotsWrap ) self._buildDots();
				});
			}, 150 );
		});
	};

	/* ─── AutoPlay ─────────────────────────────────────────── */

	LDCarousel.prototype._startAutoPlay = function () {
		var self  = this;
		var delay = typeof this.config.autoPlay === 'number' ? this.config.autoPlay : 3000;
		this._stopAutoPlay();
		this.autoTimer = setInterval( function() { self.next(); }, delay );
	};

	LDCarousel.prototype._stopAutoPlay = function () {
		clearInterval( this.autoTimer );
		this.autoTimer = null;
	};

	/* ─── Boot ─────────────────────────────────────────────── */

	function init() {
		var configs = window.LDSliderConfigs || {};
		Object.keys( configs ).forEach( function(key) {
			document.querySelectorAll( '.' + key ).forEach( function(wrapper) {
				if ( !wrapper._ldSlider ) {
					wrapper.classList.add( 'ld-slider-wrapper' );
					wrapper._ldSlider = new LDCarousel( wrapper, configs[key] );
				}
			});
		});
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	window.LDSliders = { init: init, LDCarousel: LDCarousel };

}());
