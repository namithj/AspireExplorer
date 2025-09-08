jQuery(document).ready(function () {
	new AeLightbox();
	new AeCart();
	if( jQuery('#not-found').length !== 0 ) {
		new FallingText({
			container: document.getElementById('not-found'),
			text: 'No Plugins Found',
			width: 600,
			height: 400
		});
	}
});

class AeLightbox {
	constructor() {
		this.overlay = null;
		this.overlayImg = null;
		this.closeBtn = null;
		this.bindOpen();
	}

	bindOpen() {
		jQuery('#accordion-content-screenshots ol a').on('click', (e) => {
			e.preventDefault();
			const imgSrc = jQuery(e.currentTarget).find('img').attr('src');
			this.createLightboxMarkup(imgSrc);
		});
	}

	createLightboxMarkup(imgSrc) {
		jQuery('#ae-lightbox-overlay').remove();
		// Find the caption from the <p> sibling of the clicked <a>
		const $link = jQuery(`#accordion-content-screenshots ol a img[src='${imgSrc}']`).closest('a');
		let caption = '';
		if ($link.length) {
			const $captionP = $link.next('p');
			if ($captionP.length) {
				caption = $captionP.html();
			}
		}
		jQuery('body').append(
			jQuery('<div>', { id: 'ae-lightbox-overlay', css: { display: 'flex' } }).append(
				jQuery('<span>', { id: 'ae-lightbox-close', html: '&times;' }),
				jQuery('<img>', { id: 'ae-lightbox-img', src: imgSrc, alt: 'Screenshot preview' }),
				caption ? jQuery('<div>', { id: 'ae-lightbox-caption', html: caption }) : null
			)
		);
		this.overlay = jQuery('#ae-lightbox-overlay');
		this.overlayImg = jQuery('#ae-lightbox-img');
		this.closeBtn = jQuery('#ae-lightbox-close');
		this.bindClose();
	}

	bindClose() {
		this.closeBtn.on('click', () => {
			this.destroy();
		});

		this.overlay.on('click', (e) => {
			if (e.target === this.overlay[0]) {
				this.destroy();
			}
		});

		jQuery(document).on('keydown.aelightbox', (e) => {
			if (e.key === 'Escape') {
				this.destroy();
			}
		});
	}

	destroy() {
		if (this.overlay) {
			this.overlay.remove();
			this.overlay = null;
			this.overlayImg = null;
			this.closeBtn = null;
			jQuery(document).off('keydown.aelightbox');
		}
	}
}

class AeCart {
	constructor() {
		this.cartItems = [];
		this.floatButton = null;
		this.overlay = null;
		this.popup = null;
		this.textarea = null;
		this.copyButton = null;
		this.clearButton = null;
		this.countBadge = null;

		this.init();
	}

	init() {
		this.loadCartFromCookie();
		this.createFloatButton();
		this.createPopup();
		this.bindEvents();
		this.updateUI();
	}

