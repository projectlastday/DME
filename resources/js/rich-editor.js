/**
 * DME Rich Text Editor
 *
 * A lightweight, mobile-friendly rich text editor built on contenteditable.
 * Supports: bold, italic, underline, strikethrough, font-size, highlight colours.
 *
 * Usage: Add [data-rich-editor] to a wrapper element. The module auto-initialises
 * all instances found in the DOM on import.
 *
 * Expected markup inside the wrapper:
 *   [data-re-toolbar]       – the toolbar container (auto-populated)
 *   [data-re-editable]      – the contenteditable area
 *   [data-re-input]         – hidden <input> that syncs the HTML value
 *   [data-re-counter]       – character counter element (optional)
 */

const FONT_SIZES = [
    { label: '10', value: '10px' },
    { label: '12', value: '12px' },
    { label: '14', value: '14px' },
    { label: '16', value: '16px' },
    { label: '18', value: '18px' },
    { label: '20', value: '20px' },
    { label: '24', value: '24px' },
    { label: '28', value: '28px' },
    { label: '32', value: '32px' },
    { label: '36', value: '36px' },
];

const HIGHLIGHT_COLOURS = [
    { label: 'Kuning', value: '#fef08a', icon: '🟡' },
    { label: 'Hijau',  value: '#bbf7d0', icon: '🟢' },
    { label: 'Biru',   value: '#bfdbfe', icon: '🔵' },
    { label: 'Pink',   value: '#fbcfe8', icon: '🩷' },
    { label: 'Oranye', value: '#fed7aa', icon: '🟠' },
    { label: 'Hapus',  value: '',        icon: '🚫' },
];

/**
 * Initialise a single rich-editor instance.
 */
