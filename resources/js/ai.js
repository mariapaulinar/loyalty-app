/**
 * Initializes event listeners and handles the AI button functionality.
 */
document.addEventListener('DOMContentLoaded', () => {
    initializeAiButtons();
    initializeInputFields();
    initializeAutofillButtons();
});

/**
 * Initializes AI buttons and attaches click event listeners.
 */
const initializeAiButtons = () => {
    const aiButtons = document.querySelectorAll('button[data-type="ai"]');
    aiButtons.forEach(button => button.addEventListener('click', handleAiButtonClick));
};

/**
 * Initializes input fields and attaches input event listeners to toggle AI button states.
 */
const initializeInputFields = () => {
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="number"], textarea');
    inputs.forEach(input => {
        toggleAiButtonState(input);
        input.addEventListener('input', () => toggleAiButtonState(input));
    });
};

/**
 * Initializes autofill buttons and triggers AI requests for each.
 */
const initializeAutofillButtons = () => {
    const autofillButtons = document.querySelectorAll('button[data-ai-autofill]');
    autofillButtons.forEach(button => handleAutofillButton(button));
};

/**
 * Handles autofill button functionality.
 *
 * @param {HTMLButtonElement} button - The button element with autofill attribute.
 */
const handleAutofillButton = async (button) => {
    const targetId = button.getAttribute('data-target-id');
    const targetInput = document.getElementById(targetId);
    const meta = JSON.parse(button.getAttribute('data-meta') || '{}');

    const responseText = await sendAiRequest(targetInput, '', 'autofill', meta);

    if (responseText) {
        targetInput.value = responseText;
    } else {
        showErrorAlert(targetInput);
    }
};

/**
 * Handles the AI button click event.
 *
 * @param {Event} event - The click event object.
 */
const handleAiButtonClick = async (event) => {
    const button = event.currentTarget;
    hideDropdown(button);
    
    const action = button.getAttribute('data-action');
    const targetId = button.getAttribute('data-target-id');
    const targetInput = document.getElementById(targetId);
    const chatInput = targetInput.value;
    const meta = JSON.parse(button.getAttribute('data-meta') || '{}');

    const responseText = await sendAiRequest(targetInput, chatInput, action, meta);

    if (responseText) {
        targetInput.value = responseText;
    } else {
        showErrorAlert(targetInput);
    }
};

/**
 * Hides the dropdown menu.
 *
 * @param {HTMLButtonElement} button - The button element triggering the dropdown.
 */
const hideDropdown = (button) => {
    const parentUl = button.closest('ul');
    if (parentUl) {
        const parentDiv = parentUl.parentElement;
        if (parentDiv) {
            // Hide the submenu
            const toggleButton = parentDiv.previousElementSibling;
            if (toggleButton) {
                const dropdownId = toggleButton.getAttribute('data-dropdown-toggle');
                const dropdown = document.getElementById(dropdownId);
                if (dropdown) {
                    const placement = toggleButton.getAttribute('data-dropdown-placement') || 'bottom-start';
                    const dropdownInstance = new Dropdown(dropdown, toggleButton, { placement: placement });
                    dropdownInstance.hide();
                }
            }

            // Hide the parent menu
            const parentDropdownWrapper = parentDiv.closest('.relative'); // Adjust the selector as per your structure
            if (parentDropdownWrapper) {
                const parentToggleButton = parentDropdownWrapper.querySelector('button[data-dropdown-toggle]');
                if (parentToggleButton) {
                    const parentDropdownId = parentToggleButton.getAttribute('data-dropdown-toggle');
                    const parentDropdown = document.getElementById(parentDropdownId);
                    if (parentDropdown) {
                        const parentPlacement = parentToggleButton.getAttribute('data-dropdown-placement') || 'bottom-start';
                        const parentDropdownInstance = new Dropdown(parentDropdown, parentToggleButton, { placement: parentPlacement });
                        parentDropdownInstance.hide();
                    }
                }
            }
        }
    }
};


/**
 * Sends an AI request to the server and returns the response text.
 *
 * @param {HTMLInputElement} targetInput - The input element targeted by the AI.
 * @param {string} chatInput - The input text for the AI request.
 * @param {string} action - The action to be performed by the AI.
 * @param {Object} meta - Metadata for the AI request.
 * @returns {Promise<string|null>} - The response text from the AI or null if an error occurred.
 */