	setCookie(name, value, days = 30) {
		const expires = new Date();
		expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
		document.cookie = `${name}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
	}

	getCookie(name) {
		const nameEQ = name + "=";
		const cookies = document.cookie.split(';');
		for (let i = 0; i < cookies.length; i++) {
			let cookie = cookies[i].trim();
			if (cookie.indexOf(nameEQ) === 0) {
				return decodeURIComponent(cookie.substring(nameEQ.length));
			}
		}
		return null;
	}

	saveCartToCookie() {
		this.setCookie('ae_cart_items', JSON.stringify(this.cartItems));
	}

	loadCartFromCookie() {
		const savedCart = this.getCookie('ae_cart_items');
		if (savedCart) {
			try {
				this.cartItems = JSON.parse(savedCart) || [];
				// Ensure cartItems is always an array
				if (!Array.isArray(this.cartItems)) {
					this.cartItems = [];
				}
			} catch (e) {
				console.warn('Failed to load cart from cookie:', e);
				this.cartItems = [];
			}
		}
	}

	createFloatButton() {
		// Remove existing float button if it exists
		jQuery('.ae-cart-float').remove();

		this.floatButton = jQuery(`
			<button class="ae-cart-float" title="View Cart">
				<span class="dashicons dashicons-cart"></span>
				<span class="ae-cart-count">0</span>
			</button>
		`);

		jQuery('body').append(this.floatButton);
		this.countBadge = this.floatButton.find('.ae-cart-count');
	}

	createPopup() {
		// Remove existing popup if it exists
		jQuery('.ae-cart-overlay').remove();

		this.overlay = jQuery(`
			<div class="ae-cart-overlay">
				<div class="ae-cart-popup">
					<button class="ae-cart-close">&times;</button>
					<h2 class="ae-cart-title">Selected Plugins</h2>
					<div class="ae-cart-content">
						<div class="ae-cart-empty">No plugins selected</div>
						<textarea class="ae-cart-textarea" readonly placeholder="Plugin slugs will appear here..."></textarea>
					</div>
					<div class="ae-cart-actions">
						<button class="button button-secondary ae-cart-clear">Clear All</button>
						<button class="button button-primary ae-cart-copy" disabled>Copy to Clipboard</button>
					</div>
				</div>
			</div>
		`);

		jQuery('body').append(this.overlay);
		this.popup = this.overlay.find('.ae-cart-popup');
		this.textarea = this.overlay.find('.ae-cart-textarea');
		this.copyButton = this.overlay.find('.ae-cart-copy');
		this.clearButton = this.overlay.find('.ae-cart-clear');
	}

	bindEvents() {
		// Add to cart button clicks - toggle functionality
		jQuery(document).on('click', '.entry-add-to-cart button', (e) => {
			e.preventDefault();
			const slug = jQuery(e.currentTarget).data('slug');

			// Toggle: if already in cart, remove it; otherwise add it
			if (this.cartItems.includes(slug)) {
				this.removeFromCart(slug);
			} else {
				this.addToCart(slug);
			}
		});

		// Float button click
		this.floatButton.on('click', () => {
			this.openPopup();
		});

		// Close popup events
		this.overlay.find('.ae-cart-close').on('click', () => {
			this.closePopup();
		});

		this.overlay.on('click', (e) => {
			if (e.target === this.overlay[0]) {
				this.closePopup();
			}
		});

		// Copy to clipboard
		this.copyButton.on('click', () => {
			this.copyToClipboard();
		});

		// Clear cart
		this.clearButton.on('click', () => {
			this.clearCart();
		});

		// Escape key to close popup
		jQuery(document).on('keydown.aecart', (e) => {
			if (e.key === 'Escape' && this.overlay.hasClass('ae-cart-open')) {
				this.closePopup();
			}
		});
	}

	addToCart(slug) {
		if (!slug || this.cartItems.includes(slug)) {
			return; // Don't add duplicates
		}

		this.cartItems.push(slug);
		this.saveCartToCookie();
		this.updateUI();
		this.showSuccessMessage(`"${slug}" added to cart`);
	}

	removeFromCart(slug) {
		const index = this.cartItems.indexOf(slug);
		if (index > -1) {
			this.cartItems.splice(index, 1);
			this.saveCartToCookie();
			this.updateUI();
			this.showSuccessMessage(`"${slug}" removed from cart`);
		}
	}

	clearCart() {
		this.cartItems = [];
		this.saveCartToCookie();
		this.updateUI();
		this.showSuccessMessage('Cart cleared');
	}

	updateUI() {
		const count = this.cartItems.length;

		// Update count badge
		this.countBadge.text(count);

		// Show/hide float button
		if (count > 0) {
			this.floatButton.addClass('ae-cart-visible');
		} else {
			this.floatButton.removeClass('ae-cart-visible');
		}

		// Update popup content
		if (count === 0) {
			this.overlay.find('.ae-cart-empty').show();
			this.textarea.hide().val('');
			this.copyButton.prop('disabled', true);
		} else {
			this.overlay.find('.ae-cart-empty').hide();
			this.textarea.show().val(this.cartItems.join(', '));
			this.copyButton.prop('disabled', false);
		}

		// Update add to cart buttons
		jQuery('.entry-add-to-cart button[data-slug]').each((index, button) => {
			const $button = jQuery(button);
			const slug = $button.data('slug');

			if (this.cartItems.includes(slug)) {
				$button.addClass('added-to-cart').prop('disabled', false);
				$button.find('.screen-reader-text').text('Remove from cart');
				$button.attr('aria-label', $button.attr('aria-label').replace('Add to cart', 'Remove from cart'));
				$button.attr('title', 'Remove from cart');
			} else {
				$button.removeClass('added-to-cart').prop('disabled', false);
				$button.find('.screen-reader-text').text('Add to cart');
				$button.attr('aria-label', $button.attr('aria-label').replace('Remove from cart', 'Add to cart'));
				$button.attr('title', 'Add to cart');
			}
		});
	}

	openPopup() {
		this.overlay.addClass('ae-cart-open');
		this.textarea.focus();
	}

	closePopup() {
		this.overlay.removeClass('ae-cart-open');
	}

	async copyToClipboard() {
		try {
			await navigator.clipboard.writeText(this.textarea.val());
			this.showSuccessMessage('Copied to clipboard!');
		} catch (err) {
			// Fallback for older browsers
			this.textarea[0].select();
			document.execCommand('copy');
			this.showSuccessMessage('Copied to clipboard!');
		}
	}

	showSuccessMessage(message) {
		// Remove existing success message
		jQuery('.ae-cart-success').remove();

		const successEl = jQuery(`<div class="ae-cart-success">${message}</div>`);
		jQuery('body').append(successEl);

		// Trigger animation
		setTimeout(() => {
			successEl.addClass('ae-cart-success-visible');
		}, 10);

		// Remove after 3 seconds
		setTimeout(() => {
			successEl.removeClass('ae-cart-success-visible');
			setTimeout(() => {
				successEl.remove();
			}, 300);
		}, 3000);
	}

	destroy() {
		if (this.floatButton) {
			this.floatButton.remove();
		}
		if (this.overlay) {
			this.overlay.remove();
		}
		jQuery(document).off('keydown.aecart');
		jQuery('.ae-cart-success').remove();
	}
}

class FallingText {
	constructor({ container, text, width, height }) {
		this.container = container;
		this.text = text.toUpperCase();
		this.width = width;
		this.height = height;

		this.FONT = {
			'A': ["01110", "10001", "10001", "11111", "10001", "10001", "10001"],
			'D': ["11110", "10001", "10001", "10001", "10001", "10001", "11110"],
			'E': ["11111", "10000", "10000", "11110", "10000", "10000", "11111"],
			'F': ["11111", "10000", "10000", "11110", "10000", "10000", "10000"],
			'G': ["01110", "10001", "10000", "10111", "10001", "10001", "01111"],
			'I': ["11111", "00100", "00100", "00100", "00100", "00100", "11111"],
			'L': ["10000", "10000", "10000", "10000", "10000", "10000", "11111"],
			'N': ["10001", "11001", "10101", "10011", "10001", "10001", "10001"],
			'O': ["01110", "10001", "10001", "10001", "10001", "10001", "01110"],
			'P': ["11110", "10001", "10001", "11110", "10000", "10000", "10000"],
			'S': ["01111", "10000", "10000", "01110", "00001", "00001", "11110"],
			'U': ["10001", "10001", "10001", "10001", "10001", "10001", "01110"],
			' ': ["00000", "00000", "00000", "00000", "00000", "00000", "00000"]
		};

		this.CHAR_WIDTH = 5;
		this.CHAR_HEIGHT = 7;
		this.LETTER_SPACING = 1;

		this.blocks = [];
		this.init();
	}

	init() {
		jQuery(this.container).css({ width: this.width + 'px', height: this.height + 'px' });

		const cols = this.text.length * (this.CHAR_WIDTH + this.LETTER_SPACING) - this.LETTER_SPACING;
		const rows = this.CHAR_HEIGHT;

		this.blockSize = Math.floor(Math.min(this.width / cols, this.height / rows));
		jQuery(this.container).css('--block-size', `${this.blockSize}px`);

		this.createBlocks();
		this.animate();
	}

	shuffle(array) {
		for (let i = array.length - 1; i > 0; i--) {
			const j = Math.floor(Math.random() * (i + 1));
			[array[i], array[j]] = [array[j], array[i]];
		}
	}

	createBlocks() {
		const totalTextWidth = (this.text.length * (this.CHAR_WIDTH + this.LETTER_SPACING) - this.LETTER_SPACING) * this.blockSize;
		const startX = Math.floor((this.width - totalTextWidth) / 2);
		const startY = Math.floor((this.height - this.CHAR_HEIGHT * this.blockSize) / 2);

		const targets = [];

		for (let i = 0; i < this.text.length; i++) {
			const ch = this.text[i];
			const glyph = this.FONT[ch] || this.FONT[' '];

			for (let row = 0; row < glyph.length; row++) {
				for (let col = 0; col < glyph[row].length; col++) {
					if (glyph[row][col] === '1') {
						const x = startX + (i * (this.CHAR_WIDTH + this.LETTER_SPACING) + col) * this.blockSize;
						const y = startY + row * this.blockSize;
						targets.push({ x, y });
					}
				}
			}
		}

		this.shuffle(targets);
		const now = performance.now();

		for (let i = 0; i < targets.length; i++) {
			const delay = now + Math.random() * 2000;
			this.blocks.push(new this.Block(targets[i].x, targets[i].y, delay, this));
		}
	}

	animate() {
		const now = performance.now();
		this.blocks.forEach(block => block.update(now));
		requestAnimationFrame(() => this.animate());
	}

	Block = class {
		constructor(x, targetY, delay, context) {
			this.context = context;
			this.x = x;
			this.y = 0;
			this.targetY = targetY;
			this.delay = delay;
			this.settled = false;
			this.visible = false;

			this.el = jQuery('<div class="block"></div>')
				.css({
					width: context.blockSize + 'px',
					height: context.blockSize + 'px',
					left: x + 'px',
					opacity: 0
				})
				.appendTo(jQuery(context.container));
		}

		update(time) {
			if (this.settled || time < this.delay) return;

			if (!this.visible) {
				this.el.css('opacity', 1);
				this.visible = true;
			}

			if (!this.fallSpeed) {
				this.fallSpeed = (this.context.blockSize / 4) * (0.7 + Math.random() * 0.6);
			}
			const fallSpeed = this.fallSpeed;

			const nextY = this.y + fallSpeed;

			if (nextY >= this.targetY) {
				this.y = this.targetY;
				this.settled = true;
			} else {
				this.y = nextY;
			}

			this.el.css('top', this.y + 'px');
		}
	};
}
