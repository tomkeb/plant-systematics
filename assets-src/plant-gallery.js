// <plant-gallery> web component for the public pages. The server renders it as a row of links (medium size)
// with thumbnails (see PlantGalleryRenderer); clicking one opens all of them in a blueimp lightbox.
// Source of public/assets/plant-gallery.bundle.js and .css; rebuild with: npm run build
import 'blueimp-gallery/js/blueimp-helper.js';
import 'blueimp-gallery/js/blueimp-gallery.js';
import 'blueimp-gallery/css/blueimp-gallery.css';

const LightboxId = 'plant-lightbox';

const ensureLightbox = () => {
	if (document.getElementById(LightboxId) !== null) {
		return;
	}
	document.body.insertAdjacentHTML('beforeend', `
		<div id="${LightboxId}" class="blueimp-gallery blueimp-gallery-controls" role="dialog" aria-modal="true" aria-hidden="true">
			<div class="slides" aria-live="polite"></div>
			<h3 class="title"></h3>
			<a class="prev" role="button" aria-label="Previous" aria-controls="${LightboxId}">‹</a>
			<a class="next" role="button" aria-label="Next" aria-controls="${LightboxId}">›</a>
			<a class="close" role="button" aria-label="Close" aria-controls="${LightboxId}">×</a>
		</div>`);
};

class PlantGallery extends HTMLElement {
	connectedCallback() {
		this.addEventListener('click', (event) => {
			const link = event.target.closest('a');
			if (link === null || !this.contains(link)) {
				return;
			}
			ensureLightbox();
			window.blueimp.Gallery(this.querySelectorAll('a'), {container: `#${LightboxId}`, index: link, event});
		});
	}
}

customElements.define('plant-gallery', PlantGallery);
