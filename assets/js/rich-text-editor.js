/**
 * JLM Rich Text Editor API
 * A powerful, customizable, and reusable rich text editor for web applications
 * 
 * Usage:
 * const editor = new RichTextEditor('editor-container', {
 *     height: 400,
 *     placeholder: 'Start writing...',
 *     uploadUrl: '/upload_image.php',
 *     onContentChange: (content) => console.log('Content changed:', content)
 * });
 */

class RichTextEditor {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.container = document.getElementById(containerId);
        this.options = {
            height: 400,
            placeholder: 'Start writing your content here...',
            uploadUrl: '/upload_image.php',
            allowedImageTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
            maxImageSize: 5 * 1024 * 1024, // 5MB
            brandColors: ['#1b2299', '#e4306d', '#f7de7a', '#111827', '#374151'],
            toolbar: [
                'bold', 'italic', 'underline', 'strikethrough',
                'separator',
                'alignLeft', 'alignCenter', 'alignRight', 'justifyFull',
                'separator',
                'bulletList', 'numberedList',
                'separator',
                'heading',
                'separator',
                'brandColors',
                'separator',
                'fontFamily', 'fontSize', 'textColor', 'backgroundColor',
                'separator',
                'link', 'image', 'video', 'audio',
                'separator',
                'quote', 'code', 'sectionBreak',
                'separator',
                'undo', 'redo',
                'separator',
                'clearFormat'
            ],
            fonts: [
                { name: 'Arial', value: 'Arial' },
                { name: 'Times New Roman', value: 'Times New Roman' },
                { name: 'Courier New', value: 'Courier New' },
                { name: 'Georgia', value: 'Georgia' },
                { name: 'Verdana', value: 'Verdana' },
                { name: 'Helvetica', value: 'Helvetica' },
                { name: 'Tahoma', value: 'Tahoma' },
                { name: 'Trebuchet MS', value: 'Trebuchet MS' },
                { name: 'Impact', value: 'Impact' },
                { name: 'Comic Sans MS', value: 'Comic Sans MS' },
                { name: 'Lucida Console', value: 'Lucida Console' },
                { name: 'Palatino', value: 'Palatino' },
                { name: 'Garamond', value: 'Garamond' },
                { name: 'Bookman', value: 'Bookman' },
                { name: 'Avant Garde', value: 'Avant Garde' },
                { name: 'Century Gothic', value: 'Century Gothic' },
                { name: 'Futura', value: 'Futura' },
                { name: 'Optima', value: 'Optima' },
                { name: 'Baskerville', value: 'Baskerville' },
                { name: 'Didot', value: 'Didot' },
                { name: 'Bodoni', value: 'Bodoni' },
                { name: 'Copperplate', value: 'Copperplate' },
                { name: 'Brush Script MT', value: 'Brush Script MT' },
                { name: 'Papyrus', value: 'Papyrus' },
                { name: 'Chalkboard', value: 'Chalkboard' },
                { name: 'Marker Felt', value: 'Marker Felt' },
                { name: 'Bradley Hand', value: 'Bradley Hand' },
                { name: 'Zapfino', value: 'Zapfino' },
                { name: 'Snell Roundhand', value: 'Snell Roundhand' },
                { name: 'Hoefler Text', value: 'Hoefler Text' },
                { name: 'American Typewriter', value: 'American Typewriter' },
                { name: 'Menlo', value: 'Menlo' },
                { name: 'Monaco', value: 'Monaco' },
                { name: 'Consolas', value: 'Consolas' },
                { name: 'Courier', value: 'Courier' },
                { name: 'Monospace', value: 'monospace' },
                { name: 'Serif', value: 'serif' },
                { name: 'Sans-serif', value: 'sans-serif' }
            ],
            fontSizes: [
                { name: 'Very Small', value: '1' },
                { name: 'Small', value: '2' },
                { name: 'Normal', value: '3' },
                { name: 'Large', value: '4' },
                { name: 'Very Large', value: '5' },
                { name: 'Huge', value: '6' },
                { name: 'Maximum', value: '7' },
                { name: '8px', value: '8px' },
                { name: '10px', value: '10px' },
                { name: '12px', value: '12px' },
                { name: '14px', value: '14px' },
                { name: '16px', value: '16px' },
                { name: '18px', value: '18px' },
                { name: '20px', value: '20px' },
                { name: '24px', value: '24px' },
                { name: '28px', value: '28px' },
                { name: '32px', value: '32px' },
                { name: '36px', value: '36px' },
                { name: '48px', value: '48px' },
                { name: '64px', value: '64px' }
            ],
            ...options
        };
        
        this.content = null;
        this.toolbar = null;
        this.isInitialized = false;
        this.selectedElement = null;
        this.multimediaModal = null;
        
