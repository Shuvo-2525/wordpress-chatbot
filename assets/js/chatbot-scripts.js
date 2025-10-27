// Ensure the script runs in strict mode
'use strict';

// --- Configuration and State Variables ---
const apiUrl = chatbotConfig.apiUrl; // API endpoint for chat messages
const botConfigurationUrl = chatbotConfig.configUrl; // API endpoint for bot config
let content = []; // Stores the conversation history for the API
let botConfigData = null; // Stores the fetched bot configuration
let leadCaptured = false; // Flag: true if user info has been submitted
let isFetching = false; // Flag: true if an API call is in progress
const lead_id = generateLeadId(); // Generate a unique ID for this session

// --- DOM Element References ---
// Use descriptive names and query selectors scoped to the chatbot container
const chatbotContainer = document.querySelector('.lwh-open-cbot');
if (!chatbotContainer) {
    console.error("Chatbot container (.lwh-open-cbot) not found.");
} else {
    // Only query within the container if it exists
    const chatbotImage = chatbotContainer.querySelector('.custom-chatbot__image');
    const customChatbotDiv = chatbotContainer.querySelector('.custom-chatbot');
    const chatWindow = chatbotContainer.querySelector('.chat');
    const submitButton = chatbotContainer.querySelector('#submit-btn');
    const messageInput = chatbotContainer.querySelector('#message');
    const leadCaptureFieldsDiv = chatbotContainer.querySelector('#leadCaptureFields');
    const nameInput = chatbotContainer.querySelector('#name');
    const emailInput = chatbotContainer.querySelector('#email');
    const phoneInput = chatbotContainer.querySelector('#phone');
    const formOuter = chatbotContainer.querySelector('#form-outer');
    const buttonDiv = chatbotContainer.querySelector('#button-div'); // Submit button container
    const msgInputGroup = chatbotContainer.querySelector('.form-group.msg-input');
    const chatMessagesContainer = chatbotContainer.querySelector('.chat__messages');
    const buttonStartDiv = chatbotContainer.querySelector('#button-start-div'); // Start convo button container
    const inputWithMessageDiv = chatbotContainer.querySelector('#input-with-message');
    const chatHeaderNameDiv = chatbotContainer.querySelector('#chat_header_name');
    const chatHeaderBackDiv = chatbotContainer.querySelector('#chat_header_back');
    const leadHeaderDiv = chatbotContainer.querySelector('.lead-header-container');
    const loadingIndicator = chatbotContainer.querySelector('.loading');
    const popupElement = chatbotContainer.querySelector('.popup');
    const headerTitleElement = chatbotContainer.querySelector('#wph_chatbot_header_title_display');
    const chatStatusElement = chatbotContainer.querySelector('.chat__status');
    const messageForm = chatbotContainer.querySelector('#messageForm');
    const startButton = chatbotContainer.querySelector('#start-btn'); // New conversation button

    // --- Initialization ---
    // Add event listeners only if elements exist
    if (chatbotImage) {
        chatbotImage.addEventListener('click', lwhOpenCbotToggleChat);
    }
    if (chatbotContainer.querySelector('.chat__close-icon')) {
        chatbotContainer.querySelector('.chat__close-icon').addEventListener('click', lwhOpenCbotToggleChat);
    }
    if (chatHeaderBackDiv?.querySelector('button')) {
        chatHeaderBackDiv.querySelector('button').addEventListener('click', closeLeadForm);
    }
    if (messageForm) {
        messageForm.addEventListener('submit', (event) => lwhOpenCbotonFormSubmit(event));
    }
     if (startButton) {
        startButton.addEventListener('click', displayLeadForm);
     }

    // Initialize UI based on lead capture status (check localStorage)
    initializeUIState();
    lwhOpenCbotfetchBotConfiguration(); // Fetch config on load

    // Autofill lead data if present in localStorage
    autofillLeadData();

    // Add input event listeners for floating labels (if inputs exist)
    chatbotContainer.querySelectorAll('.wph-input').forEach((input) => {
        input.addEventListener('input', handleFloatingLabel);
        // Initial check in case of autofill
        handleFloatingLabel.call(input);
    });
} // End of main execution block


