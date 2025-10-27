<?php
/**
 * Plugin Name: Shuriken AI Chatbot
 * Plugin URI: https://webpresshub.net/free-ai-chatbot/
 * Description: It’s a free AI-powered chatbot for WordPress, powered by the Gemini API! This open-source plugin allows you to easily train your chatbot, engage visitors, collect user data, and save time. Fully customizable and simple to integrate.
 * Version: 2.1.0
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Customized by ShurikenIT [https://shurikenit.com]
 * Author URI: https://shurikenit.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://webpresshub.net/free-ai-chatbot/
 * Text Domain: wph-ai-chatbot
 * Domain Path: /languages
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- Define Constants ---
// Use defined() check to prevent redefining if included multiple times (unlikely but safe)
if ( ! defined( 'CHATBOT_PLUGIN_DIR' ) ) {
    define( 'CHATBOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'CHATBOT_PLUGIN_URL' ) ) {
    define( 'CHATBOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'CHATBOT_PLUGIN_VERSION' ) ) {
    define( 'CHATBOT_PLUGIN_VERSION', '2.1.0' ); // Version for cache-busting
}

// --- Load Necessary Files ---
// Use require_once to prevent fatal errors if files are somehow included elsewhere
require_once CHATBOT_PLUGIN_DIR . 'includes/class-chatbot-api.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/chatbot-functions.php';
require_once CHATBOT_PLUGIN_DIR . 'includes/chatbot-admin-settings.php';
require_once CHATBOT_PLUGIN_DIR . 'templates/admin-entry-details.php'; // Ensure admin details template is loaded

// --- Initialize Plugin ---

/**
 * Initialize REST API routes for the chatbot.
 */
if ( ! function_exists( 'shuriken_chatbot_plugin_init' ) ) {
    function shuriken_chatbot_plugin_init() {
        // Check if the API class exists before instantiating
        if ( class_exists( 'Chatbot_API' ) ) {
            $chatbot_api = new Chatbot_API();
            $chatbot_api->register_routes();
        } else {
            error_log( 'WPH Chatbot Error: Chatbot_API class not found.' );
        }
    }
}
add_action( 'rest_api_init', 'shuriken_chatbot_plugin_init' );

// --- Helper Functions ---

/**
 * Check if the chatbot is enabled in settings.
 * Centralizes the logic for checking status.
 *
 * @return bool True if enabled, false otherwise.
 */
if ( ! function_exists( 'wph_is_chatbot_enabled' ) ) {
    function wph_is_chatbot_enabled() {
        $bot_display = get_option( 'wph_chatbot_enabled', 1 ); // Default to enabled (1)

        // Check for explicit '0' (disabled string/int) or empty/null.
        if ( $bot_display === '0' || $bot_display === 0 || $bot_display === "" || $bot_display === null ) {
            return false;
        }

        // Otherwise, assume enabled.
        return true;
    }
}

/**
 * Output dynamic CSS to wp_head for chatbot positioning and theme color.
 */
if ( ! function_exists( 'wph_chatbot_dynamic_css' ) ) {
    function wph_chatbot_dynamic_css() {
        // Only output if the chatbot is enabled
        if ( ! wph_is_chatbot_enabled() ) {
            return;
        }

        // Get settings with defaults
        $chatbot_position = get_option( 'wph_chatbot_position', 'bottom-right' );
        $theme_color      = get_option( 'wph_chatbot_theme_color', '#00665E' );

        // Sanitize the color
        $theme_color = sanitize_hex_color( $theme_color );
        if ( ! $theme_color ) {
            $theme_color = '#00665E'; // Fallback to default if sanitization fails
        }

        // Prepare CSS variables
        $css_vars = [
            '--chatbot-theme-color: ' . esc_attr( $theme_color ) . ';',
            '--chatbot-image-position-right: 3%;',  // Default right
            '--chatbot-position-right: 3%;',        // Default right
            '--chatbot-image-position-right-mob: 5%;', // Default mobile right (example)
            '--chatbot-position-right-mob: 5%;',      // Default mobile right (example)
        ];

        // Adjust variables for left position
        if ( $chatbot_position === 'bottom-left' ) {
            // Using percentages like 93%/71% for right might be less intuitive than left: value;
            // Let's use left positioning for clarity. The CSS needs to handle right/left.
             $css_vars = [
                '--chatbot-theme-color: ' . esc_attr( $theme_color ) . ';',
                 // CSS will need rules like: .chatbot-container { left: var(--chatbot-position-left); }
                '--chatbot-position-left: 3%;',
                '--chatbot-image-position-left: 3%;',
                '--chatbot-position-left-mob: 5%;',
                '--chatbot-image-position-left-mob: 5%;',
             ];
             // We'll adjust the CSS file later if needed to handle left/right based on a class/variable
        }

        // Output the CSS
        echo '<style id="wph-chatbot-dynamic-styles">:root {' . implode( ' ', $css_vars ) . '}</style>';

        // NOTE: The actual positioning (right vs left) should ideally be handled by adding a class
        // to the chatbot container based on the $chatbot_position setting, and having CSS rules
        // target that class, rather than just changing CSS variable names/values here.
        // Example: Add class="chatbot-position-left" or "chatbot-position-right" in chatbot-template.php
    }
}
add_action( 'wp_head', 'wph_chatbot_dynamic_css' );


