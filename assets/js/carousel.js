(function () {
	'use strict';

	var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function createCarousel(root) {
		var viewport = root.querySelector('[data-role="viewport"]');
		var track = root.querySelector('.ntc-carousel__track');
		var slides = track ? Array.prototype.slice.call(track.querySelectorAll('.ntc-carousel__slide')) : [];
		var prevButton = root.querySelector('[data-action="prev"]');
		var nextButton = root.querySelector('[data-action="next"]');
		var supportsPointerEvents = typeof window.PointerEvent !== 'undefined';
		var autoplayEnabled = root.getAttribute('data-autoplay') === 'true' && !prefersReducedMotion;
		var timerId = null;
		var swipeCommitTimerId = null;
		var swipeHintTimerId = null;
		var dragState = {
			active: false,
			pointerId: null,
			pointerType: '',
			startX: 0,
			startTime: 0,
			startScrollLeft: 0,
			currentDeltaX: 0,
			lastClientX: 0,
			lastTimestamp: 0,
			velocityX: 0,
			moved: false,
			suppressClick: false
		};

		if (!viewport || !track || slides.length < 2) {
			disableControls();
			return;
		}

		function disableControls() {
			if (prevButton) {
				prevButton.disabled = true;
			}

			if (nextButton) {
				nextButton.disabled = true;
			}
		}

		function getCurrentIndex() {
			var scrollLeft = viewport.scrollLeft;
			var closestIndex = 0;
			var closestDistance = Number.POSITIVE_INFINITY;

			slides.forEach(function (slide, index) {
				var distance = Math.abs(slide.offsetLeft - scrollLeft);

				if (distance < closestDistance) {
					closestDistance = distance;
					closestIndex = index;
				}
			});

			return closestIndex;
		}

		function getVisibleSlides() {
			var configuredSlides = parseInt(window.getComputedStyle(root).getPropertyValue('--ntc-visible-slides'), 10);

			if (!Number.isNaN(configuredSlides) && configuredSlides > 0) {
				return configuredSlides;
			}

			if (!slides.length) {
				return 1;
			}

			var slideWidth = slides[0].getBoundingClientRect().width;
			var viewportWidth = viewport.getBoundingClientRect().width;

			if (!slideWidth || !viewportWidth) {
				return 1;
			}

			return Math.max(1, Math.floor(viewportWidth / slideWidth));
		}

		function getMaxStartIndex() {
			return Math.max(0, slides.length - getVisibleSlides());
		}

		function getScrollBehavior() {
			return prefersReducedMotion ? 'auto' : 'smooth';
		}

		function isMobileViewport() {
			return window.matchMedia('(max-width: 719px)').matches;
		}

		function getActiveSlideWidth() {
			if (!slides.length) {
				return 0;
			}

			return slides[0].getBoundingClientRect().width;
		}

		function getSwipeThreshold() {
			var slideWidth = getActiveSlideWidth();

			if (!slideWidth) {
				return 44;
			}

			return Math.max(42, Math.min(96, slideWidth * 0.16));
		}

		function scrollToIndex(index, behavior) {
			var target = slides[index];

			if (!target) {
				return;
			}

			viewport.scrollTo({
				left: target.offsetLeft,
				behavior: behavior || getScrollBehavior()
			});
		}

		function updateNavigationState() {
			var shouldDisable = slides.length <= getVisibleSlides();

			if (prevButton) {
				prevButton.disabled = shouldDisable;
			}

			if (nextButton) {
				nextButton.disabled = shouldDisable;
			}
		}

		function stopAutoplay() {
			if (timerId) {
				window.clearInterval(timerId);
				timerId = null;
			}
		}

		function startAutoplay() {
			stopAutoplay();

			if (!autoplayEnabled || dragState.active) {
				return;
			}

			timerId = window.setInterval(function () {
				handleNext();
			}, 5000);
		}

		function handlePrev(behavior) {
			var currentIndex = Math.min(getCurrentIndex(), getMaxStartIndex());
			var targetIndex = currentIndex - 1;

			if (targetIndex < 0) {
				targetIndex = getMaxStartIndex();
			}

			scrollToIndex(targetIndex, behavior);
		}

		function handleNext(behavior) {
			var maxStartIndex = getMaxStartIndex();
			var currentIndex = Math.min(getCurrentIndex(), maxStartIndex);
			var targetIndex = currentIndex + 1;

			if (targetIndex > maxStartIndex) {
				targetIndex = 0;
			}

			scrollToIndex(targetIndex, behavior);
		}

		function snapToNearest() {
			scrollToIndex(Math.min(getCurrentIndex(), getMaxStartIndex()));
		}

		function clearSwipeCommitClasses() {
			root.classList.remove('is-swipe-committing-next');
			root.classList.remove('is-swipe-committing-prev');

			if (swipeCommitTimerId) {
				window.clearTimeout(swipeCommitTimerId);
				swipeCommitTimerId = null;
			}
		}

		function hasSeenSwipeHint() {
			try {
				return window.localStorage.getItem('ntcSwipeHintSeen') === '1';
			} catch (error) {
				return false;
			}
		}

		function markSwipeHintSeen() {
			try {
				window.localStorage.setItem('ntcSwipeHintSeen', '1');
			} catch (error) {
			}
		}

		function ensureSwipeHint() {
			var existingHint = root.querySelector('.ntc-carousel__swipe-hint');
			var hint;

			if (existingHint) {
				return existingHint;
			}

			hint = document.createElement('div');
			hint.className = 'ntc-carousel__swipe-hint';
			hint.setAttribute('aria-hidden', 'true');
			hint.innerHTML =
				'<span class="ntc-carousel__swipe-hint-arrow ntc-carousel__swipe-hint-arrow--left">&#10094;</span>' +
				'<span class="ntc-carousel__swipe-hint-label">Swipe</span>' +
				'<span class="ntc-carousel__swipe-hint-arrow ntc-carousel__swipe-hint-arrow--right">&#10095;</span>';

			root.appendChild(hint);

			return hint;
		}

		function hideSwipeHint() {
			root.classList.remove('is-swipe-hint-visible');

			if (swipeHintTimerId) {
				window.clearTimeout(swipeHintTimerId);
				swipeHintTimerId = null;
			}
		}

		function maybeShowSwipeHint() {
			if (prefersReducedMotion || !isMobileViewport() || slides.length < 2 || hasSeenSwipeHint()) {
				return;
			}

			ensureSwipeHint();
			root.classList.add('is-swipe-hint-visible');
			markSwipeHintSeen();

			swipeHintTimerId = window.setTimeout(function () {
				root.classList.remove('is-swipe-hint-visible');
				swipeHintTimerId = null;
			}, 2600);
		}

		function triggerSwipeCommit(direction) {
			var className = direction === 'next' ? 'is-swipe-committing-next' : 'is-swipe-committing-prev';

			if (prefersReducedMotion) {
				return;
			}

			clearSwipeCommitClasses();
			root.classList.add(className);

			swipeCommitTimerId = window.setTimeout(function () {
				root.classList.remove(className);
				swipeCommitTimerId = null;
			}, 280);
		}

		function updateDragVisuals(deltaX) {
			var threshold = getSwipeThreshold();
			var progress = threshold ? Math.min(1.2, Math.abs(deltaX) / threshold) : 0;
			var offset = Math.max(-44, Math.min(44, deltaX * 0.32));
			var tilt = Math.max(-0.45, Math.min(0.45, deltaX * 0.0038));

			root.style.setProperty('--ntc-drag-offset', offset + 'px');
			root.style.setProperty('--ntc-drag-tilt', tilt + 'deg');
			root.style.setProperty('--ntc-drag-progress', progress.toFixed(3));
		}

		function resetDragVisuals() {
			root.style.setProperty('--ntc-drag-offset', '0px');
			root.style.setProperty('--ntc-drag-tilt', '0deg');
			root.style.setProperty('--ntc-drag-progress', '0');
		}

		function commitSwipe(direction) {
			triggerSwipeCommit(direction);

			if (direction === 'next') {
				handleNext(getScrollBehavior());
				return;
			}

			handlePrev(getScrollBehavior());
		}

		function shouldCommitSwipe() {
			var absDeltaX = Math.abs(dragState.currentDeltaX);
			var absVelocityX = Math.abs(dragState.velocityX);
			var threshold = getSwipeThreshold();

			return absDeltaX >= threshold || absVelocityX >= 0.42;
		}

		function isBlockedPointerTarget(target) {
			return !!target.closest('a, button, audio, video');
		}

		function onPointerDown(event) {
			if (isBlockedPointerTarget(event.target)) {
				return;
			}

			if (event.pointerType === 'mouse' && event.button !== 0) {
				return;
			}

			dragState.active = true;
			dragState.pointerId = event.pointerId;
			dragState.pointerType = event.pointerType || '';
			dragState.startX = event.clientX;
			dragState.startTime = Date.now();
			dragState.startScrollLeft = viewport.scrollLeft;
			dragState.currentDeltaX = 0;
			dragState.lastClientX = event.clientX;
			dragState.lastTimestamp = dragState.startTime;
			dragState.velocityX = 0;
			dragState.moved = false;

			stopAutoplay();
			clearSwipeCommitClasses();
			hideSwipeHint();
			viewport.classList.add('is-dragging');

			if (dragState.pointerType === 'touch') {
				root.classList.add('is-touch-dragging');
			}

			if (viewport.setPointerCapture) {
				viewport.setPointerCapture(event.pointerId);
			}
		}

		function onPointerMove(event) {
			var deltaX;
			var now;
			var elapsed;
			var stepDistance;

			if (!dragState.active) {
				return;
			}

			deltaX = event.clientX - dragState.startX;
			dragState.currentDeltaX = deltaX;
			now = Date.now();
			elapsed = Math.max(1, now - dragState.lastTimestamp);
			stepDistance = event.clientX - dragState.lastClientX;
			dragState.velocityX = stepDistance / elapsed;
			dragState.lastClientX = event.clientX;
			dragState.lastTimestamp = now;

			if (Math.abs(deltaX) > 6) {
				dragState.moved = true;
				dragState.suppressClick = true;
				event.preventDefault();
			}

			if (dragState.pointerType === 'touch') {
				updateDragVisuals(deltaX);
			}

			viewport.scrollLeft = dragState.startScrollLeft - deltaX;
		}

		function onPointerEnd(event) {
			var pointerType;
			var shouldCommit;
			var swipeDirection;

			if (!dragState.active) {
				return;
			}

			pointerType = dragState.pointerType;
			shouldCommit = dragState.moved && pointerType === 'touch' && shouldCommitSwipe();
			swipeDirection = dragState.currentDeltaX < 0 ? 'next' : 'prev';

			dragState.active = false;
			viewport.classList.remove('is-dragging');
			root.classList.remove('is-touch-dragging');
			resetDragVisuals();

			if (viewport.releasePointerCapture && null !== dragState.pointerId) {
				try {
					viewport.releasePointerCapture(event.pointerId);
				} catch (error) {
				}
			}

			dragState.pointerId = null;
			dragState.pointerType = '';

			if (dragState.moved) {
				if (shouldCommit) {
					commitSwipe(swipeDirection);
				} else {
					snapToNearest();
				}
			}

			dragState.currentDeltaX = 0;
			dragState.velocityX = 0;

			window.setTimeout(function () {
				dragState.suppressClick = false;
			}, 120);

			startAutoplay();
		}

		if (prevButton) {
			prevButton.addEventListener('click', function () {
				handlePrev();
				prevButton.blur();
				startAutoplay();
			});
		}

		if (nextButton) {
			nextButton.addEventListener('click', function () {
				handleNext();
				nextButton.blur();
				startAutoplay();
			});
		}

		viewport.addEventListener('pointerdown', onPointerDown);
		viewport.addEventListener('pointermove', onPointerMove);
		viewport.addEventListener('pointerup', onPointerEnd);
		viewport.addEventListener('pointercancel', onPointerEnd);
		viewport.addEventListener('pointerleave', function () {
			if (dragState.active) {
				onPointerEnd({ pointerId: dragState.pointerId });
			}
		});

		if (!supportsPointerEvents) {
			viewport.addEventListener(
				'touchstart',
				function (event) {
					var touch;

					if (!event.changedTouches.length) {
						return;
					}

					touch = event.changedTouches[0];

					onPointerDown({
						target: event.target,
						clientX: touch.clientX,
						pointerId: 1,
						pointerType: 'touch'
					});
				},
				{ passive: true }
			);

			viewport.addEventListener(
				'touchmove',
				function (event) {
					var touch;

					if (!dragState.active || !event.changedTouches.length) {
						return;
					}

					touch = event.changedTouches[0];

					onPointerMove({
						clientX: touch.clientX,
						preventDefault: function () {
							event.preventDefault();
						}
					});
				},
				{ passive: false }
			);

			viewport.addEventListener(
				'touchend',
				function () {
					if (dragState.active) {
						onPointerEnd({ pointerId: dragState.pointerId });
					}
				},
				{ passive: true }
			);

			viewport.addEventListener(
				'touchcancel',
				function () {
					if (dragState.active) {
						onPointerEnd({ pointerId: dragState.pointerId });
					}
				},
				{ passive: true }
			);
		}

		viewport.addEventListener(
			'click',
			function (event) {
				if (dragState.suppressClick) {
					event.preventDefault();
					event.stopPropagation();
				}
			},
			true
		);

		root.addEventListener('mouseenter', stopAutoplay);
		root.addEventListener('mouseleave', startAutoplay);
		root.addEventListener('focusin', function () {
			hideSwipeHint();
			stopAutoplay();
		});
		root.addEventListener('focusout', startAutoplay);

		viewport.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				handlePrev();
			}

			if (event.key === 'ArrowRight') {
				event.preventDefault();
				handleNext();
			}
		});

		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				stopAutoplay();
				return;
			}

			startAutoplay();
		});

		window.addEventListener('resize', function () {
			updateNavigationState();
			snapToNearest();
		});

		updateNavigationState();
		startAutoplay();
		window.setTimeout(maybeShowSwipeHint, 550);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var carousels = document.querySelectorAll('[data-ntc-carousel="true"]');

		Array.prototype.forEach.call(carousels, function (carousel) {
			createCarousel(carousel);
		});
	});
})();