// --- Core Functions ---

/**
 * Toggles the visibility of the chatbot window and launcher icon.
 */
function lwhOpenCbotToggleChat() {
    if (!chatbotImage || !customChatbotDiv || !chatWindow) return;

    const isChatbotVisible = customChatbotDiv.style.display !== 'none';

    if (isChatbotVisible) {
        chatbotImage.style.display = 'block';
        customChatbotDiv.style.display = 'none';
        chatWindow.classList.remove('show');
        customChatbotDiv.style.zIndex = '9998'; // Lower z-index when hidden
    } else {
        chatbotImage.style.display = 'none';
        customChatbotDiv.style.display = 'block';
        // Use setTimeout to allow display:block before starting transition
        setTimeout(() => {
            chatWindow.classList.add('show');
        }, 10); // Small delay
        customChatbotDiv.style.zIndex = '9999'; // Higher z-index when visible
    }
}

/**
 * Initializes the UI state based on whether lead data exists in localStorage.
 */
function initializeUIState() {
     const savedName = localStorage.getItem('lead_name');
     const savedEmail = localStorage.getItem('lead_email');
     const savedPhone = localStorage.getItem('lead_phone');

     if (savedName && savedEmail && savedPhone) {
         leadCaptured = true;
         // Hide lead fields, show message input and submit button
         if (leadCaptureFieldsDiv) leadCaptureFieldsDiv.style.display = 'none';
         if (buttonStartDiv) buttonStartDiv.style.display = 'none'; // Hide "New Conversation"
         if (inputWithMessageDiv) inputWithMessageDiv.style.display = 'flex';
         if (buttonDiv) buttonDiv.style.display = 'flex'; // Show Submit button container
         if (formOuter) formOuter.classList.remove("form-outer"); // Adjust form styling
         if (buttonDiv) buttonDiv.classList.remove("button-div"); // Adjust button styling
         if (msgInputGroup) msgInputGroup.classList.add("msg-input-lead-filled");
         if (chatShowDiv) {
            chatShowDiv.classList.remove("lead-form-open");
            chatShowDiv.classList.add("lead-form-submit");
         }
         if (chatHeaderNameDiv) chatHeaderNameDiv.style.display = "block";
         if (chatHeaderBackDiv) chatHeaderBackDiv.style.display = "none";
         if (leadHeaderDiv) leadHeaderDiv.style.display = "none";
         if (chatMessagesContainer) chatMessagesContainer.style.display = "flex";
     } else {
         leadCaptured = false;
         // Show "New Conversation" button, hide form initially
         if (buttonDiv) buttonDiv.style.display = 'none'; // Hide Submit button container
         if (inputWithMessageDiv) inputWithMessageDiv.style.display = 'none';
         if (buttonStartDiv) buttonStartDiv.style.display = 'flex'; // Show "New Conversation"
         if (chatHeaderNameDiv) chatHeaderNameDiv.style.display = "block"; // Show normal header
         if (chatHeaderBackDiv) chatHeaderBackDiv.style.display = "none";
         if (leadHeaderDiv) leadHeaderDiv.style.display = "none";
         if (chatShowDiv) {
             chatShowDiv.classList.remove("lead-form-open");
             chatShowDiv.classList.remove("lead-form-submit");
         }
         if (chatMessagesContainer) chatMessagesContainer.style.display = "flex"; // Show messages container
     }
}


/**
 * Shows the lead capture form fields.
 */