/**
 * Include the chatbot HTML template in the footer if enabled.
 */
if ( ! function_exists( 'display_chatbot_in_footer' ) ) {
    function display_chatbot_in_footer() {
        if ( wph_is_chatbot_enabled() ) {
            $template_path = CHATBOT_PLUGIN_DIR . 'templates/chatbot-template.php';
            if ( file_exists( $template_path ) ) {
                include $template_path;
            } else {
                error_log( 'WPH Chatbot Error: Chatbot template file not found at ' . $template_path );
            }
        }
    }
}
add_action( 'wp_footer', 'display_chatbot_in_footer' );

/**
 * Enqueue frontend CSS and JavaScript assets if the chatbot is enabled.
 */
if ( ! function_exists( 'chatbot_plugin_enqueue_assets' ) ) {
    function chatbot_plugin_enqueue_assets() {
        // Only enqueue if the chatbot should be displayed
        if ( wph_is_chatbot_enabled() ) {
            // Enqueue Styles
            wp_enqueue_style(
                'chatbot-styles',
                CHATBOT_PLUGIN_URL . 'assets/css/chatbot-styles.css',
                [], // Dependencies
                CHATBOT_PLUGIN_VERSION // Version for cache busting
            );

            // Enqueue Scripts
            wp_enqueue_script(
                'chatbot-scripts',
                CHATBOT_PLUGIN_URL . 'assets/js/chatbot-scripts.js',
                [ 'jquery' ], // Dependencies
                CHATBOT_PLUGIN_VERSION, // Version
                true // Load in footer
            );

            // Pass PHP variables (API endpoints) to JavaScript
            wp_localize_script( 'chatbot-scripts', 'chatbotConfig', [
                'apiUrl'    => esc_url_raw( rest_url( 'myapi/v1/chat-bot/' ) ), // Use esc_url_raw for URLs passed to JS
                'configUrl' => esc_url_raw( rest_url( 'myapi/v1/chat-bot-config' ) ),
                // Add nonce here for AJAX security later if needed
                // 'ajaxNonce' => wp_create_nonce('save_bot_entry_nonce')
            ] );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'chatbot_plugin_enqueue_assets' );

// --- Custom Post Type (WPH Entries) Setup ---

/**
 * Register the 'wph_entries' Custom Post Type.
 */
if ( ! function_exists( 'register_wph_entries_cpt' ) ) {
    function register_wph_entries_cpt() {
        $labels = [
            'name'                  => _x( 'Entries', 'Post Type General Name', 'wph-ai-chatbot' ),
            'singular_name'         => _x( 'Entry', 'Post Type Singular Name', 'wph-ai-chatbot' ),
            'menu_name'             => __( 'Chatbot Entries', 'wph-ai-chatbot' ),
            'name_admin_bar'        => __( 'Entry', 'wph-ai-chatbot' ),
            'archives'              => __( 'Entry Archives', 'wph-ai-chatbot' ),
            'attributes'            => __( 'Entry Attributes', 'wph-ai-chatbot' ),
            'parent_item_colon'     => __( 'Parent Entry:', 'wph-ai-chatbot' ),
            'all_items'             => __( 'All Entries', 'wph-ai-chatbot' ),
            // 'add_new_item'          => __( 'Add New Entry', 'wph-ai-chatbot' ), // Disabled via redirect
            // 'add_new'               => __( 'Add New', 'wph-ai-chatbot' ), // Disabled via redirect/CSS
            'new_item'              => __( 'New Entry', 'wph-ai-chatbot' ),
            'edit_item'             => __( 'View Entry Details', 'wph-ai-chatbot' ), // Changed label
            'update_item'           => __( 'Update Entry', 'wph-ai-chatbot' ),
            'view_item'             => __( 'View Entry', 'wph-ai-chatbot' ),
            'view_items'            => __( 'View Entries', 'wph-ai-chatbot' ),
            'search_items'          => __( 'Search Entries', 'wph-ai-chatbot' ),
            'not_found'             => __( 'No entries found', 'wph-ai-chatbot' ),
            'not_found_in_trash'    => __( 'No entries found in Trash', 'wph-ai-chatbot' ),
            'featured_image'        => __( 'Featured Image', 'wph-ai-chatbot' ),
            'set_featured_image'    => __( 'Set featured image', 'wph-ai-chatbot' ),
            'remove_featured_image' => __( 'Remove featured image', 'wph-ai-chatbot' ),
            'use_featured_image'    => __( 'Use as featured image', 'wph-ai-chatbot' ),
            'insert_into_item'      => __( 'Insert into entry', 'wph-ai-chatbot' ),
            'uploaded_to_this_item' => __( 'Uploaded to this entry', 'wph-ai-chatbot' ),
            'items_list'            => __( 'Entries list', 'wph-ai-chatbot' ),
            'items_list_navigation' => __( 'Entries list navigation', 'wph-ai-chatbot' ),
            'filter_items_list'     => __( 'Filter entries list', 'wph-ai-chatbot' ),
        ];
        $args = [
            'label'                 => __( 'Entry', 'wph-ai-chatbot' ),
            'description'           => __( 'Chatbot user entries and conversations.', 'wph-ai-chatbot' ),
            'labels'                => $labels,
            'supports'              => [ 'custom-fields' ], // Removed 'title' support, title is generated from name
            'hierarchical'          => false,
            'public'                => false, // Not public on frontend
            'show_ui'               => true,  // Show in admin
            'show_in_menu'          => false, // Added via add_submenu_page in admin-settings.php
            'menu_position'         => 80,
            'show_in_admin_bar'     => false, // Don't show in admin bar "+ New"
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false, // Not queryable on frontend
            'capability_type'       => 'post', // Use 'post' capabilities for simplicity
            'capabilities' => [ // Disable creating new posts directly
                 'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap' => true, // Required for 'do_not_allow'
            'show_in_rest'          => true, // Show in REST API if needed later
            'rewrite'               => false, // No rewrite rules needed
        ];
        register_post_type( 'wph_entries', $args );
    }
}
add_action( 'init', 'register_wph_entries_cpt', 0 );


/**
 * Remove all meta boxes except our custom one for 'wph_entries'.
 */
if ( ! function_exists( 'wph_remove_unwanted_meta_boxes' ) ) {
    function wph_remove_unwanted_meta_boxes() {
        // Check if on the correct post type screen
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'wph_entries' ) {
            return;
        }

        // List of core meta boxes to remove (adapt as needed)
        $core_meta_boxes = [
            // 'submitdiv',        // Keep Publish box for actions like Move to Trash
            'slugdiv',
            'authordiv',
            'commentsstatusdiv',
            'commentdiv',
            'revisionsdiv',
            'pageparentdiv',
            // Add any other meta boxes added by other plugins or themes if necessary
        ];

        foreach ( $core_meta_boxes as $box_id ) {
            remove_meta_box( $box_id, 'wph_entries', 'normal' );
            remove_meta_box( $box_id, 'wph_entries', 'side' );
            remove_meta_box( $box_id, 'wph_entries', 'advanced' );
        }
         // Custom logic to remove all except specific one (more aggressive)
         /*
         global $wp_meta_boxes;
         $post_type = 'wph_entries';
         $allowed_meta_box_id = 'wph_entries_meta_box'; // Our custom meta box ID
         $publish_meta_box_id = 'submitdiv'; // Keep the publish box

         if (isset($wp_meta_boxes[$post_type])) {
             foreach ($wp_meta_boxes[$post_type] as $context => $priorities) {
                 foreach ($priorities as $priority => $meta_boxes) {
                     foreach ($meta_boxes as $meta_box_id => $meta_box) {
                         if ($meta_box_id !== $allowed_meta_box_id && $meta_box_id !== $publish_meta_box_id) {
                             unset($wp_meta_boxes[$post_type][$context][$priority][$meta_box_id]);
                         }
                     }
                 }
             }
         }
         */
    }
}
// Use a later priority like 20 to ensure boxes are registered before removal
add_action( 'add_meta_boxes', 'wph_remove_unwanted_meta_boxes', 20 );


/**
 * Add custom columns to the 'wph_entries' list table in admin.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
if ( ! function_exists( 'wph_entries_custom_columns' ) ) {
    function wph_entries_custom_columns( $columns ) {
        // Remove unwanted default columns
        unset( $columns['title'], $columns['date'], $columns['author'] );

        // Define new column order
        $new_columns = [
            'cb'                => $columns['cb'], // Checkbox
            'name'              => __( 'Name', 'wph-ai-chatbot' ),
            'email'             => __( 'Email', 'wph-ai-chatbot' ),
            'phone'             => __( 'Phone', 'wph-ai-chatbot' ),
            'query'             => __( 'Initial Query', 'wph-ai-chatbot' ),
            'user_country'      => __( 'Country', 'wph-ai-chatbot' ),
            'current_page_url'  => __( 'Chat Page URL', 'wph-ai-chatbot' ),
            'entry_date'        => __( 'Date', 'wph-ai-chatbot' ), // Add date back
        ];
        return $new_columns;
    }
}
add_filter( 'manage_wph_entries_posts_columns', 'wph_entries_custom_columns' );


/**
 * Populate content for the custom columns in the 'wph_entries' list table.
 *
 * @param string $column The name of the column.
 * @param int    $post_id The ID of the current post.
 */
if ( ! function_exists( 'wph_entries_custom_column_content' ) ) {
    function wph_entries_custom_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'name':
                // Make name link to the edit/view screen
                $edit_link = get_edit_post_link( $post_id );
                $name = get_post_meta( $post_id, '_name', true );
                echo '<a class="row-title" href="' . esc_url( $edit_link ) . '">' . esc_html( $name ) . '</a>';
                break;
            case 'email':
                $email = get_post_meta( $post_id, '_email', true );
                echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
                break;
            case 'phone':
                $phone = get_post_meta( $post_id, '_phone', true );
                // Optional: Format phone number for tel link
                $phone_link = preg_replace( '/[^+\d]/', '', $phone ); // Remove non-digits except +
                echo '<a href="tel:' . esc_attr( $phone_link ) . '">' . esc_html( $phone ) . '</a>';
                break;
            case 'query':
                $query = get_post_meta( $post_id, '_query', true );
                // Optionally truncate long queries
                echo esc_html( wp_trim_words( $query, 15, '...' ) );
                break;
            case 'current_page_url':
                $url = get_post_meta( $post_id, '_current_page_url', true );
                if ( $url ) {
                    echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $url ) . '</a>';
                }
                break;
            case 'user_country':
                echo esc_html( get_post_meta( $post_id, '_user_country', true ) );
                break;
             case 'entry_date': // Display original post date
                echo esc_html( get_the_date( '', $post_id ) );
                break;
        }
    }
}
add_action( 'manage_wph_entries_posts_custom_column', 'wph_entries_custom_column_content', 10, 2 );

