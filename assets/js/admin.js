(function () {
	'use strict';

	function getText(key, fallback) {
		if (window.ntcAdminL10n && window.ntcAdminL10n[key]) {
			return window.ntcAdminL10n[key];
		}

		return fallback;
	}

	function createTemplateItem(template) {
		if (template.content && template.content.firstElementChild) {
			return template.content.firstElementChild.cloneNode(true);
		}

		var wrapper = document.createElement('div');
		wrapper.innerHTML = template.innerHTML.trim();

		return wrapper.firstElementChild;
	}

	function moveItem(list, item, direction) {
		if (direction === 'up' && item.previousElementSibling) {
			list.insertBefore(item, item.previousElementSibling);
			return true;
		}

		if (direction === 'down' && item.nextElementSibling) {
			list.insertBefore(item.nextElementSibling, item);
			return true;
		}

		return false;
	}

	function initFeedSources(container) {
		var list = container.querySelector('[data-role="feed-source-list"]');
		var storage = container.querySelector('[data-role="feed-source-storage"]');
		var addButton = container.querySelector('[data-action="add-feed-source"]');
		var template = container.querySelector('template');
		var draggedItem = null;

		if (!list || !storage || !addButton || !template) {
			return;
		}

		function getItems() {
			return Array.prototype.slice.call(list.querySelectorAll('.ntc-feed-sources__item'));
		}

		function clearDropMarkers() {
			getItems().forEach(function (item) {
				item.classList.remove('is-drop-target-before');
				item.classList.remove('is-drop-target-after');
			});
		}

		function updateMoveButtons() {
			var items = getItems();

			items.forEach(function (item, index) {
				var moveUpButton = item.querySelector('[data-action="move-feed-source-up"]');
				var moveDownButton = item.querySelector('[data-action="move-feed-source-down"]');

				if (moveUpButton) {
					moveUpButton.disabled = index === 0;
				}

				if (moveDownButton) {
					moveDownButton.disabled = index === items.length - 1;
				}
			});
		}

		function syncStorage() {
			var values = getItems()
				.map(function (item) {
					var input = item.querySelector('.ntc-feed-sources__input');
					return input ? input.value.trim() : '';
				})
				.filter(function (value) {
					return value.length > 0;
				});

			storage.value = values.join('\n');
			container.classList.toggle('is-empty', values.length === 0);
			updateMoveButtons();
		}

		function bindItem(item) {
			var input = item.querySelector('.ntc-feed-sources__input');
			var handle = item.querySelector('.ntc-feed-sources__handle');
			var removeButton = item.querySelector('[data-action="remove-feed-source"]');
			var moveUpButton = item.querySelector('[data-action="move-feed-source-up"]');
			var moveDownButton = item.querySelector('[data-action="move-feed-source-down"]');

			if (!input || !handle || !removeButton || !moveUpButton || !moveDownButton) {
				return;
			}

			handle.setAttribute('aria-label', getText('dragSource', 'Drag to reorder'));
			moveUpButton.textContent = getText('moveUp', 'Up');
			moveDownButton.textContent = getText('moveDown', 'Down');
			removeButton.textContent = getText('removeSource', 'Remove');

			input.addEventListener('input', syncStorage);

			removeButton.addEventListener('click', function () {
				item.remove();
				syncStorage();
			});

			moveUpButton.addEventListener('click', function () {
				if (moveItem(list, item, 'up')) {
					syncStorage();
					input.focus();
				}
			});

			moveDownButton.addEventListener('click', function () {
				if (moveItem(list, item, 'down')) {
					syncStorage();
					input.focus();
				}
			});

			handle.addEventListener('mousedown', function () {
				item.draggable = true;
			});

			handle.addEventListener('mouseup', function () {
				if (!draggedItem) {
					item.draggable = false;
				}
			});

			handle.addEventListener('mouseleave', function () {
				if (!draggedItem) {
					item.draggable = false;
				}
			});

			item.addEventListener('dragstart', function (event) {
				if (!item.draggable) {
					event.preventDefault();
					return;
				}

				draggedItem = item;
				container.classList.add('is-sorting');
				item.classList.add('is-dragging');

				if (event.dataTransfer) {
					event.dataTransfer.effectAllowed = 'move';
					event.dataTransfer.setData('text/plain', input.value || 'reorder');
				}
			});

			item.addEventListener('dragover', function (event) {
				var rect;
				var insertBefore;
				var referenceNode;

				if (!draggedItem || draggedItem === item) {
					return;
				}

				event.preventDefault();
				rect = item.getBoundingClientRect();
				insertBefore = event.clientY < rect.top + (rect.height / 2);
				referenceNode = insertBefore ? item : item.nextElementSibling;

				clearDropMarkers();
				item.classList.add(insertBefore ? 'is-drop-target-before' : 'is-drop-target-after');

				if (referenceNode !== draggedItem && draggedItem.nextElementSibling !== referenceNode) {
					list.insertBefore(draggedItem, referenceNode);
				}
			});

			item.addEventListener('drop', function (event) {
				event.preventDefault();
				clearDropMarkers();
				syncStorage();
			});

			item.addEventListener('dragend', function () {
				item.draggable = false;
				item.classList.remove('is-dragging');
				container.classList.remove('is-sorting');
				clearDropMarkers();
				draggedItem = null;
				syncStorage();
			});
		}

		function addItem(value) {
			var item = createTemplateItem(template);
			var input;

			if (!item) {
				return null;
			}

			input = item.querySelector('.ntc-feed-sources__input');

			if (input) {
				input.value = value || '';
			}

			list.appendChild(item);
			bindItem(item);

			return item;
		}

		getItems().forEach(bindItem);

		list.addEventListener('dragover', function (event) {
			if (!draggedItem) {
				return;
			}

			event.preventDefault();
		});

		list.addEventListener('drop', function (event) {
			if (!draggedItem) {
				return;
			}

			event.preventDefault();
			clearDropMarkers();
			syncStorage();
		});

		addButton.addEventListener('click', function () {
			var item = addItem('');
			var input = item ? item.querySelector('.ntc-feed-sources__input') : null;

			syncStorage();

			if (input) {
				input.focus();
			}
		});

		syncStorage();
	}

	document.addEventListener('DOMContentLoaded', function () {
		Array.prototype.forEach.call(document.querySelectorAll('[data-ntc-feed-sources="true"]'), initFeedSources);
	});
})();