function displayLeadForm() {
    if (!inputWithMessageDiv || !buttonDiv || !buttonStartDiv || !chatMessagesContainer || !chatHeaderNameDiv || !chatHeaderBackDiv || !chatShowDiv || !leadHeaderDiv) return;

    inputWithMessageDiv.style.display = 'flex';
    buttonDiv.style.display = 'flex'; // Show submit button container
    buttonStartDiv.style.display = 'none'; // Hide "New Conversation" button
    chatMessagesContainer.style.display = "none"; // Hide chat messages
    chatHeaderNameDiv.style.display = "none"; // Hide normal header
    chatHeaderBackDiv.style.display = "block"; // Show back button header
    chatShowDiv.classList.add("lead-form-open");
    chatShowDiv.classList.remove("lead-form-submit");
    leadHeaderDiv.style.display = "block"; // Show colored header space
}

/**
 * Hides the lead capture form and returns to the initial state or chat view.
 */
function closeLeadForm() {
    if (!inputWithMessageDiv || !buttonDiv || !buttonStartDiv || !chatMessagesContainer || !chatHeaderNameDiv || !chatHeaderBackDiv || !chatShowDiv || !leadHeaderDiv) return;

    inputWithMessageDiv.style.display = 'none'; // Hide form fields + message input
    buttonDiv.style.display = 'none'; // Hide submit button container
    buttonStartDiv.style.display = 'flex'; // Show "New Conversation" button
    chatMessagesContainer.style.display = "flex"; // Show chat messages
    chatHeaderNameDiv.style.display = "block"; // Show normal header
    chatHeaderBackDiv.style.display = "none"; // Hide back button header
    chatShowDiv.classList.remove("lead-form-open");
    leadHeaderDiv.style.display = "none"; // Hide colored header space
}

/**
 * Autofills lead data from localStorage if available.
 */
function autofillLeadData() {
    const savedName = localStorage.getItem('lead_name');
    const savedEmail = localStorage.getItem('lead_email');
    const savedPhone = localStorage.getItem('lead_phone');

    if (nameInput && savedName) nameInput.value = savedName;
    if (emailInput && savedEmail) emailInput.value = savedEmail;
    if (phoneInput && savedPhone) phoneInput.value = savedPhone;

    // Trigger floating label check for autofilled fields
    [nameInput, emailInput, phoneInput].forEach(input => {
        if (input) handleFloatingLabel.call(input);
    });
}

/**
 * Handles the floating label effect for input fields.
 * Should be called with `this` bound to the input element.
 */
function handleFloatingLabel() {
    if (this.value) {
        this.classList.add('has-value');
    } else {
        this.classList.remove('has-value');
    }
}

/**
 * Handles form submission for both lead capture and sending messages.
 * @param {Event} event - The form submission event.
 * @param {string} [userMessage] - An optional message (used by quick-reply buttons).
 */
