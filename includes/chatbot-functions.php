<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generates a response from the Gemini API based on the conversation history.
 *
 * @param string $last_prompt The latest user message.
 * @param array $chat_history The conversation history array passed by reference.
 * @param string $lead_id The unique ID for the lead/conversation.
 * @return array Associative array with 'success' (bool), 'message' (string), and 'result' (string).
 */
function generate_chat_response($last_prompt, &$chat_history, $lead_id) {
    // Get settings safely with defaults
    $organization_info = get_option('wph_organization_info', 'No Company Information, Continue with your own knowledge');
    $fallback_value = get_option('wph_fallback_responses', "I'm sorry, I didn't catch that. Could you rephrase? Feel free to ask something else.");
    $api_key = get_option('wph_gemini_api_key', '');

    // Ensure text areas are treated as such, even if empty in options
    $organization_info = esc_textarea($organization_info);
    $fallback_value = esc_textarea($fallback_value);
    $api_key = esc_attr($api_key); // API key is usually a string, esc_attr is suitable

    // Basic check if API key is missing
    if (empty($api_key)) {
        // Log this error for the site admin
        error_log('Shuriken Chatbot Error: Gemini API Key is missing in settings.');
        // Return a user-facing error and set internal status code for API response
        return ['success' => false, 'message' => 'API Key Missing', 'result' => 'Chatbot configuration error. Please contact the site administrator.', 'status_code' => 503]; // 503 Service Unavailable
    }

   // Define the starting system instructions/prompt
    $system_instruction = "Hi! I want you to work as an AI assistant for my website. You will be a friendly and smart assistant for our company with good understanding skills. I want you to answer our customers' questions and queries just like a support team member.
I’ll provide you with some important rules and information about our website and company, and based on these, you need to give correct and helpful answers to clients.
Here are the key rules to remember:
Only answer questions related to our business and website.
If you are planning to provide any html elements like links in a tag which uses Double Quote make sure to use in that case use single apostrophe (') so that my final json structure should be valid and also my HTML element get works. for example <a href='links'></a>
If the client’s question is outside our scope, or he is tired or not happy with your answers, politely apologize and simply respond with:  " . $fallback_value . ". Limit each message or answer to a maximum of 10 words; avoid lengthy responses. and for links and other use HTML so the link is clickble and target blank so coustomers can click on it.
Here are the basic information about our website:, ###" . $organization_info . "###, ";

    $initial_history_setup = [
        ["role" => "user", "parts" => ["text" => $system_instruction]],
        ["role" => "model", "parts" => ["text" => "Okay Sure I will follow all your requirements."]]
    ];

    // Combine initial setup with the actual conversation history provided
    $full_history = array_merge($initial_history_setup, $chat_history);

    // Prepare the API request body
    $body = json_encode(["contents" => $full_history]);
    //  custom_error_log('API Request Body: ' . $body); // Uncomment for debugging

    // API request URL
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $api_key;

    // Make the API call using WordPress HTTP API
    $response = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => $body,
        'timeout' => 60, // 60-second timeout
    ]);

    // Check for WordPress HTTP API errors (e.g., cURL error, connection timed out)
    if (is_wp_error($response)) {
        error_log('Shuriken Chatbot API Error: ' . $response->get_error_message());
        return ['success' => false, 'message' => 'Failed to communicate with the API: ' . $response->get_error_message(), 'result' => 'Sorry, there was a technical issue connecting to the AI service. Please try again later.', 'status_code' => 500];
    }

    // Get response body and status code
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    // Check for non-200 HTTP status codes from the API
     if ($response_code !== 200) {
        $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
        error_log('Shuriken Chatbot API Error (HTTP ' . $response_code . '): ' . $error_message . ' | Response: ' . $response_body);
        return ['success' => false, 'message' => 'API Error (' . $response_code . '): ' . $error_message, 'result' => 'Sorry, the AI service returned an error. Please try again. (' . $response_code . ')', 'status_code' => $response_code];
    }

    // Check if the expected data structure is present in the response
   if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response = $data['candidates'][0]['content']['parts'][0]['text'];

        // Add the successful AI response to the original chat history reference
        $chat_history[] = ["role" => "model", "parts" => ["text" => $ai_response]];

	    // Save the updated conversation history (original history + new response) to the database
        save_chat_history_to_db($lead_id, $full_history); // Save the *full* history

        return ['success' => true, 'message' => 'Response generated successfully', 'result' => $ai_response];
   } elseif (isset($data['promptFeedback']['blockReason'])) {
        // Handle cases where the prompt was blocked
        $block_reason = $data['promptFeedback']['blockReason'];
        error_log('Shuriken Chatbot API Blocked: Reason - ' . $block_reason);
        return ['success' => false, 'message' => 'API request blocked: ' . $block_reason, 'result' => 'Sorry, your request could not be processed due to safety settings (' . $block_reason . '). Please rephrase your query.', 'status_code' => 400]; // 400 Bad Request
   } else {
        // Handle unexpected response format
        error_log('Shuriken Chatbot API Error: Unexpected response format. Response: ' . $response_body);
        return ['success' => false, 'message' => 'Unexpected response format from API', 'result' => 'Sorry, the AI service gave an unexpected response. Please try again.', 'status_code' => 500];
   }
}

/**
 * Saves the chat history JSON string to the post meta of the corresponding entry.
 *
 * @param string $lead_id The unique ID for the lead/conversation.
 * @param array $chat_history The full chat history array.
 */
