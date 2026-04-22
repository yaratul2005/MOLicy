/**
 * Rich Text Editor System
 * Features markdown injection and basic UI behaviors
 */

document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('post-editor');
    const buttons = document.querySelectorAll('.editor-btn');

    if (!editor) return;

    buttons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const command = btn.getAttribute('data-command');
            insertMarkdown(command);
        });
    });

    function insertMarkdown(command) {
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const text = editor.value;
        const selected = text.substring(start, end);

        let replace = selected;
        let cursorOffset = 0;

        if (command === 'image') {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = async e => {
                const file = e.target.files[0];
                if (!file) return;
                
                const formData = new FormData();
                formData.append('image', file);

                const btn = document.querySelector('[data-command="image"]');
                const oldText = btn.textContent;
                btn.textContent = 'Uploading...';
                
                try {
                    const res = await fetch('/media/upload', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.url) {
                        const imgMd = `![uploaded image](${data.url})\n`;
                        editor.value = text.substring(0, start) + imgMd + text.substring(end);
                        editor.focus();
                        editor.setSelectionRange(start + imgMd.length, start + imgMd.length);
                    } else {
                        alert(data.error || 'Upload failed');
                    }
                } catch (err) {
                    alert('Upload failed');
                } finally {
                    btn.textContent = oldText;
                }
            };
            input.click();
            return;
        }

        switch (command) {
            case 'bold':
                replace = `**${selected || 'bold text'}**`;
                cursorOffset = selected ? 0 : 2;
                break;
            case 'italic':
                replace = `*${selected || 'italic text'}*`;
                cursorOffset = selected ? 0 : 1;
                break;
            case 'link':
                replace = `[${selected || 'link text'}](https://url)`;
                cursorOffset = selected ? 0 : 1;
                break;
        }

        editor.value = text.substring(0, start) + replace + text.substring(end);
        
        // Restore cursor position
        editor.focus();
        if (cursorOffset > 0) {
            editor.setSelectionRange(start + cursorOffset, start + cursorOffset + (selected ? 0 : replace.length - cursorOffset * 2));
        } else {
            editor.setSelectionRange(start, start + replace.length);
        }
    }
});