async function lwhOpenCbotonFormSubmit(event, userMessage) {
    event.preventDefault();
    if (isFetching || submitButton?.disabled) return; // Prevent multiple submissions

    let message;

    if (!leadCaptured) {
        // --- Lead Capture Mode ---
        if (!nameInput || !emailInput || !phoneInput || !messageInput) {
             console.error("Lead capture input field(s) not found.");
             alert('An error occurred. Please refresh the page.');
             return;
        }

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const phone = phoneInput.value.trim();
        const query = messageInput.value.trim(); // Initial message/query
        const current_page_url = window.location.href;

        // --- Basic Frontend Validation ---
        if (!name || !email || !phone || !query) {
            // Use custom popup/modal instead of alert
            showCustomAlert('Please fill in all required fields (Name, Email, Phone, Message).');
            return;
        }
        // Basic email format check
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showCustomAlert('Please enter a valid email address.');
            return;
        }
         // Basic phone format check (allows digits, +, spaces, hyphens - adjust as needed)
        if (!/^[+]?[\d\s-]{7,15}$/.test(phone)) {
            showCustomAlert('Please enter a valid phone number.');
            return;
        }

        // Disable button while submitting
        setSubmitButtonState(true, 'Sending...');

        const leadData = {
            name: name,
            email: email,
            phone: phone,
            query: query,
            // ip_address: ip_address, // IP should be handled server-side for accuracy
            current_page_url: current_page_url,
            lead_id: String(lead_id) // Ensure lead_id is a string
        };

        try {
            const response = await fetch('/wp-admin/admin-ajax.php?action=save_bot_entry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    // Add nonce header if implementing nonce verification
                    // 'X-WP-Nonce': chatbotConfig.ajaxNonce
                },
                body: JSON.stringify(leadData),
            });

            const data = await response.json();

            if (data.success) {
                console.log('Lead captured and saved successfully:', data);
                leadCaptured = true;
                message = query; // Use the initial query as the first message

                // Save lead info to localStorage
                localStorage.setItem('lead_name', name);
                localStorage.setItem('lead_email', email);
                localStorage.setItem('lead_phone', phone);

                // Update UI to chat mode
                switchToChatMode();
                messageInput.value = ''; // Clear input *after* sending

                // Submit the initial message to the AI
                submitMessageToAI(message);

            } else {
                console.error('Failed to save lead entry:', data.data?.message || 'Unknown server error.');
                 showCustomAlert(`Error: ${data.data?.message || 'Could not save your information. Please try again.'}`);
                setSubmitButtonState(false); // Re-enable button on failure
            }
        } catch (error) {
            console.error('Error during AJAX lead save:', error);
            showCustomAlert('An error occurred while saving your details. Please check your connection and try again.');
            setSubmitButtonState(false); // Re-enable button on failure
        }

    } else {
        // --- Chat Mode ---
        message = userMessage !== undefined ? userMessage.trim() : messageInput?.value.trim();
        if (message && messageInput) {
            messageInput.value = ''; // Clear input immediately
            submitMessageToAI(message);
        } else if (!message) {
            console.warn("Attempted to send empty message.");
             // Optionally show a subtle feedback if user tries to send empty
        }
    }
}

/**
 * Updates the UI after successful lead capture.
 */
function switchToChatMode() {
    if (!leadCaptureFieldsDiv || !formOuter || !buttonDiv || !msgInputGroup || !chatShowDiv || !chatHeaderNameDiv || !chatHeaderBackDiv || !leadHeaderDiv || !chatMessagesContainer || !buttonStartDiv) return;

    leadCaptureFieldsDiv.style.display = 'none'; // Hide name, email, phone
    buttonStartDiv.style.display = 'none'; // Ensure "New Conversation" is hidden
    formOuter.classList.remove("form-outer");
    buttonDiv.classList.remove("button-div"); // Use standard button styling now
    msgInputGroup.classList.add("msg-input-lead-filled");
    chatShowDiv.classList.remove("lead-form-open");
    chatShowDiv.classList.add("lead-form-submit");
    chatHeaderNameDiv.style.display = "block"; // Show normal header
    chatHeaderBackDiv.style.display = "none"; // Hide back button header
    leadHeaderDiv.style.display = "none";
    chatMessagesContainer.style.display = "flex"; // Ensure messages are visible

    // Show startup buttons if configured and not already clicked
    const startupBtnsDiv = chatbotContainer.querySelector(".startup-btns");
    if (startupBtnsDiv && startupBtnsDiv.innerHTML.trim() !== '' && startupBtnsDiv.style.display !== 'flex') {
         // Check if startup buttons should be shown only once per session
         // Example: if (!sessionStorage.getItem('startupButtonsShown')) {
              startupBtnsDiv.style.display = "flex";
         //    sessionStorage.setItem('startupButtonsShown', 'true');
         // }
    }
    // Set focus to the message input
    messageInput?.focus();
}


/**
 * Submits a validated user message to the AI.
 * @param {string} message - The user's message.
 */
