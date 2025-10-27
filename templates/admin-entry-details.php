<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds the meta box to the 'wph_entries' post type edit screen.
 */
function wph_entries_add_meta_box() {
    add_meta_box(
        'wph_entries_meta_box',          // Unique ID
        'Entry Details',                 // Box title
        'wph_entries_meta_box_callback', // Content callback function
        'wph_entries',                   // Post type
        'normal',                        // Context ('normal', 'side', 'advanced')
        'high'                           // Priority ('high', 'core', 'default', 'low')
    );
}
add_action('add_meta_boxes', 'wph_entries_add_meta_box');

/**
 * Renders the content of the 'Entry Details' meta box.
 *
 * @param WP_Post $post The post object.
 */
function wph_entries_meta_box_callback($post) {
    // Retrieve meta values, escaping them for security
    $name             = esc_html(get_post_meta($post->ID, '_name', true));
    $email            = esc_html(get_post_meta($post->ID, '_email', true));
    $phone            = esc_html(get_post_meta($post->ID, '_phone', true));
    $query            = esc_html(get_post_meta($post->ID, '_query', true));
    $current_page_url = esc_url(get_post_meta($post->ID, '_current_page_url', true)); // Use esc_url for URLs
    $user_country     = esc_html(get_post_meta($post->ID, '_user_country', true));
    $chats_json       = get_post_meta($post->ID, '_chats', true);
    $chats_data       = json_decode($chats_json, true);
    // $leadId        = esc_html(get_post_meta($post->ID, '_lead_id', true)); // Not currently used in display

    // FIX: Use CHATBOT_PLUGIN_URL constant for reliable default image paths
    $user_avatar_url = get_option('wph_user_image', CHATBOT_PLUGIN_URL . 'assets/images/user-avatar.png');
    $bot_image_url   = get_option('wph_bot_image', CHATBOT_PLUGIN_URL . 'assets/images/bot-logo.png');

    // Ensure URLs are properly escaped for use in src attributes
    $user_avatar_url = esc_url($user_avatar_url);
    $bot_image_url   = esc_url($bot_image_url);

    // Inline CSS for styling the meta box content
    // Note: It's generally better to enqueue a separate CSS file for maintainability.
    ?>
    <style>
        .entry-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Responsive grid */
            gap: 20px;
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #ccd0d4;
            background-color: #f6f7f7;
            border-radius: 4px;
        }
        .entry-detail-item {
            font-size: 14px; /* Slightly larger font */
            line-height: 1.6;
        }
        .entry-detail-item strong {
            display: block; /* Label on its own line */
            font-weight: 600; /* Standard bold */
            margin-bottom: 5px;
            color: #2c3338;
        }
        .entry-detail-item a {
             word-break: break-all; /* Prevent long URLs from breaking layout */
        }
        .chat-history-container {
            margin-top: 25px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            background-color: #fff;
            padding: 20px;
        }
        .chat-history-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccd0d4;
            padding-bottom: 10px;
            font-size: 1.2em;
        }
        .chat-row {
            display: flex;
            margin-bottom: 15px; /* Increased spacing */
            align-items: flex-start; /* Align avatar top */
        }
        .chat-row.user-message {
            justify-content: flex-end;
        }
        .chat-row.ai-message {
            justify-content: flex-start;
        }
        .chat-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            flex-shrink: 0; /* Prevent avatar shrinking */
        }
        .user-message .chat-avatar {
            order: 2; /* Move avatar to the right */
            margin-left: 10px;
        }
        .ai-message .chat-avatar {
            order: 1;
            margin-right: 10px;
        }
        .chat-bubble {
            max-width: 75%; /* Slightly wider max-width */
            padding: 10px 15px;
            border-radius: 18px; /* More rounded */
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word; /* Ensure long words wrap */
            position: relative; /* Needed for potential future pseudo-elements like tails */
        }
        .user-message .chat-bubble {
            background-color: #007cba; /* Standard WordPress blue */
            color: #fff;
            border-bottom-right-radius: 5px; /* Slightly different corner */
            order: 1;
        }
        .ai-message .chat-bubble {
            background-color: #e5e5e5; /* Lighter grey */
            color: #3c434a; /* Darker text for contrast */
            border-bottom-left-radius: 5px; /* Slightly different corner */
            order: 2;
        }
        .no-chats-message {
            background-color: #f0f6fc;
            border-left: 4px solid #007cba;
            padding: 10px 15px;
            margin-top: 15px;
            color: #3c434a;
        }
    </style>
    <?php

    // Display the entry details using the grid layout
    echo '<div class="entry-details-grid">';
    echo '<div class="entry-detail-item"><strong>User Name:</strong> ' . $name . '</div>';
    echo '<div class="entry-detail-item"><strong>User Email:</strong> ' . $email . '</div>';
    echo '<div class="entry-detail-item"><strong>User Phone:</strong> ' . $phone . '</div>';
    echo '<div class="entry-detail-item"><strong>Initial Query:</strong> ' . nl2br($query) . '</div>'; // Use nl2br for multiline queries
    echo '<div class="entry-detail-item"><strong>Chat Page URL:</strong> <a href="' . $current_page_url . '" target="_blank" rel="noopener noreferrer">' . $current_page_url . '</a></div>';
    echo '<div class="entry-detail-item"><strong>User Country:</strong> ' . $user_country . '</div>';
    echo '</div>'; // End entry-details-grid

    // Display the chat section
    echo '<div class="chat-history-container">';
    echo '<h3>Chat History</h3>';

    // Check if chat data is valid
    if (is_array($chats_data) && count($chats_data) > 2) { // Ensure there are messages beyond the initial prompts
        // Skip the first two setup messages
        $messages_to_skip = 2;

        for ($i = $messages_to_skip; $i < count($chats_data); $i++) {
            $chat = $chats_data[$i];

            if (isset($chat['role']) && isset($chat['parts']['text'])) {
                 // Sanitize the text for output - allow basic HTML like links
                $allowed_html = [
                    'a' => [
                        'href'   => true,
                        'target' => true,
                        'rel'    => true,
                    ],
                    'br' => [],
                    'strong' => [],
                    'em' => [],
                    'ul' => [],
                    'ol' => [],
                    'li' => [],
                    'p' => [],
                ];
                $text = wp_kses($chat['parts']['text'], $allowed_html);
                $text = nl2br($text); // Convert newlines to <br> after sanitization

                if ($chat['role'] === 'user') {
                    // User message
                    echo '<div class="chat-row user-message">';
                    echo '<div class="chat-bubble">' . $text . '</div>';
                    echo '<img class="chat-avatar" src="' . $user_avatar_url . '" alt="User Avatar">';
                    echo '</div>';
                } elseif ($chat['role'] === 'model') {
                    // AI message
                    echo '<div class="chat-row ai-message">';
                    echo '<img class="chat-avatar" src="' . $bot_image_url . '" alt="AI Avatar">';
                    echo '<div class="chat-bubble">' . $text . '</div>';
                    echo '</div>';
                }
            }
        }
    } else {
        echo '<p class="no-chats-message">No further conversation recorded after the initial query.</p>';
    }

    echo '</div>'; // End chat-history-container
}
?>