const sendAiRequest = async (targetInput, chatInput, action, meta) => {
    const originalText = targetInput.value;
    meta.guard = document.getElementById('meta-data-guard').value;
    meta.name = document.getElementById('meta-data-name').value;
    meta.view = document.getElementById('meta-data-view').value;

    toggleAiButtonState(targetInput, true, true);
    prepareTargetInput(targetInput);

    const spinner = createSpinner();
    const inputWrapper = targetInput.parentNode;
    inputWrapper.style.position = 'relative';
    inputWrapper.appendChild(spinner);

    const appLocaleSlug = document.querySelector('meta[name="app-locale-slug"]').getAttribute('content');
    const baseUrl = window.location.origin;
    const apiUrl = `${baseUrl}/${appLocaleSlug}/partner/api/ai-response`;

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ chatInput, action, meta })
        });

        if (!response.ok) {
            const errorData = await response.json();
            window.appAlert(`${errorData.error}`, {
                btnClose: { click: () => targetInput.focus() }
            });
            window.errorMessageShown = true;
            return null;
        }

        const data = await response.json();
        return data.response;
    } catch (error) {
        console.error('Error:', error);
        showErrorAlert(targetInput);
        return null;
    } finally {
        restoreTargetInput(targetInput, originalText);
        spinner.remove();
        setTimeout(function() {
            toggleAiButtonState(targetInput);
        }, 10);
    }
};

/**
 * Toggles the AI button state based on input value or forced state.
 *
 * @param {HTMLInputElement} input - The input field element.
 * @param {boolean} [forceDisable=false] - Whether to forcefully disable the AI button.
 * @param {boolean} [isButtonClicked=false] - Whether the function is called due to a button click.
 */
const toggleAiButtonState = (input, forceDisable = false, isButtonClicked = false) => {
    const inputValue = input.value;
    const aiButton = document.getElementById(`${input.id}_ai-menu-button`);
    const aiIndicator = document.getElementById(`${input.id}_ai-indicator`);

    if (aiButton) {
        aiButton.disabled = forceDisable || !inputValue;
        updateAiButtonStyles(aiButton, aiIndicator, aiButton.disabled, isButtonClicked);
    }
};

/**
 * Prepares the target input field for AI request.
 *
 * @param {HTMLInputElement} targetInput - The input field element.
 */
const prepareTargetInput = (targetInput) => {
    targetInput.value = "";
    targetInput.focus();
    targetInput.readOnly = true;
    targetInput.style.caretColor = 'transparent';
};

/**
 * Restores the target input field after AI request.
 *
 * @param {HTMLInputElement} targetInput - The input field element.
 * @param {string} originalText - The original text value of the input field.
 */
const restoreTargetInput = (targetInput, originalText) => {
    targetInput.readOnly = false;
    targetInput.value = originalText;
    targetInput.style.caretColor = '';
};

/**
 * Creates a spinner element.
 *
 * @returns {HTMLDivElement} - The spinner element.
 */
const createSpinner = () => {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-container';
    spinner.innerHTML = `
        <svg aria-hidden="true" class="w-5 h-5 text-gray-200 animate-spin dark:text-gray-600 fill-primary-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
        </svg>
        <span class="sr-only">Loading...</span>
    `;
    Object.assign(spinner.style, {
        position: 'absolute',
        top: '21px',
        left: '21px',
        transform: 'translate(-50%, -50%)',
        zIndex: '10'
    });
    return spinner;
};

/**
 * Updates the AI button styles based on its state.
 *
 * @param {HTMLButtonElement} aiButton - The AI button element.
 * @param {HTMLDivElement} aiIndicator - The AI indicator element.
 * @param {boolean} isDisabled - Whether the AI button is disabled.
 * @param {boolean} isButtonClicked - Whether the function is called due to a button click.
 */
const updateAiButtonStyles = (aiButton, aiIndicator, isDisabled, isButtonClicked) => {
    if (isDisabled) {
        if (isButtonClicked) {
            aiIndicator.classList.remove('bg-primary-500');
            aiIndicator.classList.add('bg-gray-400');
        } else {
            aiIndicator.classList.add('hidden');
        }
        aiButton.classList.add('text-gray-400', 'dark:text-gray-500');
        aiButton.classList.remove('hover:bg-gray-100', 'dark:hover:bg-gray-700', 'text-gray-900', 'dark:text-white');
    } else {
        aiIndicator.classList.remove('hidden', 'bg-gray-400');
        aiIndicator.classList.add('bg-primary-500');
        aiButton.classList.remove('text-gray-400', 'dark:text-gray-500');
        aiButton.classList.add('hover:bg-gray-100', 'dark:hover:bg-gray-700', 'text-gray-900', 'dark:text-white');
    }
};

/**
 * Displays an error alert.
 *
 * @param {HTMLInputElement} targetInput - The input field element.
 */
const showErrorAlert = (targetInput) => {
    if (!window.errorMessageShown) {
        window.appAlert('An error occurred while processing your request. Please try again later.', {
            btnClose: { click: () => targetInput.focus() }
        });
    }
    window.errorMessageShown = false;
};