function submitMessageToAI(message) {
    if (isFetching || !message) return;

    // Add user message to history and transcript
    content.push({ role: 'user', parts: { text: message } });
    conversationTranscript.push({ sender: 'user', time: new Date().toLocaleString(), parts: { text: message } });

    // Display user message in UI
    lwhOpenCbotaddMessage('user', message);

    // Show typing indicator and fetch AI response
    lwhOpenCbotaddTypingAnimation('ai');
    lwhOpenCbotfetchData();
}

/**
 * Adds a message bubble to the chat interface.
 * @param {string} sender - 'user' or 'ai'.
 * @param {string} message - The message text (can contain basic HTML).
 */
function lwhOpenCbotaddMessage(sender, message) {
    if (!chatMessagesContainer || !botConfigData) return;

    const messageContainer = document.createElement('div');
    messageContainer.classList.add(`chat__messages__${sender}`);

    const messageDiv = document.createElement('div'); // Wrapper for avatar + bubble

    // Sanitize message slightly on frontend (server should do main sanitization)
    // Basic link handling example (more robust parsing might be needed)
    const sanitizedMessage = message.replace(/<a href='(.*?)'>(.*?)<\/a>/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>');


    let avatarHtml = '';
    if (sender === 'ai') {
        avatarHtml = `
            <div>
                <img width="30" height="30" class="bot-image"
                    src="${botConfigData.botImageURL}" alt="Bot Avatar">
            </div>`;
    } else { // sender === 'user'
        avatarHtml = `
            <div>
                <img width="30" height="30" class="avatar-image"
                    src="${botConfigData.userAvatarURL}" alt="User Avatar">
            </div>`;
    }

    // Note: Using innerHTML requires careful sanitization server-side.
    messageDiv.innerHTML = `
        ${sender === 'ai' ? avatarHtml : ''}
        <p>${sanitizedMessage}</p>
        ${sender === 'user' ? avatarHtml : ''}
    `;

    messageContainer.appendChild(messageDiv);
    chatMessagesContainer.appendChild(messageContainer);

    // Add copy button to AI messages after adding to DOM
    if (sender === 'ai') {
        const paragraph = messageDiv.querySelector('p');
        if (paragraph) {
             // Add copy button only if message isn't just whitespace
            if (paragraph.textContent.trim()) {
                paragraph.insertAdjacentHTML('beforeend', `
                    <span title="Copy" class="copy-text" onclick="lwhOpenCbotcopyText(event)">
                        <i class="fa-regular fa-copy"></i>
                        <span class="copy-tooltip">Copied</span>
                    </span>`);
            }
        }
    }


    // Scroll to the bottom
    chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
}

/**
 * Adds a typing indicator for the AI.
 */
function lwhOpenCbotaddTypingAnimation() {
     if (!chatMessagesContainer || !botConfigData) return;

    // Remove any existing typing indicator first
    lwhOpenCbotremoveTypingAnimation();

    const typingContainer = document.createElement('div');
    typingContainer.classList.add('chat__messages__ai', 'typing-indicator'); // Add class for easy removal

    const typingAnimationDiv = document.createElement('div');
    typingAnimationDiv.innerHTML = `
        <div>
            <img width="30" height="30" class="bot-image"
                src="${botConfigData.botImageURL}" alt="Bot Typing">
        </div>
        <p class="typing-dots">
            <svg height="16" width="40" style="max-height: 20px;">
                <circle class="dot" cx="10" cy="8" r="3" />
                <circle class="dot" cx="20" cy="8" r="3" />
                <circle class="dot" cx="30" cy="8" r="3" />
            </svg>
        </p>
    `;

    typingContainer.appendChild(typingAnimationDiv);
    chatMessagesContainer.appendChild(typingContainer);
    chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
}

/**
 * Removes the AI typing indicator.
 */
function lwhOpenCbotremoveTypingAnimation() {
    chatbotContainer.querySelectorAll('.typing-indicator').forEach(el => el.remove());
}


/**
 * Copies the text content of an AI message bubble to the clipboard.
 * @param {Event} event - The click event from the copy icon.
 */
function lwhOpenCbotcopyText(event) {
    const copyButtonSpan = event.currentTarget; // The span containing the icon
    const paragraph = copyButtonSpan.closest('p');
    if (!paragraph) return;

    // Clone the paragraph to manipulate without affecting the original
    const clone = paragraph.cloneNode(true);
    // Remove the copy button span itself from the clone
    clone.querySelectorAll('.copy-text').forEach(elem => elem.remove());
    const textToCopy = clone.textContent.trim();

    navigator.clipboard.writeText(textToCopy)
        .then(() => {
            // Show feedback (e.g., change icon, show tooltip)
            const tooltip = copyButtonSpan.querySelector('.copy-tooltip');
            if (tooltip) {
                 tooltip.style.display = 'inline'; // Or add a 'show' class
                 setTimeout(() => {
                     tooltip.style.display = 'none'; // Or remove the 'show' class
                 }, 1500);
            }
             // Optionally change icon briefly
             const icon = copyButtonSpan.querySelector('i');
             if(icon) {
                 const originalClass = icon.className;
                 icon.className = 'fa-solid fa-check'; // Change to checkmark
                 setTimeout(() => { icon.className = originalClass; }, 1500);
             }
        })
        .catch(error => {
            console.error('Error copying text: ', error);
            // Optionally show an error message to the user
            showCustomAlert('Could not copy text.');
        });
}

/**
 * Fetches the AI response from the backend API.
 */
async function lwhOpenCbotfetchData() {
    if (isFetching || !submitButton) return;

    setSubmitButtonState(true); // Disable button during fetch
    isFetching = true;

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
                // Add nonce header if needed
            },
            body: JSON.stringify({
                // Send only the last user prompt and history *up to that point*
                last_prompt: content[content.length - 1]?.parts?.text, // Safely access last prompt
                conversation_history: content.slice(0, -1), // Send history *before* the last user message
                lead_id: String(lead_id) // Ensure lead_id is string
            })
        });

        // Remove typing indicator *before* processing response
        lwhOpenCbotremoveTypingAnimation();

        // Check if response is ok (status code 200-299)
        if (!response.ok) {
             // Try to parse error details from JSON response
            let errorData;
            try {
                errorData = await response.json();
            } catch (e) {
                // If response is not JSON
                errorData = { message: `HTTP error ${response.status}`, result: 'Sorry, there was a server communication error.' };
            }
             console.error('API Error:', response.status, errorData);
             const errorMessage = errorData?.result || errorData?.message || 'An unknown API error occurred.';
             lwhOpenCbotaddMessage('ai', errorMessage); // Display error message in chat
             content.push({ role: 'model', parts: { text: errorMessage } }); // Add error to history
        } else {
            // Process successful response
            const data = await response.json();
            console.log("API Response Data:", data);

            if (data.success && data.result) {
                lwhOpenCbotaddMessage('ai', data.result);
                content.push({ role: 'model', parts: { text: data.result } }); // Add bot response to history
            } else {
                 // Handle cases where success is true but result is missing, or success is false
                const errorMessage = data.result || data.message || "Sorry, I couldn't generate a response.";
                console.error('API Error (Success False or Missing Result):', data);
                lwhOpenCbotaddMessage('ai', errorMessage);
                content.push({ role: 'model', parts: { text: errorMessage } }); // Add error/fallback to history
            }
        }
    } catch (error) {
        console.error('Fetch operation failed:', error);
        lwhOpenCbotremoveTypingAnimation(); // Ensure typing indicator is removed on network error
        const networkErrorMsg = 'Sorry, I couldn\'t connect to the server. Please check your internet connection.';
        lwhOpenCbotaddMessage('ai', networkErrorMsg);
        content.push({ role: 'model', parts: { text: networkErrorMsg } });
    } finally {
        isFetching = false;
        setSubmitButtonState(false); // Re-enable button
         // Ensure focus returns to input if it exists
        messageInput?.focus();
    }
}