function save_chat_history_to_db($lead_id, $chat_history) {
    // Basic validation of lead_id
    if (empty($lead_id)) {
        error_log('Shuriken Chatbot Error: Attempted to save chat history with empty lead_id.');
        return;
    }

    // Convert chat history array to JSON string
    $chat_history_json = json_encode($chat_history);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Shuriken Chatbot Error: Failed to encode chat history to JSON. Error: ' . json_last_error_msg());
        return;
    }

    // Find the post by lead_id meta key
    $args = [
        'post_type'      => 'wph_entries',
        'meta_key'       => '_lead_id',
        'meta_value'     => sanitize_text_field($lead_id), // Sanitize meta value
        'post_status'    => 'publish', // Only update published entries
        'posts_per_page' => 1,
        'fields'         => 'ids', // Only get the post ID
        'no_found_rows'  => true, // Optimization for single post query
    ];
    $posts = get_posts($args);

    if (!empty($posts)) {
        $post_id = $posts[0];
        // Update the '_chats' post meta field
        update_post_meta($post_id, '_chats', wp_slash($chat_history_json)); // wp_slash for DB saving
		// custom_error_log('Chat history saved for lead_id: ' . $lead_id . ' to post_id: ' . $post_id); // Uncomment for debugging
    } else {
        error_log('Shuriken Chatbot Error: Could not find post entry for lead_id: ' . $lead_id . ' to save chat history.');
    }
}

/**
 * Helper function to log custom messages to a debug file.
 * Use WordPress's error_log function for better integration.
 *
 * @param string $message The message to log.
 */
function custom_error_log($message) {
    // Check if WP_DEBUG and WP_DEBUG_LOG are enabled
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        if (!is_string($message)) {
            $message = print_r($message, true); // Convert arrays/objects to string
        }
        error_log('Shuriken Chatbot Log: ' . $message);
    }
}

/**
 * AJAX handler for saving the initial bot lead entry.
 * Hooked to wp_ajax_save_bot_entry and wp_ajax_nopriv_save_bot_entry.
 */
function save_bot_entry() {
    // Get the data from the request body
    $data = json_decode(file_get_contents('php://input'), true);

    // Basic check if data is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        wp_send_json_error(['message' => 'Invalid input data.'], 400); // Bad request
        wp_die();
    }

    // Sanitize and validate input data
    $name             = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $email            = isset($data['email']) ? sanitize_email($data['email']) : '';
    $phone            = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
    $query            = isset($data['query']) ? sanitize_textarea_field($data['query']) : '';
    $current_page_url = isset($data['current_page_url']) ? esc_url_raw($data['current_page_url']) : '';
    $lead_id          = isset($data['lead_id']) ? sanitize_text_field($data['lead_id']) : '';
    $user_country     = get_user_country(); // Get country based on IP

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone) || empty($lead_id)) {
         wp_send_json_error(['message' => 'Missing required fields (name, email, phone, lead_id).'], 400);
         wp_die();
    }
     if (!is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email format.'], 400);
        wp_die();
    }

    // Insert a new post of custom post type "wph_entries"
    $post_data = [
        'post_type'   => 'wph_entries',
        'post_title'  => $name, // Use name for the post title
        'post_status' => 'publish',
        'post_author' => 1, // Default to admin user 1
    ];
    $post_id = wp_insert_post($post_data, true); // Pass true to return WP_Error on failure

    if (is_wp_error($post_id)) {
        error_log('Shuriken Chatbot Error: Failed to insert entry post. Error: ' . $post_id->get_error_message());
        wp_send_json_error(['message' => 'Failed to save entry. Error: ' . $post_id->get_error_message()], 500); // Internal server error
    } else {
        // Store additional data as post meta
        update_post_meta($post_id, '_lead_id', $lead_id);
        update_post_meta($post_id, '_name', $name);
        update_post_meta($post_id, '_email', $email);
        update_post_meta($post_id, '_phone', $phone);
        update_post_meta($post_id, '_query', $query);
        update_post_meta($post_id, '_chats', ''); // Initialize chats meta field
        update_post_meta($post_id, '_user_country', $user_country);
        update_post_meta($post_id, '_current_page_url', $current_page_url);

        // Send a success response
        wp_send_json_success(['post_id' => $post_id, 'message' => 'Entry saved successfully.']);
    }
    // Ensure the script exits after sending JSON response
    wp_die();
}
// Hook the AJAX actions
add_action('wp_ajax_save_bot_entry', 'save_bot_entry'); // For logged-in users
add_action('wp_ajax_nopriv_save_bot_entry', 'save_bot_entry'); // For non-logged-in users


/**
 * Retrieves the user's country based on their IP address using freeipapi.com.
 * Includes basic error handling.
 *
 * @return string The country name or 'Unknown Country' on failure.
 */
function get_user_country() {
    // Determine the user's IP address, considering proxies
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Handle comma-separated list if multiple proxies
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip_list[0]); // Take the first IP
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Validate the IP format (basic IPv4/IPv6 check)
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return 'Invalid IP';
    }

    // Make the API request
    $api_url = "https://freeipapi.com/api/json/{$ip}";
    $response = wp_remote_get($api_url, ['timeout' => 5]); // Add a short timeout

    // Check for WP_Error during the request
    if (is_wp_error($response)) {
        error_log('Shuriken Chatbot IP Lookup Error: ' . $response->get_error_message());
        return 'Lookup Error';
    }

    // Check HTTP response code
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        error_log('Shuriken Chatbot IP Lookup Error: API returned HTTP status ' . $response_code);
        return 'API Error (' . $response_code . ')';
    }

    // Decode the response body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (json_last_error() !== JSON_ERROR_NONE) {
         error_log('Shuriken Chatbot IP Lookup Error: Failed to decode API response. Body: ' . $body);
        return 'Response Decode Error';
    }

    // Return the country name if available
    if (isset($data->countryName) && !empty($data->countryName)) {
        return sanitize_text_field($data->countryName); // Sanitize the country name
    }

    // Default if country name is not found
    return 'Unknown Country';
}
?>
