/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * TipTap WYSIWYG Editor
 *
 * CRITICAL: The editor is stored in a closure variable (not on `this`)
 * to prevent Alpine's Proxy system from wrapping it. ProseMirror does
 * not work correctly when its Editor object is proxied.
 */

import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';

// Counter for unique extension instance names (prevents duplicate warnings with multiple editors)
let editorInstanceCounter = 0;

document.addEventListener('alpine:init', () => {
    Alpine.data('tiptapEditor', (initialContent = '') => {
        // Store editor OUTSIDE the returned object to avoid Alpine's Proxy
        let editor = null;

        return {
            content: initialContent,
            editorReady: false,
            updatedAt: 0, // Reactive trigger for toolbar state

            init() {
                if (editor) return;
                if (!this.$refs.editor) return;

                const component = this;
                const instanceId = ++editorInstanceCounter;

                editor = new Editor({
                    element: this.$refs.editor,
                    extensions: [
                        StarterKit.configure({
                            // Enable heading, horizontalRule, blockquote
                            heading: {
                                levels: [1, 2, 3],
                            },
                            horizontalRule: true,
                            blockquote: true,
                            // Disable features we don't need for emails
                            codeBlock: false,
                            code: false,
                            strike: false,
                            // Disable Link in StarterKit, add separately below
                            link: false,
                        }),
                        Link.extend({ name: `link-${instanceId}` }).configure({
                            openOnClick: false,
                            HTMLAttributes: {
                                class: 'text-primary-600 underline',
                            },
                        }),
                        Underline.extend({ name: `underline-${instanceId}` }).configure({
                            HTMLAttributes: {},
                        }),
                        TextAlign.extend({ name: `textAlign-${instanceId}` }).configure({
                            types: ['heading', 'paragraph'],
                        }),
                    ],
                    content: this.content,
                    editorProps: {
                        attributes: {
                            class: [
                                'prose prose-sm dark:prose-invert max-w-none min-h-[200px] focus:outline-none p-4',
                                // Lists
                                '[&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_li]:my-1',
                                // Headings
                                '[&_h1]:text-2xl [&_h1]:font-bold [&_h1]:mt-4 [&_h1]:mb-2',
                                '[&_h2]:text-xl [&_h2]:font-bold [&_h2]:mt-3 [&_h2]:mb-2',
                                '[&_h3]:text-lg [&_h3]:font-semibold [&_h3]:mt-2 [&_h3]:mb-1',
                                // Blockquote
                                '[&_blockquote]:border-l-4 [&_blockquote]:border-primary-500 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:my-4 [&_blockquote]:text-secondary-600 dark:[&_blockquote]:text-secondary-400',
                                // Horizontal rule
                                '[&_hr]:my-6 [&_hr]:border-stone-300 dark:[&_hr]:border-secondary-600',
                            ].join(' '),
                        },
                    },
                    onUpdate({ editor: e }) {
                        component.content = e.getHTML();
                        component.syncToHiddenInput();
                        component.updatedAt = Date.now();
                    },
                    onCreate() {
                        component.editorReady = true;
                        component.updatedAt = Date.now();
                    },
                    onSelectionUpdate() {
                        component.updatedAt = Date.now();
                    },
                });

                this.syncToHiddenInput();
            },

            destroy() {
                editor?.destroy();
                editor = null;
                this.editorReady = false;
            },

            // Computed active states (call as methods, not properties)
            isActiveBold() { return editor?.isActive('bold') ?? false; },
            isActiveItalic() { return editor?.isActive('italic') ?? false; },
            isActiveUnderline() { return editor?.isActive('underline') ?? false; },
            isActiveLink() { return editor?.isActive('link') ?? false; },
            isActiveBulletList() { return editor?.isActive('bulletList') ?? false; },
            isActiveOrderedList() { return editor?.isActive('orderedList') ?? false; },
            isActiveBlockquote() { return editor?.isActive('blockquote') ?? false; },
            isActiveHeading(level) { return editor?.isActive('heading', { level }) ?? false; },
            isActiveAlignLeft() { return editor?.isActive({ textAlign: 'left' }) ?? false; },
            isActiveAlignCenter() { return editor?.isActive({ textAlign: 'center' }) ?? false; },
            isActiveAlignRight() { return editor?.isActive({ textAlign: 'right' }) ?? false; },

            // Commands using non-proxied editor
            toggleBold() { editor?.chain().focus().toggleBold().run(); },
            toggleItalic() { editor?.chain().focus().toggleItalic().run(); },
            toggleUnderline() { editor?.chain().focus().toggleUnderline().run(); },
            toggleBulletList() { editor?.chain().focus().toggleBulletList().run(); },
            toggleOrderedList() { editor?.chain().focus().toggleOrderedList().run(); },
            toggleBlockquote() { editor?.chain().focus().toggleBlockquote().run(); },
            
            // Headings
            toggleHeading(level) { editor?.chain().focus().toggleHeading({ level }).run(); },
            
            // Text alignment
            setAlignLeft() { editor?.chain().focus().setTextAlign('left').run(); },
            setAlignCenter() { editor?.chain().focus().setTextAlign('center').run(); },
            setAlignRight() { editor?.chain().focus().setTextAlign('right').run(); },
            
            // Horizontal rule
            insertHorizontalRule() { editor?.chain().focus().setHorizontalRule().run(); },

            setLink() {
                if (!editor) return;
                const url = prompt('Enter URL:', editor.getAttributes('link').href || '');
                if (url === null) return;
                if (url === '') {
                    editor.commands.unsetLink();
                } else {
                    editor.chain().focus().setLink({ href: url }).run();
                }
            },

            unsetLink() { editor?.chain().focus().unsetLink().run(); },

            syncToHiddenInput() {
                if (this.$refs.hiddenInput) {
                    this.$refs.hiddenInput.value = this.content;
                }
            },
        };
    });
});