        this.init();
    }

    init() {
        if (!this.container) {
            console.error(`RichTextEditor: Container with ID "${this.containerId}" not found`);
            return;
        }

        this.createStyles();
        this.createToolbar();
        this.createContentArea();
        this.bindEvents();
        this.isInitialized = true;
        this._savedRange = null;
        this._toolbarSelection = null;

        // Trigger initial content change event
        this.triggerContentChange();
    }

    createStyles() {
        if (document.getElementById('jlm-rich-text-editor-styles')) {
            return; // Styles already exist
        }

        const styles = document.createElement('style');
        styles.id = 'jlm-rich-text-editor-styles';
        styles.textContent = `
            .jlm-editor {
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                background: white;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                overflow: hidden;
            }
            .jlm-editor-toolbar {
                background-color: #f9fafb;
                border-bottom: 1px solid #d1d5db;
                border-radius: 0.5rem 0.5rem 0 0;
                padding: 0.5rem;
                display: flex;
                flex-wrap: wrap;
                gap: 0.25rem;
                align-items: center;
            }
            .jlm-toolbar-btn {
                padding: 0.5rem;
                border: 1px solid #d1d5db;
                background: white;
                border-radius: 0.25rem;
                cursor: pointer;
                font-size: 0.875rem;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 0.25rem;
                min-width: 32px;
                justify-content: center;
            }
            .jlm-toolbar-btn:hover {
                background: #e5e7eb;
                border-color: #9ca3af;
            }
            .jlm-toolbar-btn.active {
                background: #3b82f6;
                color: white;
                border-color: #3b82f6;
            }
            .jlm-toolbar-separator {
                width: 1px;
                height: 24px;
                background: #d1d5db;
                margin: 0 0.25rem;
            }
            .jlm-editor-content {
                height: ${this.options.height}px;
                min-height: ${this.options.height}px;
                max-height: ${this.options.height}px;
                padding: 1rem;
                outline: none;
                font-size: 16px;
                line-height: 1.6;
                color: #374151;
                overflow-y: auto;
                overscroll-behavior: contain;
            }
            .jlm-editor-content:focus {
                background: #fafafa;
            }
            .jlm-editor-content:empty:before {
                content: "${this.options.placeholder}";
                color: #9ca3af;
                pointer-events: none;
            }
            .jlm-editor-content ul {
                list-style: disc;
                list-style-position: outside;
                margin: 0.75rem 0 0.75rem 1.5rem;
                padding-left: 0.5rem;
            }
            .jlm-editor-content ol {
                list-style: decimal;
                list-style-position: outside;
                margin: 0.75rem 0 0.75rem 1.5rem;
                padding-left: 0.5rem;
            }
            .jlm-editor-content li {
                display: list-item;
                margin: 0.25rem 0;
            }
            .jlm-editor-content h1 {
                font-size: 2rem;
                font-weight: 700;
                line-height: 1.2;
                margin: 0.85rem 0 0.55rem;
            }
            .jlm-editor-content h2 {
                font-size: 1.5rem;
                font-weight: 700;
                line-height: 1.3;
                margin: 0.8rem 0 0.5rem;
            }
            .jlm-editor-content h3 {
                font-size: 1.25rem;
                font-weight: 600;
                line-height: 1.4;
                margin: 0.75rem 0 0.45rem;
            }
            .jlm-color-picker {
                width: 30px;
                height: 30px;
                border: none;
                border-radius: 0.25rem;
                cursor: pointer;
                padding: 0;
            }
            .jlm-font-select {
                padding: 0.25rem;
                border: 1px solid #d1d5db;
                border-radius: 0.25rem;
                font-size: 0.875rem;
                background: white;
                min-width: 120px;
            }
            .jlm-size-select {
                padding: 0.25rem;
                border: 1px solid #d1d5db;
                border-radius: 0.25rem;
                font-size: 0.875rem;
                background: white;
                width: 80px;
            }
            .jlm-link-input {
                padding: 0.25rem;
                border: 1px solid #d1d5db;
                border-radius: 0.25rem;
                font-size: 0.875rem;
                width: 200px;
                background: white;
            }
            .jlm-image-upload {
                display: none;
            }
            .jlm-image-preview {
                max-width: 100%;
                max-height: 200px;
                border-radius: 0.25rem;
                margin-top: 0.5rem;
            }
            
            /* Multimedia Styles */
            .jlm-multimedia {
                position: relative;
                display: inline-block;
                margin: 0.5rem 0;
                max-width: 100%;
            }
            .jlm-multimedia img,
            .jlm-multimedia video,
            .jlm-multimedia audio {
                max-width: 100%;
                height: auto;
                border-radius: 0.25rem;
            }
            .jlm-multimedia-controls {
                position: absolute;
                top: 0.25rem;
                right: 0.25rem;
                background: rgba(0, 0, 0, 0.7);
                border-radius: 0.25rem;
                padding: 0.25rem;
                display: none;
                gap: 0.25rem;
            }
            .jlm-multimedia:hover .jlm-multimedia-controls {
                display: flex;
            }
            .jlm-multimedia-btn {
                background: rgba(255, 255, 255, 0.9);
                border: none;
                border-radius: 0.125rem;
                padding: 0.125rem;
                cursor: pointer;
                font-size: 0.75rem;
                color: #333;
            }
            .jlm-multimedia-btn:hover {
                background: white;
            }
            
            /* Text wrapping options */
            .jlm-wrap-options {
                margin-top: 10px;
            }
            
            .jlm-wrap-category {
                margin-bottom: 15px;
            }
            
            .jlm-wrap-category h4 {
                margin: 0 0 8px 0;
                font-size: 12px;
                color: #666;
                font-weight: 600;
            }
            
            .jlm-wrap-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            
            .jlm-wrap-option {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 8px;
                border: 2px solid #e0e0e0;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s;
                background: white;
            }
            
            .jlm-wrap-option:hover {
                border-color: #1b2299;
                background: #f8f9fa;
            }
            
            .jlm-wrap-option.selected {
                border-color: #1b2299;
                background: linear-gradient(135deg, #1b2299/10, #e4306d/10);
            }
            
            .jlm-wrap-icon {
                width: 40px;
                height: 30px;
                position: relative;
                margin-bottom: 4px;
            }
            
            .icon-object {
                position: absolute;
                width: 20px;
                height: 15px;
                background: linear-gradient(135deg, #1b2299, #e4306d);
                border-radius: 2px;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            
            .icon-lines {
                position: absolute;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            
            .icon-lines .line {
                height: 2px;
                background: #333;
                border-radius: 1px;
            }
            
            /* Inline icon */
            .inline-icon .icon-object {
                border-radius: 50%;
                width: 12px;
                height: 12px;
                background: linear-gradient(135deg, #1b2299, #e4306d);
            }
            
            /* Square icon */
            .square-icon .icon-lines .line {
                margin: 0 2px;
            }
            
            /* Tight icon */
            .tight-icon .icon-lines .line:nth-child(1) {
                margin-right: 8px;
            }
            .tight-icon .icon-lines .line:nth-child(3) {
                margin-left: 8px;
            }
            
            /* Through icon */
            .through-icon .icon-lines .line:nth-child(2) {
                margin: 0 8px;
            }
            
            /* Top and bottom icon */
            .top-bottom-icon .icon-lines .line:nth-child(1),
            .top-bottom-icon .icon-lines .line:nth-child(3) {
                margin: 0 2px;
            }
            .top-bottom-icon .icon-lines .line:nth-child(2) {
                opacity: 0.3;
            }
            
            /* Behind text icon */
            .behind-icon .icon-object {
                opacity: 0.5;
            }
            
            /* Front text icon */
            .front-icon .icon-lines .line {
                opacity: 0.5;
            }
            
            .jlm-wrap-option span {
                font-size: 10px;
                text-align: center;
                color: #333;
                font-weight: 500;
            }
            
            /* Quote Styles */
            .jlm-quote {
                border-left: 4px solid #3b82f6;
                padding-left: 1rem;
                margin: 1rem 0;
                font-style: italic;
                color: #6b7280;
                background: #f9fafb;
                padding: 1rem;
                border-radius: 0.25rem;
            }
            
            /* Code Styles */
            .jlm-code {
                background: #1f2937;
                color: #f9fafb;
                padding: 1rem;
                border-radius: 0.25rem;
                font-family: 'Courier New', monospace;
                font-size: 0.875rem;
                overflow-x: auto;
                margin: 1rem 0;
            }
            
            /* Section Break */
            .jlm-section-break {
                border: none;
                border-top: 2px solid #d1d5db;
                margin: 2rem 0;
                height: 0;
            }
            
            /* Multimedia Modal */
            .jlm-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            }
            .jlm-modal-content {
                background: white;
                border-radius: 0.5rem;
                padding: 1.5rem;
                max-width: 500px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
            }
            .jlm-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
                border-bottom: 1px solid #d1d5db;
            }
            .jlm-modal-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #6b7280;
            }
            .jlm-modal-close:hover {
                color: #374151;
            }
            .jlm-form-group {
                margin-bottom: 1rem;
            }
            .jlm-form-label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
                color: #374151;
            }
            .jlm-form-input {
                width: 100%;
                padding: 0.5rem;
                border: 1px solid #d1d5db;
                border-radius: 0.25rem;
                font-size: 0.875rem;
            }
            .jlm-form-input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            .jlm-form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
            .jlm-form-actions {
                display: flex;
                gap: 0.5rem;
                justify-content: flex-end;
                margin-top: 1rem;
            }
            .jlm-btn {
                padding: 0.5rem 1rem;
                border: none;
                border-radius: 0.25rem;
                cursor: pointer;
                font-size: 0.875rem;
                transition: all 0.2s;
            }
            .jlm-btn-primary {
                background: linear-gradient(135deg, #1b2299, #e4306d);
                color: white;
            }
            .jlm-btn-primary:hover {
                background: linear-gradient(135deg, #151d7a, #c2285a);
            }
            .jlm-btn-secondary {
                background: #6b7280;
                color: white;
            }
            .jlm-btn-secondary:hover {
                background: #4b5563;
            }
            
            .jlm-editor[data-theme="dark"] {
                background: #1f2937;
                border-color: #374151;
            }
            .jlm-editor[data-theme="dark"] .jlm-editor-toolbar {
                background-color: #111827;
                border-color: #374151;
            }
            .jlm-editor[data-theme="dark"] .jlm-editor-content {
                background: #1f2937;
                color: #f9fafb;
            }
            .jlm-editor[data-theme="dark"] .jlm-editor-content:focus {
                background: #111827;
            }
        `;
        document.head.appendChild(styles);
    }

    createToolbar() {
        this.toolbar = document.createElement('div');
        this.toolbar.className = 'jlm-editor-toolbar';
        
        this.options.toolbar.forEach(item => {
            if (item === 'separator') {
                this.toolbar.appendChild(this.createSeparator());
            } else {
                this.toolbar.appendChild(this.createToolbarButton(item));
            }
        });

        this.container.appendChild(this.toolbar);
    }

    createToolbarButton(command) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'jlm-toolbar-btn';
        button.dataset.command = command;
        button.title = this.getButtonTitle(command);

        const icon = this.getButtonIcon(command);
        const preferLabel = ['fontFamily', 'fontSize', 'textColor', 'backgroundColor', 'heading', 'brandColors'].includes(command);
        if (icon && !preferLabel) {
            button.innerHTML = `<i class="${icon}"></i>`;
        } else {
            button.textContent = this.getButtonText(command);
        }

        return button;
    }

    createSeparator() {
        const separator = document.createElement('div');
        separator.className = 'jlm-toolbar-separator';
        return separator;
    }

    getButtonTitle(command) {
        const titles = {
            bold: 'Bold',
            italic: 'Italic',
            underline: 'Underline',
            strikethrough: 'Strikethrough',
            alignLeft: 'Align Left',
            alignCenter: 'Align Center',
            alignRight: 'Align Right',
            justifyFull: 'Justify Full',
            bulletList: 'Bullet List',
            numberedList: 'Numbered List',
            createLink: 'Insert Link',
            insertImage: 'Insert Image',
            insertVideo: 'Insert Video',
            insertAudio: 'Insert Audio',
            quote: 'Insert Quote',
            code: 'Insert Code',
            sectionBreak: 'Insert Section Break',
            heading: 'Heading (P, H1, H2, H3)',
            brandColors: 'Brand Colors',
            undo: 'Undo',
            redo: 'Redo',
            clearFormat: 'Clear Formatting'
        };
        return titles[command] || command;
    }

    getButtonIcon(command) {
        const icons = {
            bold: 'fas fa-bold',
            italic: 'fas fa-italic',
            underline: 'fas fa-underline',
            strikethrough: 'fas fa-strikethrough',
            alignLeft: 'fas fa-align-left',
            alignCenter: 'fas fa-align-center',
            alignRight: 'fas fa-align-right',
            justifyFull: 'fas fa-align-justify',
            bulletList: 'fas fa-list-ul',
            numberedList: 'fas fa-list-ol',
            createLink: 'fas fa-link',
            insertImage: 'fas fa-image',
            insertVideo: 'fas fa-video',
            insertAudio: 'fas fa-volume-up',
            quote: 'fas fa-quote-left',
            code: 'fas fa-code',
            sectionBreak: 'fas fa-minus',
            heading: 'fas fa-heading',
            brandColors: 'fas fa-palette',
            undo: 'fas fa-undo',
            redo: 'fas fa-redo',
            clearFormat: 'fas fa-eraser'
        };
        return icons[command];
    }

    getButtonText(command) {
        const texts = {
            fontFamily: 'Font',
            fontSize: 'Size',
            textColor: 'Color',
            backgroundColor: 'BG',
            heading: 'H',
            brandColors: 'Brand'
        };
        return texts[command] || '';
    }

    createContentArea() {
        this.content = document.createElement('div');
        this.content.className = 'jlm-editor-content';
        this.content.contentEditable = true;
        this.content.innerHTML = '<p><br></p>';
        this.container.appendChild(this.content);
    }

    bindEvents() {
        // Capture phase: save selection BEFORE the browser clears it on toolbar interaction.
        // Without this, only inline commands (bold/italic/underline) tend to work.
        this.toolbar.addEventListener('mousedown', (e) => {
            if (e.target.closest('.jlm-toolbar-btn')) {
                e.preventDefault();
                this._toolbarSelection = this.cloneRangeInEditor();
            }
        }, true);

        this.toolbar.addEventListener('click', (e) => {
            if (e.target.closest('.jlm-toolbar-btn')) {
                const btn = e.target.closest('.jlm-toolbar-btn');
                const command = btn.dataset.command;
                this.executeCommand(command);
            }
        });

        // Content events
        this.content.addEventListener('input', () => {
            this.updateToolbar();
            this.triggerContentChange();
        });

        this.content.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.shiftKey) {
                e.preventDefault();
                this.executeCommand('insertParagraph');
            }
        });

        this.content.addEventListener('paste', (e) => {
            // Handle pasted content
            setTimeout(() => {
                this.triggerContentChange();
            }, 100);
        });

        // Multimedia events
        this.content.addEventListener('click', (e) => {
            if (e.target.closest('.jlm-multimedia')) {
                this.selectedElement = e.target.closest('.jlm-multimedia');
            }
        });

        // Double-click to edit multimedia
        this.content.addEventListener('dblclick', (e) => {
            if (e.target.closest('.jlm-multimedia')) {
                this.openMultimediaModal(e.target.closest('.jlm-multimedia'));
            }
        });
    }

    cloneRangeInEditor() {
        const sel = window.getSelection();
        if (!sel.rangeCount) {
            return null;
        }
        const range = sel.getRangeAt(0);
        const root = range.commonAncestorContainer;
        if (!this.content.contains(root)) {
            return null;
        }
        try {
            return range.cloneRange();
        } catch (e) {
            return null;
        }
    }

    restoreRange(range) {
        if (!range) {
            return;
        }
        this.content.focus();
        const sel = window.getSelection();
        sel.removeAllRanges();
        try {
            sel.addRange(range);
        } catch (e) {
            /* nodes removed — place caret at end */
            this.placeCaretAtEnd();
        }
    }

    placeCaretAtEnd() {
        const sel = window.getSelection();
        const range = document.createRange();
        const p = this.content.querySelector('p:last-child') || this.content;
        range.selectNodeContents(p);
        range.collapse(false);
        sel.removeAllRanges();
        sel.addRange(range);
    }

    /** If there is no selection inside the editor, put caret at end */
    ensureSelectionInEditor() {
        this.content.focus();
        const sel = window.getSelection();
        if (sel.rangeCount && this.content.contains(sel.anchorNode)) {
            return;
        }
        this.placeCaretAtEnd();
    }

    safeSurroundRange(range, wrapper) {
        try {
            range.surroundContents(wrapper);
        } catch (err) {
            const contents = range.extractContents();
            wrapper.appendChild(contents);
            range.insertNode(wrapper);
            range.selectNodeContents(wrapper);
            range.collapse(false);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }

    captureSelectionRange() {
        this._savedRange = this.cloneRangeInEditor();
    }

    restoreSelectionRange() {
        if (!this._savedRange) {
            return;
        }
        const sel = window.getSelection();
        sel.removeAllRanges();
        try {
            sel.addRange(this._savedRange);
        } catch (e) {
            /* range may be invalid if DOM changed */
        }
    }

    normalizeCommand(command) {
        const aliases = {
            link: 'createLink',
            image: 'insertImage',
            video: 'insertVideo',
            audio: 'insertAudio',
        };
        return aliases[command] || command;
    }

    tryExecCommand(cmd, val = null) {
        try {
            return document.execCommand(cmd, false, val);
        } catch (e) {
            return false;
        }
    }

    /** Reliable alignment when justify* execCommand is disabled (common in Chromium). */
    applyParagraphAlign(cssAlign) {
        const sel = window.getSelection();
        if (!sel.rangeCount) {
            return;
        }
        let node = sel.anchorNode;
        if (node.nodeType === Node.TEXT_NODE) {
            node = node.parentElement;
        }
        let cur = node;
        const blockRe = /^(P|DIV|H[1-6]|BLOCKQUOTE|LI|SECTION|ARTICLE|PRE)$/i;
        while (cur && cur !== this.content) {
            if (blockRe.test(cur.tagName) && this.content.contains(cur)) {
                cur.style.textAlign = cssAlign;
                return;
            }
            cur = cur.parentElement;
        }
        const p = this.content.querySelector('p') || this.content;
        if (p) {
            p.style.textAlign = cssAlign;
        }
    }

    applyListFallback(ordered) {
        const sel = window.getSelection();
        if (!sel.rangeCount) {
            return;
        }
        let block = sel.getRangeAt(0).commonAncestorContainer;
        if (block.nodeType === Node.TEXT_NODE) {
            block = block.parentElement;
        }
        block = block && block.closest ? block.closest('p, div, h1, h2, h3, h4, h5, h6, blockquote') : null;
        if (!block || !this.content.contains(block)) {
            return;
        }
        const list = document.createElement(ordered ? 'ol' : 'ul');
        const li = document.createElement('li');
        while (block.firstChild) {
            li.appendChild(block.firstChild);
        }
        list.appendChild(li);
        block.parentNode.replaceChild(list, block);
        sel.removeAllRanges();
        const nr = document.createRange();
        nr.selectNodeContents(li);
        nr.collapse(false);
        sel.addRange(nr);
    }

    executeCommand(command, value = null) {
        command = this.normalizeCommand(command);

        this.content.focus();
        if (this._toolbarSelection) {
            this.restoreRange(this._toolbarSelection);
        }

        const asyncPickerCommands = new Set(['fontFamily', 'fontSize', 'textColor', 'backgroundColor', 'heading', 'brandColors']);
        if (!asyncPickerCommands.has(command)) {
            this._toolbarSelection = null;
        }

        if (this.content.innerHTML === '<p><br></p>' || this.content.innerHTML === '<p></p>') {
            this.content.innerHTML = '<p>Type here...</p>';
            const range = document.createRange();
            const selection = window.getSelection();
            range.selectNodeContents(this.content.querySelector('p'));
            range.collapse(false);
            selection.removeAllRanges();
            selection.addRange(range);
        }

        this.ensureSelectionInEditor();

        switch(command) {
            case 'bold':
            case 'italic':
            case 'underline':
                document.execCommand(command, false, value);
                break;
            case 'strikethrough': {
                if (!this.tryExecCommand('strikeThrough', null)) {
                    const sel = window.getSelection();
                    if (sel.rangeCount > 0 && !sel.isCollapsed && this.content.contains(sel.anchorNode)) {
                        const range = sel.getRangeAt(0);
                        const s = document.createElement('s');
                        this.safeSurroundRange(range, s);
                    }
                }
                break;
            }

            case 'alignLeft':
                this.tryExecCommand('justifyLeft', null);
                this.applyParagraphAlign('left');
                break;
            case 'alignCenter':
                this.tryExecCommand('justifyCenter', null);
                this.applyParagraphAlign('center');
                break;
            case 'alignRight':
                this.tryExecCommand('justifyRight', null);
                this.applyParagraphAlign('right');
                break;
            case 'justifyFull':
                this.tryExecCommand('justifyFull', null);
                this.applyParagraphAlign('justify');
                break;

            case 'bulletList':
                if (!this.tryExecCommand('insertUnorderedList', null) || !this.content.querySelector('ul')) {
                    this.applyListFallback(false);
                }
                break;
            case 'numberedList':
                if (!this.tryExecCommand('insertOrderedList', null) || !this.content.querySelector('ol')) {
                    this.applyListFallback(true);
                }
                break;
                
            // Custom commands
            case 'createLink':
                this.createLink();
                break;
            case 'insertImage':
                this.insertImage();
                break;
            case 'insertVideo':
                this.insertVideo();
                break;
            case 'insertAudio':
                this.insertAudio();
                break;
            case 'insertQuote':
            case 'quote':
                this.insertQuote();
                break;
            case 'insertCode':
            case 'code':
                this.insertCode();
                break;
            case 'insertSectionBreak':
            case 'sectionBreak':
                this.insertSectionBreak();
                break;
            case 'insertParagraph':
                document.execCommand('insertParagraph', false, null);
                break;
            case 'fontFamily':
                this._savedRange = this.cloneRangeInEditor();
                this._toolbarSelection = null;
                this.createFontFamilySelect();
                break;
            case 'fontSize':
                this._savedRange = this.cloneRangeInEditor();
                this._toolbarSelection = null;
                this.createFontSizeSelect();
                break;
            case 'textColor':
                this._savedRange = this.cloneRangeInEditor();
                this._toolbarSelection = null;
                this.createColorPicker('foreColor');
                break;
            case 'backgroundColor':
                this._savedRange = this.cloneRangeInEditor();
                this._toolbarSelection = null;
                this.createColorPicker('hiliteColor');
                break;
            case 'heading':
                this._savedRange = this.cloneRangeInEditor();
                this._toolbarSelection = null;
                this.createHeadingSelect();
                break;
            case 'brandColors':
                this._savedRange = this.cloneRangeInEditor();
                this._toolbarSelection = null;
                this.createBrandColorPalette();
                break;
            case 'undo':
                document.execCommand('undo', false, null);
                break;
            case 'redo':
                document.execCommand('redo', false, null);
                break;
            case 'clearFormat':
                document.execCommand('removeFormat', false, null);
                break;
            default:
                console.warn('Unknown command:', command);
        }
        
        this.updateToolbar();
        this.triggerContentChange();
    }

    createLink() {
        const url = prompt('Enter URL:');
        if (url) {
            this.content.focus();
            const selection = window.getSelection();
            if (selection.rangeCount > 0 && !selection.isCollapsed && this.content.contains(selection.anchorNode)) {
                const range = selection.getRangeAt(0);
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                this.safeSurroundRange(range, link);
            } else {
                // If no selection, create a link with the URL as text
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.textContent = url;
                this.insertElement(link);
            }
        }
    }

    insertImage() {
        const rangeSnapshot = this.cloneRangeInEditor();
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.className = 'jlm-image-upload';
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                this.uploadImage(file, rangeSnapshot);
            }
        };
        input.click();
    }

    insertVideo() {
        const url = prompt('Enter video URL (YouTube, Vimeo, or direct video file):');
        if (url) {
            this.insertMultimedia(url, 'video');
        }
    }

    insertAudio() {
        const url = prompt('Enter audio URL:');
        if (url) {
            this.insertMultimedia(url, 'audio');
        }
    }

    insertQuote() {
        const sel = window.getSelection();
        if (sel.rangeCount > 0 && !sel.isCollapsed && this.content.contains(sel.anchorNode)) {
            const range = sel.getRangeAt(0);
            const quote = document.createElement('blockquote');
            quote.className = 'jlm-quote';
            this.safeSurroundRange(range, quote);
        } else {
            const text = prompt('Enter quote text:');
            if (text) {
                const quote = document.createElement('blockquote');
                quote.className = 'jlm-quote';
                quote.innerHTML = text;
                this.insertElement(quote);
            }
        }
    }

    insertCode() {
        const sel = window.getSelection();
        if (sel.rangeCount > 0 && !sel.isCollapsed && this.content.contains(sel.anchorNode)) {
            const range = sel.getRangeAt(0);
            const codeElement = document.createElement('pre');
            codeElement.className = 'jlm-code';
            this.safeSurroundRange(range, codeElement);
        } else {
            const code = prompt('Enter code:');
            if (code) {
                const codeElement = document.createElement('pre');
                codeElement.className = 'jlm-code';
                codeElement.textContent = code;
                this.insertElement(codeElement);
            }
        }
    }

    insertSectionBreak() {
        const hr = document.createElement('hr');
        hr.className = 'jlm-section-break';
        this.insertElement(hr);
    }

    insertMultimedia(url, type) {
        const wrapper = document.createElement('div');
        wrapper.className = 'jlm-multimedia';
        
        let element;
        if (type === 'video') {
            element = document.createElement('video');
            element.controls = true;
            element.style.maxWidth = '100%';
            element.style.height = 'auto';
        } else if (type === 'audio') {
            element = document.createElement('audio');
            element.controls = true;
        }
        
        element.src = url;
        
        // Add controls
        const controls = document.createElement('div');
        controls.className = 'jlm-multimedia-controls';
        controls.innerHTML = `
            <button class="jlm-multimedia-btn" onclick="this.closest('.jlm-multimedia').querySelector('${type}').style.width='100%'">100%</button>
            <button class="jlm-multimedia-btn" onclick="this.closest('.jlm-multimedia').querySelector('${type}').style.width='75%'">75%</button>
            <button class="jlm-multimedia-btn" onclick="this.closest('.jlm-multimedia').querySelector('${type}').style.width='50%'">50%</button>
            <button class="jlm-multimedia-btn" onclick="this.closest('.jlm-multimedia').querySelector('${type}').style.width='25%'">25%</button>
        `;
        
        wrapper.appendChild(element);
        wrapper.appendChild(controls);
        this.insertElement(wrapper);
    }

    insertElement(element) {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(element);
            range.setStartAfter(element);
            range.setEndAfter(element);
            selection.removeAllRanges();
            selection.addRange(range);
        } else {
            this.content.appendChild(element);
        }
    }

    openMultimediaModal(element) {
        this.selectedElement = element;
        this.createMultimediaModal();
    }

    createMultimediaModal() {
        if (this.multimediaModal) {
            this.multimediaModal.remove();
        }

        this.multimediaModal = document.createElement('div');
        this.multimediaModal.className = 'jlm-modal';
        this.multimediaModal.setAttribute('data-editor-instance', '');
        // Store reference to this editor instance
        this.multimediaModal.editorInstance = this;
        
        const element = this.selectedElement;
        const mediaElement = element.querySelector('img, video, audio');
        
        this.multimediaModal.innerHTML = `
            <div class="jlm-modal-content">
                <div class="jlm-modal-header">
                    <h3>Edit Multimedia</h3>
                    <button class="jlm-modal-close" onclick="this.closest('.jlm-modal').remove()">&times;</button>
                </div>
                
                <div class="jlm-form-group">
                    <label class="jlm-form-label">Source URL</label>
                    <input type="url" class="jlm-form-input" id="multimedia-url" value="${mediaElement.src || ''}" placeholder="Enter URL">
                </div>
                
                <div class="jlm-form-row">
                    <div class="jlm-form-group">
                        <label class="jlm-form-label">Width</label>
                        <input type="text" class="jlm-form-input" id="multimedia-width" value="${mediaElement.style.width || '100%'}" placeholder="e.g., 100%, 500px">
                    </div>
                    <div class="jlm-form-group">
                        <label class="jlm-form-label">Height</label>
                        <input type="text" class="jlm-form-input" id="multimedia-height" value="${mediaElement.style.height || 'auto'}" placeholder="e.g., auto, 300px">
                    </div>
                </div>
                
                <div class="jlm-form-group">
                    <label class="jlm-form-label">Text Wrapping</label>
                    <div class="jlm-wrap-options">
                        <div class="jlm-wrap-category">
                            <h4>In Line with Text</h4>
                            <div class="jlm-wrap-option" data-wrap="inline">
                                <div class="jlm-wrap-icon inline-icon">
                                    <div class="icon-object"></div>
                                    <div class="icon-lines">
                                        <div class="line"></div>
                                        <div class="line"></div>
                                        <div class="line"></div>
                                    </div>
                                </div>
                                <span>Inline</span>
                            </div>
                        </div>
                        
                        <div class="jlm-wrap-category">
                            <h4>With Text Wrapping</h4>
                            <div class="jlm-wrap-grid">
                                <div class="jlm-wrap-option" data-wrap="square">
                                    <div class="jlm-wrap-icon square-icon">
                                        <div class="icon-object"></div>
                                        <div class="icon-lines">
                                            <div class="line"></div>
                                            <div class="line"></div>
                                            <div class="line"></div>
                                        </div>
                                    </div>
                                    <span>Square</span>
                                </div>
                                
                                <div class="jlm-wrap-option" data-wrap="tight">
                                    <div class="jlm-wrap-icon tight-icon">
                                        <div class="icon-object"></div>
                                        <div class="icon-lines">
                                            <div class="line"></div>
                                            <div class="line"></div>
                                            <div class="line"></div>
                                        </div>
                                    </div>
                                    <span>Tight</span>
                                </div>
                                
                                <div class="jlm-wrap-option" data-wrap="through">
                                    <div class="jlm-wrap-icon through-icon">
                                        <div class="icon-object"></div>
                                        <div class="icon-lines">
                                            <div class="line"></div>
                                            <div class="line"></div>
                                            <div class="line"></div>
                                        </div>
                                    </div>
                                    <span>Through</span>
                                </div>
                                
                                <div class="jlm-wrap-option" data-wrap="top-bottom">
                                    <div class="jlm-wrap-icon top-bottom-icon">
                                        <div class="icon-object"></div>
                                        <div class="icon-lines">
                                            <div class="line"></div>
                                            <div class="line"></div>
                                            <div class="line"></div>
                                        </div>
                                    </div>
                                    <span>Top and Bottom</span>
                                </div>
                                
                                <div class="jlm-wrap-option" data-wrap="behind">
                                    <div class="jlm-wrap-icon behind-icon">
                                        <div class="icon-object"></div>
                                        <div class="icon-lines">
                                            <div class="line"></div>
                                            <div class="line"></div>
                                            <div class="line"></div>
                                        </div>
                                    </div>
                                    <span>Behind Text</span>
                                </div>
                                
                                <div class="jlm-wrap-option" data-wrap="front">
                                    <div class="jlm-wrap-icon front-icon">
                                        <div class="icon-object"></div>
                                        <div class="icon-lines">
                                            <div class="line"></div>
                                            <div class="line"></div>
                                            <div class="line"></div>
                                        </div>
                                    </div>
                                    <span>In Front of Text</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="jlm-form-row">
                    <div class="jlm-form-group">
                        <label class="jlm-form-label">Alignment</label>
                        <select class="jlm-form-input" id="multimedia-align">
                            <option value="left" ${element.style.textAlign === 'left' ? 'selected' : ''}>Left</option>
                            <option value="center" ${element.style.textAlign === 'center' ? 'selected' : ''}>Center</option>
                            <option value="right" ${element.style.textAlign === 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                    <div class="jlm-form-group">
                        <label class="jlm-form-label">Float Direction</label>
                        <select class="jlm-form-input" id="multimedia-float">
                            <option value="none" ${element.style.float === 'none' || !element.style.float ? 'selected' : ''}>None</option>
                            <option value="left" ${element.style.float === 'left' ? 'selected' : ''}>Left</option>
                            <option value="right" ${element.style.float === 'right' ? 'selected' : ''}>Right</option>
                        </select>
                    </div>
                </div>
                
                <div class="jlm-form-group">
                    <label class="jlm-form-label">Alt Text (for images)</label>
                    <input type="text" class="jlm-form-input" id="multimedia-alt" value="${mediaElement.alt || ''}" placeholder="Alternative text for accessibility">
                </div>
                
                <div class="jlm-form-actions">
                    <button class="jlm-btn jlm-btn-secondary" id="cancel-btn">Cancel</button>
                    <button class="jlm-btn jlm-btn-primary" id="apply-btn">Apply</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(this.multimediaModal);
        
        // Add event listeners for wrapping options
        this.multimediaModal.querySelectorAll('.jlm-wrap-option').forEach(option => {
            option.addEventListener('click', () => {
                // Remove selected class from all options
                this.multimediaModal.querySelectorAll('.jlm-wrap-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                // Add selected class to clicked option
                option.classList.add('selected');
            });
        });
        
        // Add event listeners for buttons
        this.multimediaModal.querySelector('#cancel-btn').addEventListener('click', () => {
            this.multimediaModal.remove();
        });
        
        this.multimediaModal.querySelector('#apply-btn').addEventListener('click', () => {
            this.applyMultimediaChanges();
        });
        
        // Set initial selected state based on current wrapping
        const currentWrap = element.style.float || 'none';
        const currentDisplay = element.style.display || 'block';
        let currentWrapType = 'inline';
        
        if (currentDisplay === 'inline' || currentDisplay === 'inline-block') {
            currentWrapType = 'inline';
        } else if (currentWrap === 'left') {
            currentWrapType = 'square';
        } else if (currentWrap === 'right') {
            currentWrapType = 'square';
        } else {
            currentWrapType = 'square';
        }
        
        const selectedOption = this.multimediaModal.querySelector(`[data-wrap="${currentWrapType}"]`);
        if (selectedOption) {
            selectedOption.classList.add('selected');
        }
    }

    applyMultimediaChanges() {
        if (!this.selectedElement || !this.multimediaModal) {
            return;
        }
        
        const element = this.selectedElement;
        const mediaElement = element.querySelector('img, video, audio');
        
        // Apply changes
        const url = document.getElementById('multimedia-url').value;
        const width = document.getElementById('multimedia-width').value;
        const height = document.getElementById('multimedia-height').value;
        const align = document.getElementById('multimedia-align').value;
        const float = document.getElementById('multimedia-float').value;
        const alt = document.getElementById('multimedia-alt').value;
        
        // Get selected wrapping option
        const selectedWrapOption = this.multimediaModal.querySelector('.jlm-wrap-option.selected');
        const wrapType = selectedWrapOption ? selectedWrapOption.dataset.wrap : 'square';
        
        if (url) mediaElement.src = url;
        if (width) mediaElement.style.width = width;
        if (height) mediaElement.style.height = height;
        if (alt) mediaElement.alt = alt;
        
        // Apply wrapping based on selected option
        switch (wrapType) {
            case 'inline':
                element.style.display = 'inline';
                element.style.float = 'none';
                element.style.margin = '0 5px';
                break;
            case 'square':
                element.style.display = 'block';
                element.style.float = float;
                element.style.margin = '10px 0';
                break;
            case 'tight':
                element.style.display = 'block';
                element.style.float = float;
                element.style.margin = '10px 0';
                element.style.shapeOutside = 'margin-box';
                break;
            case 'through':
                element.style.display = 'block';
                element.style.float = float;
                element.style.margin = '10px 0';
                element.style.shapeOutside = 'content-box';
                break;
            case 'top-bottom':
                element.style.display = 'block';
                element.style.float = 'none';
                element.style.margin = '10px 0';
                element.style.textAlign = 'center';
                break;
            case 'behind':
                element.style.display = 'block';
                element.style.float = float;
                element.style.margin = '10px 0';
                element.style.zIndex = '-1';
                element.style.position = 'relative';
                break;
            case 'front':
                element.style.display = 'block';
                element.style.float = float;
                element.style.margin = '10px 0';
                element.style.zIndex = '1';
                element.style.position = 'relative';
                break;
            default:
                element.style.display = 'block';
                element.style.float = float;
                element.style.margin = '10px 0';
        }
        
        // Apply alignment
        if (wrapType !== 'top-bottom') {
            element.style.textAlign = align;
        }
        
        // Clear float if needed
        if (float !== 'none') {
            const clearDiv = document.createElement('div');
            clearDiv.style.clear = 'both';
            element.parentNode.insertBefore(clearDiv, element.nextSibling);
        }
        
        this.multimediaModal.remove();
        this.multimediaModal = null;
        this.selectedElement = null;
        this.triggerContentChange();
    }

    createFontFamilySelect() {
        const select = document.createElement('select');
        select.className = 'jlm-font-select';
        
        select.innerHTML = '<option value="">Font</option>';
        this.options.fonts.forEach(font => {
            select.innerHTML += `<option value="${font.value}" style="font-family: ${font.value}">${font.name}</option>`;
        });

        select.addEventListener('change', (e) => {
            const font = e.target.value;
            if (font) {
                this.restoreSelectionRange();
                this.ensureSelectionInEditor();
                const selection = window.getSelection();
                if (selection.rangeCount > 0 && !selection.isCollapsed && this.content.contains(selection.anchorNode)) {
                    const range = selection.getRangeAt(0);
                    const span = document.createElement('span');
                    span.style.fontFamily = font;
                    this.safeSurroundRange(range, span);
                } else {
                    // If no selection, apply to the current paragraph
                    const currentElement = this.getCurrentElement();
                    if (currentElement) {
                        currentElement.style.fontFamily = font;
                    }
                }
            }
            this.updateToolbar();
            this.triggerContentChange();
        });

        // Replace the button temporarily
        const button = this.toolbar.querySelector('[data-command="fontFamily"]');
        button.style.display = 'none';
        button.parentNode.insertBefore(select, button.nextSibling);
        
        setTimeout(() => {
            select.remove();
            button.style.display = 'flex';
        }, 15000);
    }

    createFontSizeSelect() {
        const select = document.createElement('select');
        select.className = 'jlm-size-select';
        
        select.innerHTML = '<option value="">Size</option>';
        this.options.fontSizes.forEach(size => {
            select.innerHTML += `<option value="${size.value}">${size.name}</option>`;
        });

        select.addEventListener('change', (e) => {
            const size = e.target.value;
            if (size) {
                this.restoreSelectionRange();
                this.ensureSelectionInEditor();
                const selection = window.getSelection();
                if (selection.rangeCount > 0 && !selection.isCollapsed && this.content.contains(selection.anchorNode)) {
                    const range = selection.getRangeAt(0);
                    const span = document.createElement('span');
                    const legacyPx = { '1': '10px', '2': '13px', '3': '16px', '4': '18px', '5': '24px', '6': '32px', '7': '48px' };
                    span.style.fontSize = legacyPx[size] || size;
                    this.safeSurroundRange(range, span);
                } else {
                    // If no selection, apply to the current paragraph
                    const currentElement = this.getCurrentElement();
                    if (currentElement) {
                        const legacyPx = { '1': '10px', '2': '13px', '3': '16px', '4': '18px', '5': '24px', '6': '32px', '7': '48px' };
                        currentElement.style.fontSize = legacyPx[size] || size;
                    }
                }
            }
            this.updateToolbar();
            this.triggerContentChange();
        });

        // Replace the button temporarily
        const button = this.toolbar.querySelector('[data-command="fontSize"]');
        button.style.display = 'none';
        button.parentNode.insertBefore(select, button.nextSibling);
        
        setTimeout(() => {
            select.remove();
            button.style.display = 'flex';
        }, 15000);
    }

    getCurrentElement() {
        const selection = window.getSelection();
        if (!selection.rangeCount) {
            return null;
        }
        let node = selection.anchorNode;
        if (node.nodeType === Node.TEXT_NODE) {
            node = node.parentElement;
        }
        while (node && node !== this.content) {
            if (node.parentElement === this.content) {
                return node;
            }
            node = node.parentElement;
        }
        return this.content.querySelector('p') || this.content;
    }

    createColorPicker(command) {
        const picker = document.createElement('input');
        picker.type = 'color';
        picker.className = 'jlm-color-picker';
        picker.value = command === 'foreColor' ? '#000000' : '#ffffff';

        picker.addEventListener('change', (e) => {
            const color = e.target.value;
            if (color) {
                this.restoreSelectionRange();
                this.ensureSelectionInEditor();
                const selection = window.getSelection();
                if (selection.rangeCount > 0 && !selection.isCollapsed && this.content.contains(selection.anchorNode)) {
                    const range = selection.getRangeAt(0);
                    const span = document.createElement('span');
                    if (command === 'foreColor') {
                        span.style.color = color;
                    } else {
                        span.style.backgroundColor = color;
                    }
                    this.safeSurroundRange(range, span);
                } else {
                    // If no selection, apply to the current paragraph
                    const currentElement = this.getCurrentElement();
                    if (currentElement) {
                        if (command === 'foreColor') {
                            currentElement.style.color = color;
                        } else {
                            currentElement.style.backgroundColor = color;
                        }
                    }
                }
            }
            this.updateToolbar();
            this.triggerContentChange();
        });

        picker.click();
    }

    applyHeadingFallback(tagName) {
        const selection = window.getSelection();
        if (!selection.rangeCount) {
            return;
        }

        const range = selection.getRangeAt(0);
        let node = range.commonAncestorContainer;
        if (node.nodeType === Node.TEXT_NODE) {
            node = node.parentElement;
        }
        const block = node && node.closest
            ? node.closest('p, div, h1, h2, h3, h4, h5, h6, blockquote')
            : null;
        if (!block || !this.content.contains(block)) {
            return;
        }

        const target = document.createElement(tagName.toLowerCase());
        target.innerHTML = block.innerHTML;
        target.style.cssText = block.style.cssText;
        block.parentNode.replaceChild(target, block);

        const newRange = document.createRange();
        newRange.selectNodeContents(target);
        newRange.collapse(false);
        selection.removeAllRanges();
        selection.addRange(newRange);
    }

    createHeadingSelect() {
        const select = document.createElement('select');
        select.className = 'jlm-size-select';
        select.innerHTML = `
            <option value="">Headings</option>
            <option value="P">Paragraph</option>
            <option value="H1">H1</option>
            <option value="H2">H2</option>
            <option value="H3">H3</option>
        `;

        select.addEventListener('change', (e) => {
            const heading = (e.target.value || '').toUpperCase();
            if (heading) {
                this.restoreSelectionRange();
                this.ensureSelectionInEditor();
                const formatValue = `<${heading.toLowerCase()}>`;
                if (!this.tryExecCommand('formatBlock', formatValue)) {
                    this.applyHeadingFallback(heading);
                }
            }
            this.updateToolbar();
            this.triggerContentChange();
        });

        const button = this.toolbar.querySelector('[data-command="heading"]');
        if (button) {
            button.style.display = 'none';
            button.parentNode.insertBefore(select, button.nextSibling);
            setTimeout(() => {
                select.remove();
                button.style.display = 'flex';
            }, 15000);
        }
    }

    createBrandColorPalette() {
        this.restoreSelectionRange();
        this.ensureSelectionInEditor();

        const colors = Array.isArray(this.options.brandColors) && this.options.brandColors.length
            ? this.options.brandColors
            : ['#1b2299', '#e4306d'];
        const picker = document.createElement('div');
        picker.style.display = 'flex';
        picker.style.alignItems = 'center';
        picker.style.flexWrap = 'wrap';
        picker.style.gap = '8px';
        picker.style.padding = '8px';
        picker.style.border = '1px solid #d1d5db';
        picker.style.borderRadius = '8px';
        picker.style.background = '#ffffff';
        picker.style.boxShadow = '0 4px 10px rgba(0,0,0,0.08)';

        const triggerBtn = this.toolbar.querySelector('[data-command="brandColors"]');
        if (!triggerBtn || !triggerBtn.parentNode) {
            return;
        }

        colors.forEach((hex) => {
            const swatch = document.createElement('button');
            swatch.type = 'button';
            swatch.title = `Apply ${hex}`;
            swatch.style.width = '24px';
            swatch.style.height = '24px';
            swatch.style.borderRadius = '50%';
            swatch.style.border = '1px solid #cbd5e1';
            swatch.style.background = hex;
            swatch.style.cursor = 'pointer';
            swatch.addEventListener('click', () => {
                this.restoreSelectionRange();
                this.ensureSelectionInEditor();
                const selection = window.getSelection();
                if (selection.rangeCount > 0 && !selection.isCollapsed && this.content.contains(selection.anchorNode)) {
                    const range = selection.getRangeAt(0);
                    const span = document.createElement('span');
                    span.style.color = hex;
                    this.safeSurroundRange(range, span);
                } else {
                    const currentElement = this.getCurrentElement();
                    if (currentElement) {
                        currentElement.style.color = hex;
                    }
                }
                this.updateToolbar();
                this.triggerContentChange();
            });
            picker.appendChild(swatch);
        });

        triggerBtn.style.display = 'none';
        triggerBtn.parentNode.insertBefore(picker, triggerBtn.nextSibling);
        setTimeout(() => {
            picker.remove();
            triggerBtn.style.display = 'flex';
        }, 6000);
    }

    async uploadImage(file, rangeSnapshot = null) {
        // Validate file type
        if (!this.options.allowedImageTypes.includes(file.type)) {
            alert('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
            return;
        }

        // Validate file size
        if (file.size > this.options.maxImageSize) {
            alert(`File too large. Maximum size is ${this.options.maxImageSize / (1024 * 1024)}MB.`);
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch(this.options.uploadUrl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.url) {
                this.content.focus();
                if (rangeSnapshot) {
                    try {
                        const sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(rangeSnapshot);
                    } catch (e) {
                        this.placeCaretAtEnd();
                    }
                }

                // Create multimedia wrapper for image
                const wrapper = document.createElement('div');
                wrapper.className = 'jlm-multimedia';
                
                const img = document.createElement('img');
                img.src = result.url;
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                
                // Add controls
                const controls = document.createElement('div');
                controls.className = 'jlm-multimedia-controls';
                controls.innerHTML = `
                    <button class="jlm-multimedia-btn" onclick="this.closest('.jlm-multimedia').querySelector('img').style.width='100%'">100%</button>
                    <button class="jlm-multimedia-btn" onclick="this.closest('.jlm-multimedia').querySelector('img').style.width='75%'">75%</button>
                    <button class="jlm-multimedia-btn" onclick="this.closest('.jlm-multimedia').querySelector('img').style.width='50%'">50%</button>
                    <button class="jlm-multimedia-btn" onclick="this.closest('.jlm-multimedia').querySelector('img').style.width='25%'">25%</button>
                `;
                
                wrapper.appendChild(img);
                wrapper.appendChild(controls);
                this.insertElement(wrapper);
            } else {
                alert('Image upload failed: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            alert('Image upload failed: ' + error.message);
        }
    }

    updateToolbar() {
        // Update button states based on current selection
        const buttons = this.toolbar.querySelectorAll('.jlm-toolbar-btn');
        buttons.forEach(btn => {
            const command = btn.dataset.command;
            if (command === 'strikethrough') {
                btn.classList.toggle('active', document.queryCommandState('strikeThrough'));
            } else if (['bold', 'italic', 'underline'].includes(command)) {
                btn.classList.toggle('active', document.queryCommandState(command));
            }
        });
    }

    triggerContentChange() {
        if (this.options.onContentChange) {
            this.options.onContentChange(this.getContent());
        }
    }

    // Public API Methods
    getContent() {
        return this.content.innerHTML;
    }

    setContent(html) {
        if (this.content) {
            this.content.innerHTML = html || '<p><br></p>';
            this.triggerContentChange();
        }
    }

    getText() {
        return this.content.textContent || this.content.innerText || '';
    }

    setText(text) {
        if (this.content) {
            this.content.textContent = text;
            this.triggerContentChange();
        }
    }

    focus() {
        if (this.content) {
            this.content.focus();
        }
    }

    blur() {
        if (this.content) {
            this.content.blur();
        }
    }

    isEnabled() {
        return this.content && !this.content.contentEditable === 'false';
    }

    enable() {
        if (this.content) {
            this.content.contentEditable = 'true';
        }
    }

    disable() {
        if (this.content) {
            this.content.contentEditable = 'false';
        }
    }

    setTheme(theme) {
        if (this.container) {
            this.container.setAttribute('data-theme', theme);
        }
    }

    destroy() {
        if (this.container && this.isInitialized) {
            this.container.innerHTML = '';
            this.isInitialized = false;
        }
        if (this.multimediaModal) {
            this.multimediaModal.remove();
        }
    }

    // Event listeners
    on(event, callback) {
        if (!this.eventListeners) {
            this.eventListeners = {};
        }
        if (!this.eventListeners[event]) {
            this.eventListeners[event] = [];
        }
        this.eventListeners[event].push(callback);
    }

    off(event, callback) {
        if (this.eventListeners && this.eventListeners[event]) {
            const index = this.eventListeners[event].indexOf(callback);
            if (index > -1) {
                this.eventListeners[event].splice(index, 1);
            }
        }
    }

    emit(event, data) {
        if (this.eventListeners && this.eventListeners[event]) {
            this.eventListeners[event].forEach(callback => {
                callback(data);
            });
        }
    }
}

// Export for different module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RichTextEditor;
} else if (typeof define === 'function' && define.amd) {
    define([], function() { return RichTextEditor; });
} else if (typeof window !== 'undefined') {
    window.RichTextEditor = RichTextEditor;
} 