/**
 * Make custom columns sortable.
 *
 * @param array $columns Existing sortable columns.
 * @return array Modified sortable columns.
 */
if ( ! function_exists( 'wph_entries_sortable_columns' ) ) {
    function wph_entries_sortable_columns( $columns ) {
        $columns['name'] = '_name'; // Sort by meta key
        $columns['email'] = '_email';
        $columns['phone'] = '_phone';
        $columns['user_country'] = '_user_country';
        $columns['entry_date'] = 'date'; // Sort by post date
        return $columns;
    }
}
add_filter( 'manage_edit-wph_entries_sortable_columns', 'wph_entries_sortable_columns' );

/**
 * Handle sorting by custom meta fields.
 *
 * @param WP_Query $query The main query object.
 */
if ( ! function_exists( 'wph_entries_custom_orderby' ) ) {
    function wph_entries_custom_orderby( $query ) {
        // Only modify the main query in the admin for wph_entries
        if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'wph_entries' ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        // Check if ordering by one of our meta keys
        $meta_keys = [ '_name', '_email', '_phone', '_user_country' ];
        if ( in_array( $orderby, $meta_keys ) ) {
            $query->set( 'meta_key', $orderby );
            $query->set( 'orderby', 'meta_value' ); // Sort alphabetically by meta value
        }
    }
}
add_action( 'pre_get_posts', 'wph_entries_custom_orderby' );


