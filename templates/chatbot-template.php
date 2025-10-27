<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" xintegrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <?php // Consider loading lottie player conditionally or using wp_enqueue_script ?>
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>

<div class="chatbot-container lwh-open-cbot">
        <?php // Chatbot Launcher Icon ?>
        <div class="custom-chatbot__image" style="display: block;" onclick="lwhOpenCbotToggleChat()">
            <dotlottie-player src="https://lottie.host/16dfd418-2a6d-4d32-bd85-a25dfbf46ba9/IjLZs82Ay5.json"
                background="transparent" speed="1" style="width: 90px; height: 90px;" loop autoplay aria-label="<?php esc_attr_e('Open Chatbot', 'wph-ai-chatbot'); ?>"></dotlottie-player>
        </div>

        <?php // Main Chatbot Window Container ?>
        <div class="custom-chatbot" style="display: none;"> <?php // Start hidden ?>

            <div class="chat"> <?php // Transition element ?>
                <?php /* Feedback form (Consider moving JS inline event handlers to chatbot-scripts.js)
                <div class="feedback-form">
                    <div class="feedback-header">
                        <p><?php esc_html_e('Feedback', 'wph-ai-chatbot'); ?></p>
                        <p class="feedback__modal-close" onclick="lwhOpenCbotremoveFeedbackModal()"><i class="fa-solid fa-xmark"></i></p>
                    </div>
                    <form onsubmit="lwhOpenCbotsendFeedback(event)">
                        <textarea name="feedback" id="feedback" rows="4" required placeholder="<?php esc_attr_e('Your feedback...', 'wph-ai-chatbot'); ?>"></textarea>
                        <button type="submit"><?php esc_html_e('Send Feedback', 'wph-ai-chatbot'); ?></button>
                    </form>
                </div>
                */ ?>

                <?php // Loading Indicator ?>
                <div class="loading" style="display: none;"> <?php // Start hidden ?>
                    <p><i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i></p>
                    <p><?php esc_html_e('Wait a moment', 'wph-ai-chatbot'); ?></p>
                </div>

                <?php // Popup Message Area ?>
                <div class="popup" style="display: none; opacity: 0;"> <?php // Start hidden ?>
                    <p></p> <?php // Message will be inserted here by JS ?>
                </div>

                <?php // Chat Header ?>
                <div class="chat__header">
                    <?php // Normal Header View ?>
                    <div id="chat_header_name">
                        <div class="chat__title" id="wph_chatbot_header_title_display">
                            <?php echo esc_html(get_option('wph_chatbot_header_title', __('Shuriken AI Chatbot', 'wph-ai-chatbot'))); ?>
                        </div>
                        <div>
                            <div class="chat__status"><span></span> <?php esc_html_e('Offline', 'wph-ai-chatbot'); ?></div>
                        </div>
                    </div>
                    <?php // Header View for Lead Form ?>
					<div id="chat_header_back" style="display: none;">
						<button type="button" onclick="closeLeadForm()"> <?php // Use type="button" ?>
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i> <?php esc_html_e('Back', 'wph-ai-chatbot'); ?>
						</button>
                    </div>
                    <?php // Close Button ?>
                    <div>
                        <button type="button" class="chat__close-icon" onclick="lwhOpenCbotToggleChat()" aria-label="<?php esc_attr_e('Close Chatbot', 'wph-ai-chatbot'); ?>">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <?php // Placeholder div for Lead Form Header background ?>
				<div class="lead-header-container" style="display: none;"></div>

                <?php // Chat Messages Area ?>
                <div class="chat__messages" role="log" aria-live="polite">
                  <?php // Messages will be added here by JS ?>
                </div>

                 <?php // Startup Buttons Container (rendered by JS if needed) ?>
                 <div class="startup-btns" style="display: none;"></div>

                <?php // Input Area ?>
                <div class="chat__input-area">
                    <?php /* Image preview container (if image upload is added)
                    <div class="selected-image-container"></div>
                    */ ?>

                    <?php // Message Form ?>
                    <form id="messageForm" onsubmit="lwhOpenCbotonFormSubmit(event)">
                        <div class="form-outer" id="form-outer">
                            <div class="input" id="input-with-message" style="display: none;"> <?php // Start hidden unless lead captured ?>
                                <!-- Lead Capture Fields -->
                                <div id="leadCaptureFields">
                                    <div class="form-group">
                                        <input type="text" id="name" name="name" placeholder=" " class="wph-input" required aria-required="true">
                                        <label for="name" class="wph-label"><?php esc_html_e('Name', 'wph-ai-chatbot'); ?></label>
                                    </div>
                                    <div class="form-group">
                                        <input type="email" id="email" name="email" placeholder=" " class="wph-input" required aria-required="true">
                                        <label for="email" class="wph-label"><?php esc_html_e('Email', 'wph-ai-chatbot'); ?></label>
                                    </div>
                                    <div class="form-group">
                                        <input type="tel" id="phone" name="phone" placeholder=" " class="wph-input" required aria-required="true">
                                        <label for="phone" class="wph-label"><?php esc_html_e('Phone Number', 'wph-ai-chatbot'); ?></label>
                                    </div>
                                </div>

                                <!-- Message Input Field -->
                                <div class="form-group msg-input">
                                    <input type="text" id="message" name="message" placeholder=" " autocomplete="off" class="wph-input" required aria-required="true">
                                    <label for="message" class="wph-label"><?php esc_html_e('Message', 'wph-ai-chatbot'); ?></label>
                                </div>
                            </div>

                            <?php // Submit Button (for lead form & chat) ?>
                            <div class="button-div" id="button-div" style="display: none;"> <?php // Start hidden ?>
                                <button type="submit" id="submit-btn" aria-label="<?php esc_attr_e('Send Message', 'wph-ai-chatbot'); ?>">
                                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                    <span style="display: none;"><?php esc_html_e('Start Chat', 'wph-ai-chatbot'); ?></span>
                                </button>
                            </div>

                            <?php // "New Conversation" Button ?>
                            <div class="button-start-div button-div" id="button-start-div">
                                <button type="button" id="start-btn" onclick="displayLeadForm()"> <?php // Use type="button" ?>
                                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                    <span><?php esc_html_e('New Conversation', 'wph-ai-chatbot'); ?></span>
                                </button>
                            </div>
                        </div><?php // End form-outer ?>
                    </form>

                    <?php // Footer Link ?>
                    <div class="chatbot-footer">
                      <a href="https://shurikenit.com" target="_blank" rel="noopener noreferrer nofollow">
                          <?php esc_html_e('customised by', 'wph-ai-chatbot'); ?> <strong>ShurikenIT</strong>
                      </a>
                    </div>

                </div><?php // End chat__input-area ?>
            </div><?php // End chat ?>
        </div><?php // End custom-chatbot ?>
</div><?php // End chatbot-container ?>

<?php // Inline script for floating labels (Consider moving to chatbot-scripts.js for better organization) ?>
 <script id="wph-chatbot-inline-script">
    document.addEventListener('DOMContentLoaded', function() {
        // Function to handle floating label
        function handleFloatingLabel() {
            if (this.value && this.value.length > 0) { // Check if value is not empty
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        }

        // Attach event listener to all relevant inputs within the chatbot container
        const chatbotInputs = document.querySelectorAll('.lwh-open-cbot .wph-input');
        chatbotInputs.forEach((input) => {
            input.addEventListener('input', handleFloatingLabel);
            // Initial check for pre-filled/autofilled values
            handleFloatingLabel.call(input);
             // Add focus/blur handlers if needed for more complex styling
            // input.addEventListener('focus', () => { input.parentElement.classList.add('is-focused'); });
            // input.addEventListener('blur', () => { input.parentElement.classList.remove('is-focused'); });
        });

         // Remove this script tag after execution to keep DOM clean (optional)
        // const scriptTag = document.getElementById('wph-chatbot-inline-script');
        // if (scriptTag) scriptTag.remove();
    });
</script>
