# JLM Rich Text Editor API

A powerful, customizable, and reusable rich text editor for web applications. Built with vanilla JavaScript, no external dependencies required.

## Features

- ✅ **No External Dependencies** - Pure vanilla JavaScript
- ✅ **Fully Customizable** - Configurable toolbar, themes, and styling
- ✅ **Multimedia Support** - Images, videos, and audio with resizing and formatting
- ✅ **Advanced Text Formatting** - 35+ fonts, section breaks, quotes, code blocks
- ✅ **Text Wrapping & Alignment** - Full justification, text wrap around media
- ✅ **Image Upload Support** - Drag & drop or click to upload
- ✅ **Multiple Export Formats** - HTML, Text, and more
- ✅ **Event System** - Comprehensive event handling
- ✅ **Theme Support** - Light and dark themes included
- ✅ **Responsive Design** - Works on all screen sizes
- ✅ **Accessibility** - Keyboard navigation and screen reader support

## Quick Start

### 1. Include the Script

```html
<!-- Include Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Include the Rich Text Editor -->
<script src="assets/js/rich-text-editor.js"></script>
```

### 2. Create a Container

```html
<div id="my-editor"></div>
```

### 3. Initialize the Editor

```javascript
const editor = new RichTextEditor('my-editor', {
    height: 400,
    placeholder: 'Start writing your content...',
    uploadUrl: '/upload_image.php'
});
```

## Basic Usage

### Simple Implementation

```javascript
// Basic editor with default settings
const editor = new RichTextEditor('editor-container');

// Get content
const html = editor.getContent();

// Set content
editor.setContent('<p>Hello World!</p>');
```

### Advanced Configuration

```javascript
const editor = new RichTextEditor('editor-container', {
    // Editor height in pixels
    height: 500,
    
    // Placeholder text
    placeholder: 'Start writing your amazing content...',
    
    // Image upload endpoint
    uploadUrl: '/api/upload-image.php',
    
    // Custom toolbar configuration
    toolbar: [
        'bold', 'italic', 'underline', 'strikethrough',
        'separator',
        'alignLeft', 'alignCenter', 'alignRight',
        'separator',
        'bulletList', 'numberedList',
        'separator',
        'fontFamily', 'fontSize', 'textColor', 'backgroundColor',
        'separator',
        'link', 'image',
        'separator',
        'undo', 'redo',
        'separator',
        'clearFormat'
    ],
    
    // Custom fonts
    fonts: [
        { name: 'Arial', value: 'Arial' },
        { name: 'Times New Roman', value: 'Times New Roman' },
        { name: 'Courier New', value: 'Courier New' },
        { name: 'Georgia', value: 'Georgia' },
        { name: 'Verdana', value: 'Verdana' },
        { name: 'Custom Font', value: 'CustomFont' }
    ],
    
    // Custom font sizes
    fontSizes: [
        { name: 'Very Small', value: '1' },
        { name: 'Small', value: '2' },
        { name: 'Normal', value: '3' },
        { name: 'Large', value: '4' },
        { name: 'Very Large', value: '5' },
        { name: 'Huge', value: '6' },
        { name: 'Maximum', value: '7' }
    ],
    
    // Image upload settings
    allowedImageTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
    maxImageSize: 5 * 1024 * 1024, // 5MB
    
    // Content change callback
    onContentChange: (content) => {
        console.log('Content changed:', content);
        // Auto-save or other actions
    }
});
```

## API Reference

### Constructor

```javascript
new RichTextEditor(containerId, options)
```

**Parameters:**
- `containerId` (string): The ID of the HTML element to contain the editor
- `options` (object): Configuration options (optional)

### Methods

#### Content Management

```javascript
// Get HTML content
editor.getContent()

// Set HTML content
editor.setContent(html)

// Get plain text content
editor.getText()

// Set plain text content
editor.setText(text)
```

#### Editor Control

```javascript
// Focus the editor
editor.focus()

// Remove focus from editor
editor.blur()

// Enable editing
editor.enable()

// Disable editing
editor.disable()

// Check if editor is enabled
editor.isEnabled()

// Destroy the editor
editor.destroy()
```

#### Theme Management

```javascript
// Set theme (light or dark)
editor.setTheme('dark')
editor.setTheme('light')
```

#### Event Handling

```javascript
// Listen for events
editor.on('contentChange', (content) => {
    console.log('Content changed:', content);
});

editor.on('imageUpload', (file) => {
    console.log('Image uploaded:', file);
});

// Remove event listener
editor.off('contentChange', callback);

// Emit custom events
editor.emit('customEvent', data);
```

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `height` | number | 400 | Editor height in pixels |
| `placeholder` | string | 'Start writing your content here...' | Placeholder text |
| `uploadUrl` | string | '/upload_image.php' | Image upload endpoint |
| `allowedImageTypes` | array | ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'] | Allowed image MIME types |
| `maxImageSize` | number | 5 * 1024 * 1024 | Maximum image file size in bytes |
| `toolbar` | array | See default toolbar | Toolbar button configuration |
| `fonts` | array | See default fonts | Available font families |
| `fontSizes` | array | See default sizes | Available font sizes |
| `onContentChange` | function | null | Content change callback |

### Toolbar Buttons

Available toolbar buttons:

#### Text Formatting
- `bold` - Bold text
- `italic` - Italic text
- `underline` - Underlined text
- `strikethrough` - Strikethrough text