/**
 * Redirect users trying to access the 'Add New Entry' screen directly.
 */
if ( ! function_exists( 'redirect_add_new_wph_entries' ) ) {
    function redirect_add_new_wph_entries() {
        global $pagenow;

        if ( $pagenow === 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'wph_entries' ) {
            wp_redirect( admin_url( 'edit.php?post_type=wph_entries' ) );
            exit;
        }
    }
}
add_action( 'admin_init', 'redirect_add_new_wph_entries' );

/**
 * Remove the "Add New" button and admin bar menu item for 'wph_entries'.
 */
if ( ! function_exists( 'remove_add_new_button_for_wph_entries' ) ) {
    function remove_add_new_button_for_wph_entries() {
        global $pagenow;

        if ( is_admin() && $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'wph_entries' ) {
            // Remove "Add New" button next to the title using CSS
            add_action( 'admin_head', function () {
                echo '<style> .page-title-action { display: none !important; } </style>';
            } );
        }
        // Remove "Add New -> Entry" from admin bar (runs on all admin pages)
        add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
            $wp_admin_bar->remove_node( 'new-wph_entries' );
        }, 999 ); // High priority to remove node added by WP core
    }
}
add_action( 'admin_init', 'remove_add_new_button_for_wph_entries' );

/**
 * Modify the row actions (Edit, Trash, View) for 'wph_entries' list table.
 *
 * @param array   $actions Default actions.
 * @param WP_Post $post    The current post object.
 * @return array Modified actions.
 */
