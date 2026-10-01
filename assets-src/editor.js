// Replaces every textarea[data-editor="markdown"] with a Toast UI editor (Markdown / WYSIWYG switch)
// and syncs its Markdown back into the textarea on submit.
// Source of public/assets/editor.bundle.js and editor.bundle.css; rebuild with: npm run build
import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';

document.querySelectorAll('textarea[data-editor="markdown"]').forEach((textarea) => {
	const host = document.createElement('div');
	textarea.hidden = true;
	textarea.after(host);

	const editor = new Editor({
		el: host,
		initialValue: textarea.value,
		initialEditType: 'markdown',
		previewStyle: 'tab',
		height: '400px',
		usageStatistics: false,
		toolbarItems: [
			['heading', 'bold', 'italic', 'strike'],
			['quote', 'hr'],
			['ul', 'ol'],
			['table', 'link'],
		],
	});

	textarea.form.addEventListener('submit', () => {
		textarea.value = editor.getMarkdown();
	});
});
