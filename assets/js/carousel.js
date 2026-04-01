(function () {
	'use strict';

	var i18n = window.wp && window.wp.i18n ? window.wp.i18n : {};
	var __ = i18n.__
		? i18n.__
		: function (text) {
			return text;
		};
	var sprintf = i18n.sprintf
		? i18n.sprintf
		: function (text) {
			var output = text;

			Array.prototype.slice.call(arguments, 1).forEach(function (value, index) {
				output = output.replace('%' + (index + 1) + '$d', value);
			});

			return output;
		};
	var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function createCarousel(root) {
		var viewport = root.querySelector('[data-role="viewport"]');
		var track = root.querySelector('.ntc-carousel__track');
		var slides = track ? Array.prototype.slice.call(track.querySelectorAll('.ntc-carousel__slide')) : [];
		var prevButton = root.querySelector('[data-action="prev"]');
		var nextButton = root.querySelector('[data-action="next"]');
		var status = root.querySelector('[data-role="status"]');
		var autoplayEnabled = root.getAttribute('data-autoplay') === 'true' && !prefersReducedMotion;
		var timerId = null;
		var scrollTimerId = null;
		var dragState = {
			active: false,
			pointerId: null,
			pointerType: '',
			startX: 0,
			startScrollLeft: 0,
			currentDeltaX: 0,
			moved: false,
			suppressClick: false
		};

		if (!viewport || !track || slides.length < 2) {
			disableControls();
			updateStatus();
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

		function scrollToIndex(index) {
			var target = slides[index];

			if (!target) {
				return;
			}

			viewport.scrollTo({
				left: target.offsetLeft,
				behavior: getScrollBehavior()
			});
		}

		function updateStatus() {
			var currentIndex;
			var visibleSlides;
			var lastVisible;

			if (!status) {
				return;
			}

			currentIndex = getCurrentIndex();
			visibleSlides = getVisibleSlides();
			lastVisible = Math.min(slides.length, currentIndex + visibleSlides);

			status.textContent = sprintf(
				__('Showing %1$d-%2$d of %3$d', 'rss-news-carousel'),
				currentIndex + 1,
				lastVisible,
				slides.length
			);
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

		function handlePrev() {
			var currentIndex = Math.min(getCurrentIndex(), getMaxStartIndex());
			var targetIndex = currentIndex - 1;

			if (targetIndex < 0) {
				targetIndex = getMaxStartIndex();
			}

			scrollToIndex(targetIndex);
		}

		function handleNext() {
			var maxStartIndex = getMaxStartIndex();
			var currentIndex = Math.min(getCurrentIndex(), maxStartIndex);
			var targetIndex = currentIndex + 1;

			if (targetIndex > maxStartIndex) {
				targetIndex = 0;
			}

			scrollToIndex(targetIndex);
		}

		function snapToNearest() {
			scrollToIndex(Math.min(getCurrentIndex(), getMaxStartIndex()));
		}

		function isBlockedPointerTarget(target) {
			return !!target.closest('button, audio, video');
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
			dragState.startScrollLeft = viewport.scrollLeft;
			dragState.currentDeltaX = 0;
			dragState.moved = false;

			stopAutoplay();
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

			if (!dragState.active) {
				return;
			}

			deltaX = event.clientX - dragState.startX;
			dragState.currentDeltaX = deltaX;

			if (Math.abs(deltaX) > 6) {
				dragState.moved = true;
				dragState.suppressClick = true;
				event.preventDefault();
			}

			if (dragState.pointerType === 'touch') {
				root.style.setProperty('--ntc-drag-offset', Math.max(-26, Math.min(26, deltaX * 0.18)) + 'px');
				root.style.setProperty('--ntc-drag-tilt', Math.max(-1.2, Math.min(1.2, deltaX * 0.008)) + 'deg');
			}

			viewport.scrollLeft = dragState.startScrollLeft - deltaX;
		}

		function onPointerEnd(event) {
			if (!dragState.active) {
				return;
			}

			dragState.active = false;
			viewport.classList.remove('is-dragging');
			root.classList.remove('is-touch-dragging');
			root.style.setProperty('--ntc-drag-offset', '0px');
			root.style.setProperty('--ntc-drag-tilt', '0deg');

			if (viewport.releasePointerCapture && null !== dragState.pointerId) {
				try {
					viewport.releasePointerCapture(event.pointerId);
				} catch (error) {
				}
			}

			dragState.pointerId = null;
			dragState.pointerType = '';
			dragState.currentDeltaX = 0;

			if (dragState.moved) {
				snapToNearest();
			}

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
		root.addEventListener('focusin', stopAutoplay);
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

		viewport.addEventListener('scroll', function () {
			if (scrollTimerId) {
				window.clearTimeout(scrollTimerId);
			}

			scrollTimerId = window.setTimeout(updateStatus, 120);
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
			updateStatus();
		});

		updateStatus();
		updateNavigationState();
		startAutoplay();
	}

	document.addEventListener('DOMContentLoaded', function () {
		var carousels = document.querySelectorAll('[data-ntc-carousel="true"]');

		Array.prototype.forEach.call(carousels, function (carousel) {
			createCarousel(carousel);
		});
	});
})();