#### Alignment
- `alignLeft` - Left alignment
- `alignCenter` - Center alignment
- `alignRight` - Right alignment
- `justifyFull` - Full justification

#### Lists
- `bulletList` - Unordered list
- `numberedList` - Ordered list

#### Typography
- `fontFamily` - Font family selector (35+ fonts)
- `fontSize` - Font size selector (20+ sizes)
- `textColor` - Text color picker
- `backgroundColor` - Background color picker

#### Media & Links
- `link` - Insert/edit link
- `image` - Insert image with resizing
- `video` - Insert video with controls
- `audio` - Insert audio with controls

#### Content Blocks
- `quote` - Insert styled quote block
- `code` - Insert code block with syntax highlighting
- `sectionBreak` - Insert horizontal section break

#### Actions
- `undo` - Undo last action
- `redo` - Redo last action
- `clearFormat` - Clear all formatting
- `separator` - Visual separator

## Multimedia Support

The editor supports images, videos, and audio with advanced formatting options:

### Image Upload

The editor supports image uploads with the following features:

#### Server-Side Requirements

Your upload endpoint should:

1. Accept POST requests with file uploads
2. Return JSON response with `url` property
3. Handle errors with `error` property

### Video & Audio

- **Video Support**: Insert videos from URLs (YouTube, Vimeo, direct files)
- **Audio Support**: Insert audio files with player controls
- **Resizing**: Click and drag to resize multimedia elements
- **Alignment**: Left, center, right alignment options
- **Text Wrap**: Wrap text around media elements

### Multimedia Customization

Double-click any multimedia element to open the formatting modal with options for:
- **Size**: Width and height controls
- **Alignment**: Left, center, right positioning
- **Text Wrap**: None, left, or right text wrapping
- **Alt Text**: Accessibility descriptions for images

### Example Upload Endpoint (PHP)

```php
<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$uploadDir = 'uploads/';

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type']);
    exit;
}

// Validate file size (5MB max)
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '_' . time() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['url' => $filepath]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload file']);
}
?>
```

## Events

The editor emits the following events:

- `contentChange` - Fired when content changes
- `focus` - Fired when editor gains focus
- `blur` - Fired when editor loses focus
- `imageUpload` - Fired when image upload starts
- `imageUploadSuccess` - Fired when image upload succeeds
- `imageUploadError` - Fired when image upload fails

## Styling

The editor includes built-in styles but can be customized:

### Custom CSS

```css
/* Custom editor styling */
.jlm-editor {
    border: 2px solid #3b82f6;
    border-radius: 1rem;
}

.jlm-editor-toolbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.jlm-toolbar-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}
```

### Dark Theme

```javascript
// Enable dark theme
editor.setTheme('dark');
```

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## License

This rich text editor is part of the JLM application suite and is available for use in your projects.

## Examples

### Blog Post Editor

```javascript
const blogEditor = new RichTextEditor('blog-content', {
    height: 600,
    placeholder: 'Write your blog post here...',
    uploadUrl: '/api/blog/upload-image.php',
    toolbar: [
        'bold', 'italic', 'underline', 'strikethrough',
        'separator',
        'alignLeft', 'alignCenter', 'alignRight', 'justifyFull',
        'separator',
        'bulletList', 'numberedList',
        'separator',
        'fontFamily', 'fontSize', 'textColor', 'backgroundColor',
        'separator',
        'link', 'image', 'video', 'audio',
        'separator',
        'quote', 'code', 'sectionBreak',
        'separator',
        'undo', 'redo'
    ],
    onContentChange: (content) => {
        // Auto-save draft
        saveDraft(content);
    }
});
```

### Comment Editor

```javascript
const commentEditor = new RichTextEditor('comment-input', {
    height: 150,
    placeholder: 'Write your comment...',
    toolbar: ['bold', 'italic', 'link'],
    onContentChange: (content) => {
        // Enable/disable submit button
        document.getElementById('submit-comment').disabled = !content.trim();
    }
});
```

### Email Editor

```javascript
const emailEditor = new RichTextEditor('email-body', {
    height: 400,
    placeholder: 'Compose your email...',
    toolbar: [
        'bold', 'italic', 'underline', 'strikethrough',
        'separator',
        'alignLeft', 'alignCenter', 'alignRight', 'justifyFull',
        'separator',
        'bulletList', 'numberedList',
        'separator',
        'fontFamily', 'fontSize', 'textColor',
        'separator',
        'link', 'image', 'video',
        'separator',
        'quote', 'sectionBreak'
    ]
});
```

## Troubleshooting

### Common Issues

1. **Editor not loading**: Check if the container element exists
2. **Images not uploading**: Verify upload URL and server configuration
3. **Styling issues**: Ensure Font Awesome is loaded for icons
4. **Content not saving**: Check if form submission includes editor content

### Debug Mode

Enable debug logging:

```javascript
const editor = new RichTextEditor('editor', {
    debug: true,
    onContentChange: (content) => {
        console.log('Editor content:', content);
    }
});
```

## Contributing

To extend the editor:

1. Add new toolbar buttons in the `getButtonIcon()` method
2. Implement new commands in the `executeCommand()` method
3. Add new events in the `bindEvents()` method
4. Update documentation for new features

## Changelog

### Version 1.0.0
- Initial release
- Basic rich text editing
- Image upload support
- Customizable toolbar
- Theme support
- Event system 