function initEditor(wrapper) {
    if (wrapper.__reInitialised) return;
    wrapper.__reInitialised = true;

    const toolbar  = wrapper.querySelector('[data-re-toolbar]');
    const editable = wrapper.querySelector('[data-re-editable]');
    const input    = wrapper.querySelector('[data-re-input]');
    const counter  = wrapper.querySelector('[data-re-counter]');
    const maxChars = parseInt(wrapper.dataset.reMaxChars || '5000', 10);

    if (!toolbar || !editable || !input) return;

    // ─── Build toolbar ───────────────────────────────────────────────
    toolbar.innerHTML = '';
    toolbar.className = 'dme-re-toolbar';

    // Formatting row
    const fmtRow = el('div', 'dme-re-toolbar__row');

    // Format buttons
    const btnBold   = fmtBtn('B', 'bold', 'Tebal', 'dme-re-btn--bold');
    const btnItalic = fmtBtn('I', 'italic', 'Miring', 'dme-re-btn--italic');
    const btnUline  = fmtBtn('U', 'underline', 'Garis Bawah', 'dme-re-btn--underline');
    const btnStrike = fmtBtn('S', 'strikethrough', 'Coret', 'dme-re-btn--strike');

    fmtRow.append(btnBold, btnItalic, btnUline, btnStrike);

    // Divider
    fmtRow.appendChild(el('div', 'dme-re-divider'));

    // Font-size selector
    const sizeWrap = el('div', 'dme-re-size-wrap');
    const sizeSelect = document.createElement('select');
    sizeSelect.className = 'dme-re-size-select';
    sizeSelect.title = 'Ukuran Font';
    sizeSelect.setAttribute('aria-label', 'Ukuran Font');

    const defaultOpt = document.createElement('option');
    defaultOpt.value = '';
    defaultOpt.textContent = 'Ukuran';
    defaultOpt.disabled = true;
    defaultOpt.selected = true;
    sizeSelect.appendChild(defaultOpt);

    FONT_SIZES.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.value;
        opt.textContent = s.label;
        sizeSelect.appendChild(opt);
    });

    sizeWrap.appendChild(sizeSelect);
    fmtRow.appendChild(sizeWrap);

    // Divider
    fmtRow.appendChild(el('div', 'dme-re-divider'));

    // Highlight colour picker
    const hlWrap = el('div', 'dme-re-hl-wrap');
    const hlToggle = document.createElement('button');
    hlToggle.type = 'button';
    hlToggle.className = 'dme-re-btn dme-re-btn--hl-toggle';
    hlToggle.title = 'Stabilo';
    hlToggle.setAttribute('aria-label', 'Stabilo');
    hlToggle.innerHTML = '<svg class="dme-re-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path class="dme-re-hl-indicator" d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>';

    const hlPalette = el('div', 'dme-re-hl-palette');
    hlPalette.hidden = true;

    HIGHLIGHT_COLOURS.forEach(c => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'dme-re-hl-swatch';
        btn.title = c.label;
        btn.setAttribute('aria-label', 'Stabilo ' + c.label);
        btn.dataset.hlColor = c.value;
        if (c.value) {
            btn.style.backgroundColor = c.value;
        } else {
            btn.textContent = '✕';
            btn.classList.add('dme-re-hl-swatch--clear');
        }
        hlPalette.appendChild(btn);
    });

    hlWrap.append(hlToggle, hlPalette);
    fmtRow.appendChild(hlWrap);

    toolbar.appendChild(fmtRow);

    // ─── Event handlers ──────────────────────────────────────────────

    // Prevent toolbar clicks from stealing focus from the contenteditable
    toolbar.addEventListener('mousedown', (e) => {
        if (e.target.tagName !== 'SELECT') {
            e.preventDefault();
        }
    });

    const handleFormat = (cmd) => {
        exec(cmd);
        updateToolbar();
        editable.focus();
    };

    // Toolbar format buttons
    btnBold.addEventListener('click',   () => handleFormat('bold'));
    btnItalic.addEventListener('click', () => handleFormat('italic'));
    btnUline.addEventListener('click',  () => handleFormat('underline'));
    btnStrike.addEventListener('click', () => handleFormat('strikeThrough'));

    // Font-size select
    sizeSelect.addEventListener('change', () => {
        const size = sizeSelect.value;
        if (!size) return;
        editable.focus();
        applyFontSize(editable, size);
        updateToolbar();
    });

    // Highlight toggle
    hlToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        hlPalette.hidden = !hlPalette.hidden;
    });

    // Highlight colour buttons
    hlPalette.addEventListener('click', (e) => {
        const swatch = e.target.closest('[data-hl-color]');
        if (!swatch) return;
        const color = swatch.dataset.hlColor;
        editable.focus();
        applyHighlight(editable, color);
        hlPalette.hidden = true;
        updateToolbar();
    });

    // Close palette on outside click
    document.addEventListener('click', (e) => {
        if (!hlWrap.contains(e.target)) {
            hlPalette.hidden = true;
        }
    });

    // Sync editable → hidden input
    const syncInput = () => {
        const html = editable.innerHTML;
        // If the editable only contains an empty <br> or is empty, set to empty string
        const isEmpty = html === '' || html === '<br>' || html === '<div><br></div>';
        input.value = isEmpty ? '' : html;

        if (counter) {
            const textLen = (editable.textContent || '').length;
            counter.textContent = `${textLen}/${maxChars}`;
        }
    };

    editable.addEventListener('input', syncInput);
    editable.addEventListener('blur', syncInput);

    // Initial sync: populate editable from input if input has content
    if (input.value.trim()) {
        editable.innerHTML = input.value;
    }

    // Initial counter
    syncInput();

    // Handle paste — keep only allowed formatting
    editable.addEventListener('paste', (e) => {
        e.preventDefault();
        const html = e.clipboardData.getData('text/html');
        const text = e.clipboardData.getData('text/plain');

        if (html) {
            // Insert sanitized HTML (browser will handle basic cleanup)
            const temp = document.createElement('div');
            temp.innerHTML = html;
            // Strip scripts, styles, and most tags
            const cleaned = stripToBasicFormatting(temp);
            document.execCommand('insertHTML', false, cleaned);
        } else {
            document.execCommand('insertText', false, text);
        }
    });

    // Handle Ctrl+A inside the editor (select all within editor only)
    editable.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'a') {
            e.preventDefault();
            const range = document.createRange();
            range.selectNodeContents(editable);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
    });

    // Update toolbar button states based on selection
    function updateToolbar() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;
        
        const range = sel.getRangeAt(0);
        if (!editable.contains(range.commonAncestorContainer)) {
            btnBold.classList.remove('is-active');
            btnItalic.classList.remove('is-active');
            btnUline.classList.remove('is-active');
            btnStrike.classList.remove('is-active');
            sizeSelect.selectedIndex = 0;
            return;
        }

        btnBold.classList.toggle('is-active', document.queryCommandState('bold'));
        btnItalic.classList.toggle('is-active', document.queryCommandState('italic'));
        btnUline.classList.toggle('is-active', document.queryCommandState('underline'));
        btnStrike.classList.toggle('is-active', document.queryCommandState('strikeThrough'));

        let currentSize = '';
        let currentBg = '';
        let node = sel.focusNode;
        while (node && node !== editable) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                if (!currentSize && node.style.fontSize) currentSize = node.style.fontSize;
                if (!currentBg && node.style.backgroundColor) currentBg = node.style.backgroundColor;
            }
            node = node.parentNode;
        }

        const hlIndicator = hlToggle.querySelector('.dme-re-hl-indicator');
        if (hlIndicator) {
            if (currentBg && currentBg !== 'transparent' && currentBg !== 'rgba(0, 0, 0, 0)') {
                hlIndicator.style.stroke = currentBg;
                hlIndicator.style.strokeWidth = '4';
            } else {
                hlIndicator.style.stroke = 'currentColor';
                hlIndicator.style.strokeWidth = '2';
            }
        }
        
        if (currentSize) {
           const match = Array.from(sizeSelect.options).find(opt => opt.value === currentSize);
           if (match) {
               sizeSelect.value = currentSize;
           } else {
               sizeSelect.selectedIndex = 0;
           }
        } else {
            sizeSelect.selectedIndex = 0;
        }
    };

    document.addEventListener('selectionchange', updateToolbar);
    editable.addEventListener('keyup', updateToolbar);
    editable.addEventListener('mouseup', updateToolbar);
    editable.addEventListener('click', updateToolbar);

    // Sync before form submit
    const form = wrapper.closest('form');
    if (form) {
        form.addEventListener('submit', syncInput);
    }

    // Expose API for external scripts
    wrapper.__reAPI = {
        syncInput,
        getTextLength: () => (editable.textContent || '').length,
        getHTML: () => editable.innerHTML,
        setHTML: (html) => { editable.innerHTML = html; syncInput(); },
        focus: () => editable.focus(),
    };
}