/**
 * Fetches the initial chatbot configuration from the backend.
 */
async function lwhOpenCbotfetchBotConfiguration() {
    if (!loadingIndicator || !chatMessagesContainer) return;

    showLoading(true);
    chatMessagesContainer.innerHTML = ''; // Clear previous messages
    content = []; // Reset conversation history

    try {
        const response = await fetch(botConfigurationUrl);

        if (!response.ok) {
             throw new Error(`HTTP error ${response.status}`);
        }

        botConfigData = await response.json();
        console.log("Bot Config Loaded:", botConfigData);

        // Update UI elements based on config
        updateChatbotUI(botConfigData);

        // Add initial welcome message
        if (botConfigData.StartUpMessage) {
            lwhOpenCbotaddMessage('ai', botConfigData.StartUpMessage);
            content.push({ role: 'model', parts: { text: botConfigData.StartUpMessage } });
        }

        // Add startup buttons if not in lead capture mode
        if (!leadCaptured) {
            renderStartupButtons(botConfigData.commonButtons);
        }


    } catch (error) {
        console.error('Failed to fetch bot configuration:', error);
        showCustomAlert('Oops! Could not load chatbot configuration.', '#991a1a');
        if(chatStatusElement) {
            chatStatusElement.innerHTML = `<span></span> Error`;
            chatStatusElement.querySelector('span').style.background = '#ff0000';
        }
        // Disable input if config fails
        setSubmitButtonState(true);
        if (messageInput) messageInput.disabled = true;

    } finally {
        showLoading(false);
    }
}

