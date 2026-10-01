// Replaces every textarea[data-editor="html"] with a Quill editor and syncs its HTML back on submit.
document.querySelectorAll('textarea[data-editor="html"]').forEach((textarea) => {
	const host = document.createElement('div');
	host.innerHTML = textarea.value;
	textarea.hidden = true;
	textarea.after(host);

	const quill = new Quill(host, {
		theme: 'snow',
		modules: {
			toolbar: [
				[{header: [2, 3, false]}],
				['bold', 'italic', 'underline'],
				[{list: 'ordered'}, {list: 'bullet'}],
				['link', 'blockquote'],
				['clean'],
			],
		},
	});

	textarea.form.addEventListener('submit', () => {
		textarea.value = quill.getSemanticHTML();
	});
});
