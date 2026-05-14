'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Premium Image Upload Component
 *
 * Modern drag-and-drop image upload with smooth animations and elegant UX.
 * Each component allows users to upload, preview, and remove images.
 *
 * Component structure:
 * - .dropzone-label: Main container wrapping the upload area
 * - .dropzone-file: Hidden file input for selection
 * - .image-preview: Displays the uploaded/existing image
 * - .image-wrapper: Container for the image preview
 * - .remove-image: Container for action buttons
 * - .delete-image-btn: Button to remove the uploaded image
 * - .change-image-btn: Button to change the uploaded image (triggers file input)
 * - .upload-text: Upload instructions and icon
 */

document.addEventListener('DOMContentLoaded', () => {
    initializeImageUploaders();
});

/**
 * Initialize all image upload components on the page.
 * Can be called again to reinitialize after dynamic content loads.
 */
function initializeImageUploaders() {
    const dropzoneLabels = document.querySelectorAll('.dropzone-label');

    dropzoneLabels.forEach((label) => {
        // Skip if already initialized
        if (label.dataset.initialized) return;
        label.dataset.initialized = 'true';

        // Track if initial image exists (for delete tracking)
        let hasInitialImage = false;

        // Get the parent container
        const container = label.parentElement;

        // Store initial height for reset
        const initialHeight = label.clientHeight;

        // Query child elements
        const inputElement = label.querySelector('.dropzone-file');
        const imagePreviewElement = label.querySelector('.image-preview');
        const imageWrapperElement = label.querySelector('.image-wrapper');
        const actionButtonsContainer = container.querySelector('.remove-image');
        const deleteButton = container.querySelector('.delete-image-btn');
        const changeButton = container.querySelector('.change-image-btn');
        const uploadTextElement = label.querySelector('.upload-text');
        const imageDefaultInput = label.querySelector('.image-default');
        const imageChangedInput = label.querySelector('.image-changed');
        const imageDeletedInput = label.querySelector('.image-deleted');

        // Check if image already exists
        const hasExistingImage = imagePreviewElement.src && 
            imagePreviewElement.src !== 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';

        if (hasExistingImage) {
            showImagePreview();
            hasInitialImage = true;
        }

        // File input change handler
        inputElement.addEventListener('change', handleFileSelect);

        // Drag and drop handlers for the label (for browsers that need explicit handling)
        label.addEventListener('drop', handleDrop);
        label.addEventListener('dragover', (e) => e.preventDefault());

        // Image load handler (for height adjustment)
        imagePreviewElement.addEventListener('load', handleImageLoad);

        // Delete button click handler
        if (deleteButton) {
            deleteButton.addEventListener('click', handleDeleteImage);
        }

        // Change button click handler (trigger file input)
        if (changeButton) {
            changeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                inputElement.click();
            });
        }

        /**
         * Handle file drop
         */
        function handleDrop(event) {
            event.preventDefault();
            const files = event.dataTransfer?.files;
            if (files && files.length > 0) {
                // Manually assign files to input and trigger change
                inputElement.files = files;
                handleFileSelect({ target: { files: files } });
            }
        }

        /**
         * Handle file selection from input
         */
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type.startsWith('image/')) {
                console.warn('Invalid file type. Please select an image.');
                return;
            }

            const reader = new FileReader();
            
            reader.onload = (e) => {
                // Add loading animation
                imageWrapperElement.classList.add('animate-pulse');
                
                // Update preview
                imagePreviewElement.src = e.target.result;
                
                // Show preview after short delay for smooth transition
                requestAnimationFrame(() => {
                    showImagePreview();
                    imageWrapperElement.classList.remove('animate-pulse');
                });
            };

            reader.onerror = () => {
                console.error('Error reading file');
            };

            reader.readAsDataURL(file);

            // Mark as changed
            imageChangedInput.value = '1';
            imageDeletedInput.value = '';
        }

        /**
         * Handle image load for height adjustment
         */
        function handleImageLoad() {
            if (imagePreviewElement.clientHeight > 0) {
                label.style.height = `${imagePreviewElement.clientHeight}px`;
            }
        }

        /**
         * Handle image deletion
         */
        function handleDeleteImage(e) {
            e.preventDefault();
            e.stopPropagation();

            // Add fade out animation
            imageWrapperElement.style.opacity = '0';
            imageWrapperElement.style.transform = 'scale(0.95)';

            setTimeout(() => {
                const defaultImage = imageDefaultInput.value;
                
                // If there's a default image, show it; otherwise hide preview
                if (defaultImage && defaultImage !== 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=') {
                    // Show default image
                    imagePreviewElement.src = defaultImage;
                    showImagePreview();
                } else {
                    // No default, hide preview
                    imagePreviewElement.src = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
                    hideImagePreview();
                }
                
                // Reset input
                inputElement.value = '';

                // Reset height
                if (initialHeight > 0) {
                    label.style.height = `${initialHeight}px`;
                } else {
                    label.style.height = '';
                }

                // Update state
                imageChangedInput.value = '';
                
                // Mark as deleted if there was an initial image
                if (hasInitialImage) {
                    imageDeletedInput.value = '1';
                }

                // Reset animation styles
                imageWrapperElement.style.opacity = '';
                imageWrapperElement.style.transform = '';
            }, 150);
        }

        /**
         * Show image preview UI state
         */
        function showImagePreview() {
            imageWrapperElement.classList.remove('hidden');
            actionButtonsContainer?.classList.remove('hidden');
            uploadTextElement.classList.add('hidden');
        }

        /**
         * Hide image preview UI state
         */
        function hideImagePreview() {
            imageWrapperElement.classList.add('hidden');
            actionButtonsContainer?.classList.add('hidden');
            uploadTextElement.classList.remove('hidden');
        }
    });

    // Adjust heights after initialization
    window.appSetImageUploadHeight();
}

/**
 * Set the height of all image upload components to match their preview images.
 * Call this after dynamic content loads or images finish loading.
 */
function setImageUploadHeight() {
    // Small delay to ensure images are rendered
    setTimeout(() => {
        const dropzoneLabels = document.querySelectorAll('.dropzone-label');

        dropzoneLabels.forEach((label) => {
            const imagePreviewElement = label.querySelector('.image-preview');
            
            if (imagePreviewElement?.src && 
                imagePreviewElement.src !== 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' &&
                imagePreviewElement.clientHeight > 0) {
                label.style.height = `${imagePreviewElement.clientHeight}px`;
            }
        });
    }, 10);
}

// Expose functions globally
window.appSetImageUploadHeight = setImageUploadHeight;
window.appInitImageUploaders = initializeImageUploaders;