/**
 * Updates various UI elements based on the fetched configuration.
 * @param {object} config - The bot configuration data.
 */
function updateChatbotUI(config) {
    if (!config) return;

    if (headerTitleElement && config.headerTitle) {
        headerTitleElement.textContent = config.headerTitle;
    }

    if (chatMessagesContainer && config.fontSize) {
        // Ensure font size is applied safely
        const size = parseInt(config.fontSize, 10);
        if (!isNaN(size) && size > 8 && size < 30) { // Basic sanity check
            chatMessagesContainer.style.fontSize = `${size}px`;
        }
    }

    if (chatStatusElement) {
        if (config.botStatus == 1) {
            chatStatusElement.innerHTML = `<span></span> Online`;
            const span = chatStatusElement.querySelector('span');
            if (span) span.style.background = "#68D391";
             // Enable input if disabled
             setSubmitButtonState(false);
             if (messageInput) messageInput.disabled = false;
        } else { // Offline or error state
            chatStatusElement.innerHTML = `<span></span> Offline`;
             const span = chatStatusElement.querySelector('span');
            if (span) span.style.background = "#acacac";
            // Disable input
            setSubmitButtonState(true, 'Bot Offline');
            if (messageInput) messageInput.disabled = true;
        }
    } else {
         // Disable input if status element doesn't exist
         setSubmitButtonState(true);
         if (messageInput) messageInput.disabled = true;
    }
}

/**
 * Renders the initial quick-reply buttons.
 * @param {Array} buttons - Array of button objects from config.
 */
function renderStartupButtons(buttons) {
    let startupBtnsDiv = chatbotContainer.querySelector(".startup-btns");
    if (!startupBtnsDiv) {
         // Create the container if it doesn't exist
         startupBtnsDiv = document.createElement('div');
         startupBtnsDiv.classList.add('startup-btns');
         chatMessagesContainer?.after(startupBtnsDiv); // Place after messages
    }

    if (buttons && buttons.length > 0) {
        let startupBtnsHtml = buttons.map(btn =>
            `<p data-prompt="${escapeHtml(btn.buttonPrompt)}">${escapeHtml(btn.buttonText)}</p>`
        ).join('');
        startupBtnsDiv.innerHTML = startupBtnsHtml;

         // Add event listener using event delegation
         startupBtnsDiv.addEventListener('click', handleStartupButtonClick);

         // Show only if not captured lead and has buttons
         if(!leadCaptured) {
             startupBtnsDiv.style.display = 'flex';
         } else {
             startupBtnsDiv.style.display = 'none';
         }

    } else {
        startupBtnsDiv.innerHTML = ''; // Clear if no buttons
        startupBtnsDiv.style.display = 'none';
    }
}