// ─── Helpers ─────────────────────────────────────────────────────────

function el(tag, className) {
    const e = document.createElement(tag);
    if (className) e.className = className;
    return e;
}

function fmtBtn(label, cmd, title, extraClass) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'dme-re-btn' + (extraClass ? ' ' + extraClass : '');
    btn.title = title;
    btn.setAttribute('aria-label', title);
    btn.innerHTML = `<span>${label}</span>`;
    return btn;
}

function exec(command) {
    document.execCommand(command, false, null);
}

/**
 * Apply font-size using a wrapping <span> via execCommand + range manipulation.
 */
function applyFontSize(editable, size) {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;

    const range = sel.getRangeAt(0);
    if (!editable.contains(range.commonAncestorContainer)) return;

    if (range.collapsed) {
        const span = document.createElement('span');
        span.style.fontSize = size;
        span.innerHTML = '\u200B';
        range.insertNode(span);
        range.selectNodeContents(span);
        range.collapse(false);
        sel.removeAllRanges();
        sel.addRange(range);
        return;
    }

    // Use fontSize command with a marker value, then replace
    document.execCommand('fontSize', false, '7');

    // Find all <font size="7"> elements and replace with <span style="font-size: ...">
    editable.querySelectorAll('font[size="7"]').forEach(font => {
        const span = document.createElement('span');
        span.style.fontSize = size;
        while (font.firstChild) {
            span.appendChild(font.firstChild);
        }
        font.replaceWith(span);
    });
}

/**
 * Apply highlight (background-color) to the current selection.
 */
function applyHighlight(editable, color) {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;

    const range = sel.getRangeAt(0);
    if (!editable.contains(range.commonAncestorContainer)) return;

    if (range.collapsed) {
        if (color) {
            const span = document.createElement('span');
            span.style.backgroundColor = color;
            span.innerHTML = '\u200B';
            range.insertNode(span);
            range.selectNodeContents(span);
            range.collapse(false);
            sel.removeAllRanges();
            sel.addRange(range);
        } else {
            document.execCommand('hiliteColor', false, 'transparent');
        }
        return;
    }

    if (color) {
        document.execCommand('hiliteColor', false, color);
    } else {
        document.execCommand('hiliteColor', false, 'transparent');
        // Also try to remove background-color from selected spans
        document.execCommand('removeFormat', false, null);
        // Re-execute to only remove hilite (removeFormat is too aggressive)
        // Alternative: just set transparent
        document.execCommand('hiliteColor', false, 'transparent');
    }
}

/**
 * Strip HTML to only basic formatting for paste.
 */
function stripToBasicFormatting(container) {
    const allowed = ['B', 'STRONG', 'I', 'EM', 'U', 'S', 'DEL', 'STRIKE', 'SPAN', 'BR', 'P', 'DIV'];
    const walk = (node) => {
        let html = '';
        node.childNodes.forEach(child => {
            if (child.nodeType === Node.TEXT_NODE) {
                html += child.textContent;
            } else if (child.nodeType === Node.ELEMENT_NODE) {
                const tag = child.tagName;
                if (allowed.includes(tag)) {
                    const inner = walk(child);
                    if (tag === 'BR') {
                        html += '<br>';
                    } else if (tag === 'SPAN') {
                        // Keep only font-size and background-color styles
                        const fontSize = child.style.fontSize;
                        const bgColor  = child.style.backgroundColor;
                        let style = '';
                        if (fontSize) style += `font-size: ${fontSize};`;
                        if (bgColor && bgColor !== 'transparent') style += `background-color: ${bgColor};`;
                        html += style ? `<span style="${style}">${inner}</span>` : inner;
                    } else {
                        html += `<${tag.toLowerCase()}>${inner}</${tag.toLowerCase()}>`;
                    }
                } else {
                    // Unwrap: keep children
                    html += walk(child);
                }
            }
        });
        return html;
    };
    return walk(container);
}

// ─── Auto-init ──────────────────────────────────────────────────────

function initAll() {
    document.querySelectorAll('[data-rich-editor]').forEach(initEditor);
}

// Run on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}

// Export for manual init
window.initRichEditors = initAll;
window.initRichEditor = initEditor;
