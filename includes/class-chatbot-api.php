<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Chatbot_API
 * Handles the registration and callbacks for the chatbot REST API endpoints.
 */
class Chatbot_API {

    /**
     * Registers the REST API routes for the chatbot.
     */
    public function register_routes() {
        // Route for handling chat messages
        register_rest_route('myapi/v1', '/chat-bot/', [
            'methods'             => WP_REST_Server::CREATABLE, // Use constant for POST method
            'callback'            => [$this, 'handle_chat_bot_request'],
            'permission_callback' => '__return_true', // Allow public access (consider adding nonce verification later)
            'args'                => [ // Define expected arguments
                'last_prompt' => [
                    'required'          => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_string($param) && ! empty(trim($param));
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'conversation_history' => [
                    'required'          => false, // History might not exist initially
                    'validate_callback' => function($param, $request, $key) {
                        // Allow null or an array
                        return is_null($param) || is_array($param);
                    },
                    // No direct sanitization, handled within generate_chat_response
                ],
                 'lead_id' => [
                    'required'          => true,
                    'validate_callback' => function($param, $request, $key) {
                        // Basic check if it looks like the expected ID format (numeric string)
                        return is_string($param) && ctype_digit($param) && strlen($param) > 10;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                 ],
            ],
        ]);

        // Route for getting chatbot configuration
        register_rest_route('myapi/v1', '/chat-bot-config', [
            'methods'             => WP_REST_Server::READABLE, // Use constant for GET method
            'callback'            => [$this, 'load_chat_bot_base_configuration'],
            'permission_callback' => '__return_true', // Allow public access
        ]);
    }

    /**
     * Handles incoming chat requests via the REST API.
     * Validates input and calls the function to generate the chat response.
     *
     * @param WP_REST_Request $request The incoming request object.
     * @return WP_REST_Response|WP_Error The response object or an error.
     */
    public function handle_chat_bot_request(WP_REST_Request $request) {
        // Parameters are already validated and sanitized by register_rest_route args
        $last_prompt          = $request->get_param('last_prompt');
        $conversation_history = $request->get_param('conversation_history');
        $lead_id              = $request->get_param('lead_id');

        // Ensure conversation_history is an array, default to empty if null/invalid
        if (!is_array($conversation_history)) {
            $conversation_history = [];
        }

        // Call the function that interacts with the Gemini API
        $response_data = generate_chat_response($last_prompt, $conversation_history, $lead_id);

        // Check the result from generate_chat_response
        if ($response_data['success']) {
            return new WP_REST_Response($response_data, 200);
        } else {
            // Return an appropriate error response
            // Map internal error messages to WP_Error codes if needed
            $status_code = isset($response_data['status_code']) ? $response_data['status_code'] : 500; // Default to Internal Server Error
            return new WP_Error(
                'chatbot_api_error',
                $response_data['message'], // Error message from generate_chat_response
                ['status' => $status_code, 'result' => $response_data['result']] // Pass result (fallback message) back too
            );
        }
    }

   /**
    * Provides the basic configuration settings for the chatbot frontend.
    * Fetches options from the database.
    *
    * @param WP_REST_Request $request The incoming request object.
    * @return WP_REST_Response The response object containing configuration.
    */
   public function load_chat_bot_base_configuration(WP_REST_Request $request) {
        // Retrieve settings using get_option with defaults
        $bot_status      = get_option('wph_chatbot_enabled', 1);
        $startup_message = get_option('wph_welcome_message', 'Hi, How are you?');
        $font_size       = '16'; // Assuming this is fixed for now
        $header_title    = get_option('wph_chatbot_header_title', 'Shuriken AI Chatbot'); // Updated Default title

        // Handle bot status conversion (ensure it's 0 or 1)
        if ($bot_status !== 0 && $bot_status !== '0') {
             $bot_status = 1; // Treat anything else (including empty, null, '1', 1) as enabled
        } else {
            $bot_status = 0;
        }


        // Fix: Use CHATBOT_PLUGIN_URL for reliable default image paths
        $default_user_avatar = CHATBOT_PLUGIN_URL . 'assets/images/user-avatar.png';
        $default_bot_avatar  = CHATBOT_PLUGIN_URL . 'assets/images/bot-logo.png';

        $user_avatar_url = get_option('wph_user_image', $default_user_avatar);
        $bot_image_url   = get_option('wph_bot_image', $default_bot_avatar);

        // Ensure URLs are properly escaped if they are retrieved from options
        $user_avatar_url = esc_url($user_avatar_url ?: $default_user_avatar); // Fallback again if option is empty string
        $bot_image_url   = esc_url($bot_image_url ?: $default_bot_avatar);   // Fallback again if option is empty string


        // Get initial query button texts
        $buttons = [];
        $button_options = [
            'wph_button_1_query',
            'wph_button_2_query',
            'wph_button_3_query',
            'wph_button_4_query',
            'wph_button_5_query',
        ];

        foreach ($button_options as $option_name) {
            $button_text = get_option($option_name, '');
            // Only add button if text is not empty after trimming
            if (!empty(trim($button_text))) {
                $buttons[] = [
                    // Sanitize button text for display
                    'buttonText' => esc_html(trim($button_text)),
                    // The prompt can remain as is (will be sanitized before API call)
                    'buttonPrompt' => trim($button_text)
                ];
            }
        }

        // Prepare response data
        $response_data = [
            'botStatus'      => (int) $bot_status, // Cast to integer
            'StartUpMessage' => esc_textarea($startup_message), // Sanitize startup message
            'fontSize'       => $font_size, // Assuming fixed
            'userAvatarURL'  => $user_avatar_url,
            'botImageURL'    => $bot_image_url,
            'commonButtons'  => $buttons,
            'headerTitle'    => esc_html($header_title) // Sanitize header title
        ];

        // Return the configuration
        return new WP_REST_Response($response_data, 200);
   }
}
?>