/**
 * Handles clicks on startup buttons using event delegation.
 * @param {Event} event
 */
function handleStartupButtonClick(event) {
     if (event.target.tagName === 'P') {
        const prompt = event.target.dataset.prompt;
        if (prompt) {
            // Simulate form submission with the button's prompt
            lwhOpenCbotonFormSubmit(new Event('submit'), prompt); // Pass a dummy event object
            // Hide the buttons after one is clicked
            const startupBtnContainer = event.currentTarget; // The container div
             if (startupBtnContainer) {
                 startupBtnContainer.style.display = 'none';
                 // Optionally remove listener if only one click is allowed
                 // startupBtnContainer.removeEventListener('click', handleStartupButtonClick);
             }
        }
     }
}


// --- Utility Functions ---

/**
 * Generates a unique numeric string ID for the lead session.
 * @returns {string}
 */
function generateLeadId() {
    // Simple approach: timestamp + random number (adjust length/complexity as needed)
    return Date.now().toString() + Math.floor(Math.random() * 10000);
}

/**
 * Shows or hides the loading indicator.
 * @param {boolean} show - True to show, false to hide.
 */
function showLoading(show) {
    if (loadingIndicator) {
        loadingIndicator.style.display = show ? 'flex' : 'none';
    }
}

/**
 * Displays a custom popup/alert message.
 * @param {string} message - The message to display.
 * @param {string} [color='#333'] - Optional text color.
 */
function showCustomAlert(message, color = '#333') {
    // Replace this with your preferred modal/popup implementation
    // For now, using the existing popup element
    if (popupElement) {
        const innerPopup = popupElement.querySelector('p');
        if(innerPopup) {
            innerPopup.textContent = message;
            innerPopup.style.color = color;
        }
        popupElement.style.display = 'block';
        popupElement.style.opacity = 1; // Ensure visible
        popupElement.classList.add('popup-animation'); // Start animation

        // Auto-hide after a delay
        setTimeout(() => {
             if (popupElement) {
                popupElement.classList.remove('popup-animation');
                // Allow animation to finish before hiding
                 setTimeout(() => {
                      if (popupElement) {
                         popupElement.style.display = 'none';
                         popupElement.style.opacity = 0;
                      }
                 }, 500); // Match animation duration if possible
             }
        }, 3500); // Slightly longer display time
    } else {
         // Fallback to standard alert if popup element isn't found
         alert(message);
    }
}


/**
 * Sets the disabled state and text of the submit button.
 * @param {boolean} disabled - True to disable, false to enable.
 * @param {string} [text=''] - Optional text to display while disabled (e.g., 'Sending...').
 */
function setSubmitButtonState(disabled, text = '') {
    if (!submitButton) return;
    submitButton.disabled = disabled;
    const icon = submitButton.querySelector('i');
    const span = submitButton.querySelector('span');

    if (disabled) {
        if (icon) icon.style.display = 'none'; // Hide icon
        if (span) {
             span.textContent = text || 'Sending...'; // Default disabled text
             span.style.display = 'inline'; // Show text
        }
    } else {
         // Restore normal state (Icon visible, text hidden for send button)
         if (icon) icon.style.display = 'inline'; // Show icon
         if (span) span.style.display = 'none'; // Hide text
    }
}

/**
 * Simple HTML escaping function.
 * @param {string} str - String to escape.
 * @returns {string} Escaped string.
 */
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(match) {
        switch (match) {
            case '&': return '&amp;';
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '"': return '&quot;';
            case "'": return '&#39;'; // Use HTML entity for single quote
            default: return match;
        }
    });
}