if ( ! function_exists( 'wph_entries_row_actions' ) ) {
    function wph_entries_row_actions( $actions, $post ) {
        if ( $post->post_type === 'wph_entries' ) {
            // Remove default 'Edit' and 'Quick Edit'
            unset( $actions['edit'], $actions['inline hide-if-no-js'] );

            // Customize the "View" link to act as the primary action (goes to edit screen)
            if ( isset( $actions['view'] ) ) {
                $edit_link = get_edit_post_link( $post->ID );
                $view_label = __( 'View Details', 'wph-ai-chatbot' ); // More descriptive label
                $actions['view'] = '<a href="' . esc_url( $edit_link ) . '" aria-label="' . esc_attr( sprintf( __( 'View details for %s', 'wph-ai-chatbot' ), $post->post_title ) ) . '">' . $view_label . '</a>';

                // Make View bold like Edit usually is
                 $actions['view'] = '<strong>' . $actions['view'] . '</strong>';
            }

            // Ensure Trash link is correct (it usually is by default)
             if ( isset( $actions['trash'] ) ) {
                $trash_link = get_delete_post_link( $post->ID );
                if ($trash_link) {
                    $actions['trash'] = '<a href="' . esc_url( $trash_link ) . '" class="submitdelete" aria-label="' . esc_attr( sprintf( __( 'Move %s to the Trash', 'wph-ai-chatbot' ), $post->post_title ) ) . '">' . __( 'Trash', 'wph-ai-chatbot' ) . '</a>';
                } else {
                    unset($actions['trash']);
                }
             }
        }
        return $actions;
    }
}
add_filter( 'post_row_actions', 'wph_entries_row_actions', 20, 2 ); // Priority 20


/**
 * Add inline CSS to hide the title input and rearrange meta boxes on the 'wph_entries' edit screen.
 */
if ( ! function_exists( 'add_inline_css_to_post_editor_wph' ) ) {
    function add_inline_css_to_post_editor_wph() {
        $screen = get_current_screen();
        // Ensure this runs only on the post edit screen for 'wph_entries'
        if ( $screen && $screen->id === 'wph_entries' && $screen->base === 'post' ) {
            $custom_css = "
            /* Hide the title input field */
            .post-type-wph_entries #titlediv,
            .post-type-wph_entries #titlewrap {
                display: none;
            }
            /* Adjust layout if needed, e.g., move our metabox up */
            /* This column-reverse might be too aggressive if other boxes are present */
            /* .post-type-wph_entries #post-body { display: flex; flex-direction: column-reverse; } */

            /* Ensure our metabox is prominent */
             #wph_entries_meta_box { /* Style our main meta box if needed */ }
            ";
            wp_add_inline_style( 'wp-admin', $custom_css ); // Add inline style relative to common admin CSS
        }
    }
}
add_action( 'admin_enqueue_scripts', 'add_inline_css_to_post_editor_wph' );


// --- Deprecated / Removed ---
/*
 * CRITICAL FIX: Removed the `client_data_send_ai_chatbot` function
 * and its activation hook. This function violated privacy guidelines.
 */
// register_activation_hook(__FILE__, 'client_data_send_ai_chatbot'); // DO NOT RE-ENABLE

// Removed potential extra closing brace from original code.
?>
