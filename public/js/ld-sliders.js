/**
 * LD Sliders — core carousel engine v1.1.0
 * Original implementation by Lounge Design (loungedesign.co.uk)
 * No third-party dependencies.
 */
( function () {
	'use strict';

	/* ─── Utility ──────────────────────────────────────────── */

	function clamp( val, min, max ) {
		return Math.min( Math.max( val, min ), max );
	}

	function prefixCSS( el, prop, val ) {
		var cap = prop.charAt(0).toUpperCase() + prop.slice(1);
		el.style[ prop ] = val;
		el.style[ 'webkit' + cap ] = val;
		el.style[ 'ms' + cap ] = val;
	}

	function hexToRgb( hex ) {
		hex = hex.replace('#','');
		if ( hex.length === 3 ) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
		return [
			parseInt(hex.substring(0,2),16),
			parseInt(hex.substring(2,4),16),
			parseInt(hex.substring(4,6),16)
		];
	}

	function getResponsiveGroupCells( config ) {
		var w = window.innerWidth;
		if ( w <= 767 && config._mobileGroupCells ) return config._mobileGroupCells;
		if ( w <= 1024 && config._tabletGroupCells ) return config._tabletGroupCells;
		return config.groupCells || 1;
	}

	/* ─── imagesLoaded helper ──────────────────────────────── */

	function imagesLoaded( container, callback ) {
		var imgs   = container.querySelectorAll( 'img' );
		var total  = imgs.length;
		if ( total === 0 ) { callback(); return; }
		var loaded = 0;
		function onLoad() {
			loaded++;
			if ( loaded >= total ) callback();
		}
		imgs.forEach( function(img) {
			if ( img.complete ) {
				onLoad();
			} else {
				img.addEventListener( 'load',  onLoad );
				img.addEventListener( 'error', onLoad );
			}
		});
	}

	/* ─── LDCarousel ───────────────────────────────────────── */

	function LDCarousel( wrapper ) {
		this.wrapper    = wrapper;
		this.track      = wrapper.querySelector( '.ld-carousel' );
		this.config     = {};
		this.cells      = [];
		this.index      = 0;
		this.isDragging = false;
		this.startX     = 0;
		this.startY     = 0;
		this.currentX   = 0;
		this.velocity   = 0;
		this.lastX      = 0;
		this.lastTime   = 0;
		this.autoTimer  = null;
		this.isTouch    = false;

		this._init();
	}

	LDCarousel.prototype._init = function () {
		var self = this;
		var raw  = this.wrapper.getAttribute( 'data-settings' );
		try { this.config = JSON.parse( raw ) || {}; } catch(e) { this.config = {}; }

		// watchCSS — read :after content to decide if enabled
		if ( this.config.watchCSS ) {
			var afterContent = window.getComputedStyle( this.wrapper, ':after' ).content;
			if ( afterContent !== '"flickity"' && afterContent !== "'flickity'" ) {
				return; // disabled by CSS
			}
		}

		this._buildCells();
		this._buildNav();
		this._bindDrag();
		this._bindKeyboard();
		if ( this.config.resize !== false ) {
			this._bindResize();
		}

		// asNavFor — sync to another slider
		if ( this.config.asNavFor ) {
			this._bindAsNavFor();
		}

		// imagesLoaded — wait before positioning
		if ( this.config.imagesLoaded ) {
			imagesLoaded( this.track, function() {
				self.goTo( self.config.initialIndex || 0, false );
			});
		} else {
			this.goTo( this.config.initialIndex || 0, false );
		}

		// lazyLoad
		if ( this.config.lazyLoad ) {
			this._initLazyLoad();
		}

		if ( this.config.autoPlay ) {
			this._startAutoPlay();
			if ( this.config.pauseAutoPlayOnHover ) {
				this.wrapper.addEventListener( 'mouseenter', this._stopAutoPlay.bind(this) );
				this.wrapper.addEventListener( 'mouseleave', this._startAutoPlay.bind(this) );
			}
		}
	};

	LDCarousel.prototype._buildCells = function () {
		this.cells = Array.prototype.slice.call(
			this.track.querySelectorAll( '.ld-carousel-cell' )
		);
	};

	/* ─── Nav ──────────────────────────────────────────────── */

	LDCarousel.prototype._buildNav = function () {
		var self = this;

		if ( this.config.prevNextButtons ) {
			this.btnPrev = document.createElement('button');
			this.btnNext = document.createElement('button');
			this.btnPrev.className = 'ld-slider-btn ld-slider-btn--prev';
			this.btnNext.className = 'ld-slider-btn ld-slider-btn--next';
			this.btnPrev.setAttribute('aria-label','Previous');
			this.btnNext.setAttribute('aria-label','Next');

			var prevSVG = this._arrowSVG('prev');
			var nextSVG = this._arrowSVG('next');
			this.btnPrev.innerHTML = prevSVG;
			this.btnNext.innerHTML = nextSVG;

			this.btnPrev.addEventListener('click', function(){ self.previous(); });
			this.btnNext.addEventListener('click', function(){ self.next(); });
			this.wrapper.appendChild(this.btnPrev);
			this.wrapper.appendChild(this.btnNext);
		}

		if ( this.config.pageDots ) {
			this.dotsWrapper = document.createElement('div');
			this.dotsWrapper.className = 'ld-slider-dots';
			this.wrapper.appendChild(this.dotsWrapper);
			this._buildDots();
		}
	};

	LDCarousel.prototype._arrowSVG = function( dir ) {
		// Custom arrowShape path support
		if ( this.config.arrowShape && this.config.arrowShape !== 'default' ) {
			return '<svg viewBox="0 0 100 100"><path d="' + this.config.arrowShape + '"/></svg>';
		}
		if ( dir === 'prev' ) {
			return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>';
		}
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>';
	};

	LDCarousel.prototype._buildDots = function () {
		var self = this;
		if ( !this.dotsWrapper ) return;
		this.dotsWrapper.innerHTML = '';
		var groups = this._groupCount();
		for ( var i = 0; i < groups; i++ ) {
			(function(idx) {
				var dot = document.createElement('button');
				dot.className = 'ld-slider-dot';
				dot.setAttribute('aria-label', 'Go to slide ' + (idx+1));
				dot.addEventListener('click', function(){ self.goTo(idx); });
				self.dotsWrapper.appendChild(dot);
			})(i);
		}
		this._updateDots();
	};

	LDCarousel.prototype._groupCount = function () {
		var g = getResponsiveGroupCells(this.config);
		if (!g || g <= 1) return this.cells.length;
		return Math.ceil(this.cells.length / g);
	};

	/* ─── Positioning ──────────────────────────────────────── */

	LDCarousel.prototype._cellOffset = function ( idx ) {
		if ( !this.cells[idx] ) return 0;
		var cell      = this.cells[idx];
		var trackRect = this.track.getBoundingClientRect();
		var cellRect  = cell.getBoundingClientRect();

		var raw;
		if ( this.config.percentPosition ) {
			raw = ( (cellRect.left - trackRect.left) / this.wrapper.offsetWidth ) * 100;
			// percent mode — return percentage
			if ( this.config.cellAlign === 'center' ) {
				raw -= ( (cell.offsetWidth / this.wrapper.offsetWidth) * 50 );
			}
			return raw; // caller handles unit
		}

		raw = cellRect.left - trackRect.left + this._currentTranslate();

		if ( this.config.cellAlign === 'center' ) {
			raw -= (this.wrapper.offsetWidth - cell.offsetWidth) / 2;
		} else if ( this.config.cellAlign === 'right' ) {
			raw -= this.wrapper.offsetWidth - cell.offsetWidth;
		}

		if ( this.config.contain ) {
			var maxScroll = this.track.scrollWidth - this.wrapper.offsetWidth;
			raw = clamp(raw, 0, Math.max(0, maxScroll));
		}

		return raw;
	};

	LDCarousel.prototype.goTo = function ( idx, animate ) {
		var groups  = this._groupCount();
		var g       = getResponsiveGroupCells(this.config);
		var cellIdx = (g > 1) ? idx * g : idx;

		if ( this.config.wrapAround ) {
			idx = ((idx % groups) + groups) % groups;
		} else {
			idx = clamp(idx, 0, groups - 1);
		}

		this.index = idx;
		cellIdx    = (g > 1) ? idx * g : idx;

		var offset = this._cellOffset(cellIdx);

		if ( animate !== false ) {
			prefixCSS(this.track, 'transition', 'transform 0.4s cubic-bezier(0.23,1,0.32,1)');
		} else {
			prefixCSS(this.track, 'transition', 'none');
		}

		if ( this.config.percentPosition ) {
			prefixCSS(this.track, 'transform', 'translateX(-' + offset + '%)');
		} else {
			prefixCSS(this.track, 'transform', 'translateX(' + (-offset) + 'px)');
		}

		this._updateDots();
		this._updateButtons();
		this._announceSlide();
		this._lazyLoadVisible();

		// asNavFor — notify linked slider
		if ( this._navTarget ) {
			this._navTarget.goTo(idx);
		}
	};

	LDCarousel.prototype.next     = function () { this.goTo(this.index + 1); };
	LDCarousel.prototype.previous = function () { this.goTo(this.index - 1); };

	LDCarousel.prototype._updateDots = function () {
		if ( !this.dotsWrapper ) return;
		var idx = this.index;
		this.dotsWrapper.querySelectorAll('.ld-slider-dot').forEach(function(d,i){
			d.classList.toggle('is-selected', i === idx);
		});
	};

	LDCarousel.prototype._updateButtons = function () {
		if ( !this.btnPrev || !this.btnNext ) return;
		if ( this.config.wrapAround ) {
			this.btnPrev.disabled = false;
			this.btnNext.disabled = false;
			return;
		}
		var groups = this._groupCount();
		this.btnPrev.disabled = this.index <= 0;
		this.btnNext.disabled = this.index >= groups - 1;
	};

	LDCarousel.prototype._announceSlide = function () {
		if ( !this.config.accessibility ) return;
		if ( !this._liveRegion ) {
			this._liveRegion = document.createElement('div');
			this._liveRegion.setAttribute('aria-live','polite');
			this._liveRegion.setAttribute('aria-atomic','true');
			this._liveRegion.className = 'ld-sr-only';
			this.wrapper.appendChild(this._liveRegion);
		}
		this._liveRegion.textContent = 'Slide ' + (this.index+1) + ' of ' + this._groupCount();
	};

	/* ─── Lazy Load ────────────────────────────────────────── */

	LDCarousel.prototype._initLazyLoad = function () {
		var self = this;
		this.cells.forEach(function(cell) {
			cell.querySelectorAll('img[data-src]').forEach(function(img){
				img.setAttribute('src','');
			});
		});
		this._lazyLoadVisible();
	};

	LDCarousel.prototype._lazyLoadVisible = function () {
		if ( !this.config.lazyLoad ) return;
		var ahead = typeof this.config.lazyLoad === 'number' ? this.config.lazyLoad : 1;
		var g     = getResponsiveGroupCells(this.config);
		for ( var i = this.index * g; i < Math.min(this.cells.length, (this.index * g) + g + ahead); i++ ) {
			if ( this.cells[i] ) {
				this.cells[i].querySelectorAll('img[data-src]').forEach(function(img){
					if ( img.getAttribute('src') !== img.dataset.src ) {
						img.src = img.dataset.src;
					}
				});
			}
		}
	};

	/* ─── asNavFor ─────────────────────────────────────────── */

	LDCarousel.prototype._bindAsNavFor = function () {
		var self     = this;
		var selector = this.config.asNavFor;
		// Try to find immediately, or wait for DOM
		function tryLink() {
			var target = document.querySelector(selector);
			if ( target && target._ldSlider ) {
				self._navTarget = target._ldSlider;
				target._ldSlider._navSource = self;
			}
		}
		setTimeout(tryLink, 100);
	};

	/* ─── Drag / Touch ─────────────────────────────────────── */

	LDCarousel.prototype._bindDrag = function () {
		var self = this;
		var el   = this.track;

		function onStart(x, y, touch) {
			self.isTouch    = touch;
			self.isDragging = true;
			self.startX     = x;
			self.startY     = y;
			self.currentX   = self._currentTranslate();
			self.lastX      = x;
			self.lastTime   = Date.now();
			self.velocity   = 0;
			prefixCSS(el, 'transition', 'none');
			if (self.autoTimer) self._stopAutoPlay();
		}

		function onMove(x, y) {
			if (!self.isDragging) return;
			var dx  = x - self.startX;
			var dy  = y - self.startY;
			var now = Date.now();
			var dt  = now - self.lastTime || 1;
			self.velocity = (x - self.lastX) / dt;
			self.lastX    = x;
			self.lastTime = now;
			if ( Math.abs(dy) > Math.abs(dx) && Math.abs(dx) < self.config.dragThreshold ) {
				self.isDragging = false;
				return;
			}
			prefixCSS(el, 'transform', 'translateX(' + (self.currentX + dx) + 'px)');
		}

		function onEnd(x) {
			if (!self.isDragging) return;
			self.isDragging = false;
			var dx = x - self.startX;
			if (self.config.freeScroll) {
				var dest = self.currentX + dx + self.velocity * 150;
				prefixCSS(el, 'transition', 'transform 0.5s cubic-bezier(0.23,1,0.32,1)');
				prefixCSS(el, 'transform',  'translateX(' + dest + 'px)');
				return;
			}
			if ( Math.abs(dx) > self.config.dragThreshold || Math.abs(self.velocity) > 0.3 ) {
				dx < 0 ? self.next() : self.previous();
			} else {
				self.goTo(self.index);
			}
			if (self.config.autoPlay) self._startAutoPlay();
		}

		el.addEventListener('mousedown', function(e){ onStart(e.clientX, e.clientY, false); });
		window.addEventListener('mousemove', function(e){ if (self.isDragging && !self.isTouch) onMove(e.clientX, e.clientY); });
		window.addEventListener('mouseup',   function(e){ if (!self.isTouch) onEnd(e.clientX); });

		el.addEventListener('touchstart', function(e){
			var t = e.touches[0]; onStart(t.clientX, t.clientY, true);
		}, {passive:true});
		el.addEventListener('touchmove', function(e){
			var t = e.touches[0]; onMove(t.clientX, t.clientY);
		}, {passive:true});
		el.addEventListener('touchend', function(e){
			var t = e.changedTouches[0]; onEnd(t.clientX);
		});
		el.addEventListener('dragstart', function(e){ e.preventDefault(); });
	};

	LDCarousel.prototype._currentTranslate = function () {
		var style  = window.getComputedStyle(this.track);
		var matrix = style.transform || style.webkitTransform;
		if (!matrix || matrix === 'none') return 0;
		var m = matrix.match(/matrix.*\((.+)\)/);
		return m ? parseFloat(m[1].split(',')[4]) : 0;
	};

	/* ─── Keyboard ─────────────────────────────────────────── */

	LDCarousel.prototype._bindKeyboard = function () {
		var self = this;
		if (!this.config.accessibility) return;
		this.wrapper.setAttribute('tabindex','0');
		this.wrapper.addEventListener('keydown', function(e){
			if (e.key==='ArrowLeft'  || e.key==='ArrowUp')   { self.previous(); e.preventDefault(); }
			if (e.key==='ArrowRight' || e.key==='ArrowDown')  { self.next();     e.preventDefault(); }
		});
	};

	/* ─── Resize ───────────────────────────────────────────── */

	LDCarousel.prototype._bindResize = function () {
		var self    = this;
		var timeout = null;
		window.addEventListener('resize', function(){
			clearTimeout(timeout);
			timeout = setTimeout(function(){
				self.goTo(self.index, false);
				if (self.dotsWrapper) self._buildDots();
			}, 150);
		});
	};

	/* ─── AutoPlay ─────────────────────────────────────────── */

	LDCarousel.prototype._startAutoPlay = function () {
		var self  = this;
		var delay = typeof this.config.autoPlay === 'number' ? this.config.autoPlay : 3000;
		this._stopAutoPlay();
		this.autoTimer = setInterval(function(){ self.next(); }, delay);
	};

	LDCarousel.prototype._stopAutoPlay = function () {
		clearInterval(this.autoTimer);
		this.autoTimer = null;
	};

	/* ─── Boot ─────────────────────────────────────────────── */

	function initAll() {
		document.querySelectorAll('[data-ld-slider]').forEach(function(el){
			if (!el._ldSlider) {
				el._ldSlider = new LDCarousel(el);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

	window.LDSliders = { init: initAll, LDCarousel: LDCarousel };

}());
