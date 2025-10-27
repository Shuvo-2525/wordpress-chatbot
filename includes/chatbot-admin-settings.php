<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the admin menu pages for the chatbot.
 */
if ( ! function_exists( 'wph_chatbot_menu' ) ) {
    function wph_chatbot_menu() {
        // Add the main menu item
        add_menu_page(
            __( 'Shuriken AI Chatbot', 'wph-ai-chatbot' ), // Page title
            __( 'Shuriken AI Chatbot', 'wph-ai-chatbot' ), // Menu title
            'manage_options', // Capability required
            'wph-chatbot', // Menu slug (parent)
            'wph_chatbot_main_page', // Callback function for the main page
            'dashicons-format-chat', // Icon URL (updated icon)
            75 // Position (adjust as needed)
        );

        // Add the dashboard submenu (makes the main menu item clickable)
        add_submenu_page(
            'wph-chatbot', // Parent slug
            __( 'Dashboard', 'wph-ai-chatbot' ), // Page title
            __( 'Dashboard', 'wph-ai-chatbot' ), // Menu title
            'manage_options', // Capability
            'wph-chatbot', // Menu slug (same as parent)
            'wph_chatbot_main_page', // Callback function
            0 // Position 0 (top)
        );

        // Add the entries submenu (links to the CPT list)
        add_submenu_page(
            'wph-chatbot', // Parent slug
            __( 'Entries', 'wph-ai-chatbot' ), // Page title
            __( 'Entries', 'wph-ai-chatbot' ), // Menu title
            'manage_options', // Capability
            'edit.php?post_type=wph_entries', // Link to the CPT admin screen
            null, // No direct callback function needed
            10 // Position
        );

        // Add the "Train Bot" submenu
        add_submenu_page(
            'wph-chatbot', // Parent slug
            __( 'Train Bot', 'wph-ai-chatbot' ), // Page title
            __( 'Train Bot', 'wph-ai-chatbot' ), // Menu title
            'manage_options', // Capability
            'wph-chatbot-train', // Menu slug
            'wph_chatbot_train_bot_page', // Callback function
            20 // Position
        );

        // Add the settings submenu
        add_submenu_page(
            'wph-chatbot', // Parent slug
            __( 'Settings', 'wph-ai-chatbot' ), // Page title
            __( 'Settings', 'wph-ai-chatbot' ), // Menu title
            'manage_options', // Capability
            'wph-chatbot-settings', // Menu slug
            'wph_chatbot_settings_page', // Callback function
            30 // Position
        );

        // Remove the default duplicate submenu created by add_menu_page if the slug is the same
        remove_submenu_page( 'wph-chatbot', 'wph-chatbot' );
        // Re-add the dashboard at the desired position (optional if already first)
        add_submenu_page(
            'wph-chatbot',
            __( 'Dashboard', 'wph-ai-chatbot' ),
            __( 'Dashboard', 'wph-ai-chatbot' ),
            'manage_options',
            'wph-chatbot', // Slug matches parent
            'wph_chatbot_main_page',
            0 // Explicitly set position 0
        );
    }
}
add_action( 'admin_menu', 'wph_chatbot_menu' );

/**
 * Enqueue admin scripts and styles for the chatbot pages.
 *
 * @param string $hook_suffix The current admin page hook.
 */
if ( ! function_exists( 'wph_chatbot_admin_enqueue_assets' ) ) {
    function wph_chatbot_admin_enqueue_assets( $hook_suffix ) {
        // Define the screen IDs for our plugin pages
        $plugin_pages = [
            'toplevel_page_wph-chatbot',                // Main dashboard page
            'shuriken-ai-chatbot_page_wph-chatbot-train', // Train Bot page
            'shuriken-ai-chatbot_page_wph-chatbot-settings', // Settings page
        ];

        // Check if the current page is one of our plugin pages
        if ( in_array( $hook_suffix, $plugin_pages ) ) {

            // Enqueue Tailwind CSS via CDN (ensure this is allowed/desired)
            // Consider self-hosting or using WP's built-in styles if preferred
            wp_enqueue_script( 'wph-chatbot-tailwind', 'https://cdn.tailwindcss.com', [], null, false ); // Run in header

            // Enqueue WordPress Media Uploader scripts and styles
            wp_enqueue_media();

            // Enqueue WP Color Picker styles and scripts
            wp_enqueue_style( 'wp-color-picker' );

            // Enqueue our custom admin JavaScript
            wp_enqueue_script(
                'wph-chatbot-admin-scripts',
                CHATBOT_PLUGIN_URL . 'assets/js/chatbot-admin-scripts.js',
                [ 'jquery', 'wp-color-picker' ], // Dependencies
                CHATBOT_PLUGIN_VERSION, // Version for cache busting
                true // Load in footer
            );

            // Enqueue our custom admin CSS
            wp_enqueue_style(
                'wph-chatbot-admin-styles',
                CHATBOT_PLUGIN_URL . 'assets/css/chatbot-admin-styles.css',
                [], // Dependencies
                CHATBOT_PLUGIN_VERSION // Version
            );

            // Pass translatable strings and other data to our admin script
            wp_localize_script( 'wph-chatbot-admin-scripts', 'wphChatbotAdmin', [
                'uploaderTitle'  => __( 'Select or Upload an Image', 'wph-ai-chatbot' ),
                'uploaderButton' => __( 'Use this Image', 'wph-ai-chatbot' ),
            ] );
        }
    }
}
add_action( 'admin_enqueue_scripts', 'wph_chatbot_admin_enqueue_assets' );


// --- Utility Functions for Rendering Admin UI ---

/**
 * Renders the opening wrapper for settings forms.
 * Includes Tailwind config and form opening tag.
 *
 * @param string $page_slug The slug of the current admin page.
 * @param string $page_title The title for the page header.
 * @param string $settings_group The settings group name for settings_fields().
 */
if ( ! function_exists( 'wph_chatbot_form_wrapper_start' ) ) {
    function wph_chatbot_form_wrapper_start( $page_slug, $page_title, $settings_group ) {
        ?>
        <div class="wrap wph-chatbot-admin-wrap font-sans text-text-primary">
            <script>
                // Basic Tailwind config for admin pages
                // Consider moving this to a separate JS file if it grows
                if (typeof tailwind !== 'undefined') {
                    tailwind.config = {
                        theme: {
                            extend: {
                                fontFamily: {
                                    sans: ['Inter', 'sans-serif'],
                                },
                                colors: {
                                    'primary': '#3E64DE', 'primary-light': '#F0F3FF', 'primary-hover': '#2F50BB',
                                    'success': '#27AE60', 'warning': '#FDB32C', 'danger': '#EB5757', 'info': '#2D9CDB',
                                    'text-primary': '#1A2133', 'text-secondary': '#5B616F', 'text-muted': '#9FA3AB',
                                    'border-color': '#E3E5E8', 'bg-muted': '#F8F9FA', 'bg-light': '#FAFAFB',
                                },
                                borderRadius: { 'DEFAULT': '8px', 'lg': '16px', 'sm': '4px' },
                                boxShadow: { 'DEFAULT': '0px 2px 4px 0px rgba(118, 126, 148, 0.08), 0px 0px 2px 0px rgba(139, 147, 171, 0.12)' }
                            }
                        }
                    }
                } else {
                    console.warn("WPH Chatbot: Tailwind CDN script not loaded.");
                }
            </script>

            <form method="post" action="options.php">
                <?php
                settings_fields( $settings_group ); // Output nonce, action, and option_page fields for the group
                ?>

                <header class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-text-primary"><?php echo esc_html( $page_title ); ?></h1>
                    </div>
                    <div>
                        <?php submit_button( __( 'Save Changes', 'wph-ai-chatbot' ), 'primary large', 'submit', false ); // Output only the button, no wrapper ?>
                    </div>
                </header>

                <?php // Settings sections/fields will be rendered after this ?>
        <?php
    }
}

/**
 * Renders the closing wrapper for settings forms.
 * Includes the closing form tag.
 */
if ( ! function_exists( 'wph_chatbot_form_wrapper_end' ) ) {
    function wph_chatbot_form_wrapper_end() {
        ?>
            </form>
        </div><!-- /.wrap -->
        <?php
    }
}

/**
 * Renders a card container for grouping settings.
 *
 * @param string $title The title of the card.
 * @param callable $content_callback A function that renders the table rows (<tr>...</tr>).
 */
if ( ! function_exists( 'wph_chatbot_render_card' ) ) {
    function wph_chatbot_render_card( $title, $content_callback ) {
        ?>
        <div class="bg-white rounded-lg shadow mt-8">
            <div class="p-6 border-b border-border-color">
                <h2 class="text-xl font-semibold text-text-primary">
                    <?php echo esc_html( $title ); ?>
                </h2>
            </div>
            <div class="p-6">
                <table class="form-table wph-chatbot-form-table">
                    <tbody>
                        <?php
                        // Execute the callback function which should output table rows
                        if ( is_callable( $content_callback ) ) {
                            call_user_func( $content_callback );
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}

// --- Field Rendering Functions ---

/**
 * Renders a standard text input field row.
 *
 * @param string $name Option name.
 * @param string $label Field label.
 * @param string $description Helper text below the field.
 * @param string $placeholder Placeholder text.
 * @param string $type Input type (text, password, email, etc.).
 */
if ( ! function_exists( 'wph_chatbot_render_text_field' ) ) {
    function wph_chatbot_render_text_field( $name, $label, $description = '', $placeholder = '', $type = 'text' ) {
        $value = get_option( $name, '' ); // Get saved value, default to empty string
        ?>
        <tr valign="top">
            <th scope="row" class="w-1/3">
                <label for="<?php echo esc_attr( $name ); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="w-2/3">
                <input
                    type="<?php echo esc_attr( $type ); ?>"
                    id="<?php echo esc_attr( $name ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    value="<?php echo esc_attr( $value ); ?>"
                    placeholder="<?php echo esc_attr( $placeholder ); ?>"
                    class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                />
                <?php if ( $description ) : ?>
                    <p class="text-sm text-text-muted mt-2"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}

/**
 * Renders a color picker input field row.
 *
 * @param string $name Option name.
 * @param string $label Field label.
 * @param string $description Helper text below the field.
 * @param string $default_color Default color value.
 */
if ( ! function_exists( 'wph_chatbot_render_color_field' ) ) {
    function wph_chatbot_render_color_field( $name, $label, $description = '', $default_color = '#00665E' ) {
        $color = get_option( $name, $default_color );
        // Ensure the saved color is valid, otherwise use default
        $color = sanitize_hex_color( $color ) ?: $default_color;
        ?>
        <tr valign="top">
            <th scope="row" class="w-1/3">
                <label for="<?php echo esc_attr( $name ); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="w-2/3">
                <input
                    type="text"
                    id="<?php echo esc_attr( $name ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    value="<?php echo esc_attr( $color ); ?>"
                    class="wph-color-picker"
                    data-default-color="<?php echo esc_attr( $default_color ); ?>"
                />
                <?php if ( $description ) : ?>
                    <p class="text-sm text-text-muted mt-2"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}

/**
 * Renders a textarea field row.
 *
 * @param string $name Option name.
 * @param string $label Field label.
 * @param string $description Helper text below the field.
 * @param int $rows Number of rows for the textarea.
 */
if ( ! function_exists( 'wph_chatbot_render_textarea_field' ) ) {
    function wph_chatbot_render_textarea_field( $name, $label, $description = '', $rows = 5 ) {
        $value = get_option( $name, '' );
        ?>
        <tr valign="top">
            <th scope="row" class="w-1/3">
                <label for="<?php echo esc_attr( $name ); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="w-2/3">
                <textarea
                    id="<?php echo esc_attr( $name ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    rows="<?php echo esc_attr( $rows ); ?>"
                    class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                ><?php echo esc_textarea( $value ); // Use esc_textarea for textarea output ?></textarea>
                <?php if ( $description ) : ?>
                    <p class="text-sm text-text-muted mt-2"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}

/**
 * Renders a checkbox field row.
 *
 * @param string $name Option name.
 * @param string $label Field label (usually positioned above).
 * @param string $description Text next to the checkbox.
 */
if ( ! function_exists( 'wph_chatbot_render_checkbox_field' ) ) {
    function wph_chatbot_render_checkbox_field( $name, $label, $description = '' ) {
        $checked = get_option( $name, 1 ); // Default to 1 (enabled)
        ?>
        <tr valign="top">
            <th scope="row" class="w-1/3">
                <label for="<?php echo esc_attr( $name ); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="w-2/3">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr( $name ); ?>"
                        name="<?php echo esc_attr( $name ); ?>"
                        value="1" <?php checked( 1, $checked ); ?>
                        class="h-5 w-5 rounded border-border-color text-primary focus:ring-primary"
                    />
                    <?php if ( $description ) : ?>
                    <span class="ml-3 text-base text-text-secondary"><?php echo esc_html( $description ); ?></span>
                    <?php endif; ?>
                </label>
                 <?php // Add hidden input if needed to ensure value '0' is saved when unchecked ?>
                 <input type="hidden" name="<?php echo esc_attr($name); ?>_hidden" value="0" />
                 <script>
                    // Simple JS to handle unchecked case (optional, depends on sanitize callback)
                    jQuery(document).ready(function($) {
                        $('#<?php echo esc_js($name); ?>').on('change', function() {
                            if (!$(this).is(':checked')) {
                                // If you don't have a sanitize callback handling '0',
                                // you might need to ensure '0' is submitted.
                                // A hidden field or more complex JS might be needed.
                            }
                        });
                    });
                 </script>
            </td>
        </tr>
        <?php
    }
}


/**
 * Renders a select dropdown field row.
 *
 * @param string $name Option name.
 * @param string $label Field label.
 * @param array $options Associative array of value => text pairs.
 * @param string $description Helper text below the field.
 */
if ( ! function_exists( 'wph_chatbot_render_select_field' ) ) {
    function wph_chatbot_render_select_field( $name, $label, $options, $description = '' ) {
        $current_value = get_option( $name, '' );
        ?>
        <tr valign="top">
            <th scope="row" class="w-1/3">
                <label for="<?php echo esc_attr( $name ); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="w-2/3">
                <select
                    id="<?php echo esc_attr( $name ); ?>"
                    name="<?php echo esc_attr( $name ); ?>"
                    class="w-full max-w-xs p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary" <?php // Adjusted max-width ?>
                >
                    <?php foreach ( $options as $value => $text ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_value, $value ); ?>>
                            <?php echo esc_html( $text ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ( $description ) : ?>
                    <p class="text-sm text-text-muted mt-2"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}

/**
 * Renders an image uploader field row using the WordPress Media Library.
 *
 * @param string $name Option name.
 * @param string $label Field label.
 * @param string $description Helper text.
 */
if ( ! function_exists( 'wph_chatbot_render_image_uploader_field' ) ) {
    function wph_chatbot_render_image_uploader_field( $name, $label, $description = '' ) {
        $image_url = get_option( $name, '' );
        ?>
        <tr valign="top">
            <th scope="row" class="w-1/3">
                <label class="text-base font-semibold text-text-primary"><?php echo esc_html( $label ); ?></label>
            </th>
            <td class="w-2/3">
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 bg-slate-100 border border-border-color rounded-lg flex items-center justify-center overflow-hidden relative"> <?php // Added relative positioning ?>
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php esc_attr_e( 'Preview', 'wph-ai-chatbot' ); ?>"
                             class="wph-image-uploader-preview <?php echo $image_url ? '' : 'hidden'; ?>"
                             id="<?php echo esc_attr( $name ); ?>_preview"
                             style="max-width: 100%; height: 100%; object-fit: cover;"> <?php // Changed to 100% height ?>
                        <span class="text-text-muted absolute <?php echo $image_url ? 'hidden' : ''; ?>" <?php // Added absolute positioning ?>
                              id="<?php echo esc_attr( $name ); ?>_placeholder">
                            <?php esc_html_e( 'No image', 'wph-ai-chatbot' ); ?>
                        </span>
                    </div>
                    <div>
                        <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $image_url ); ?>" />
                        <button type="button" class="wph-image-upload-button button button-secondary">
                            <?php esc_html_e( 'Upload Image', 'wph-ai-chatbot' ); ?>
                        </button>
                        <button type="button" class="wph-image-remove-button button button-secondary text-red-600 border-red-600 hover:bg-red-50" style="<?php echo $image_url ? '' : 'display:none;'; ?>">
                            <?php esc_html_e( 'Remove', 'wph-ai-chatbot' ); ?>
                        </button>
                        <?php if ( $description ) : ?>
                            <p class="text-sm text-text-muted mt-2"><?php echo esc_html( $description ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }
}


// ==================================================================
// PAGE CALLBACK FUNCTIONS
// ==================================================================

/**
 * Renders the main dashboard page content.
 */
if ( ! function_exists( 'wph_chatbot_main_page' ) ) {
    function wph_chatbot_main_page() {
        // Get total entries count
        $entry_count = wp_count_posts( 'wph_entries' );
        $total_entries = $entry_count->publish ?? 0; // Use null coalescing operator

        // Get bot status using helper function
        $bot_enabled = wph_is_chatbot_enabled();

        // Start page wrapper
        wph_chatbot_form_wrapper_start( 'wph-chatbot', __( 'Shuriken AI Chatbot Dashboard', 'wph-ai-chatbot' ), '' ); // No settings group needed here
        ?>
        <p class="text-text-secondary mt-1 mb-8">
            <?php esc_html_e( "Welcome! Here's an overview of your chatbot's activity.", 'wph-ai-chatbot' ); ?>
        </p>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Total Entries Card -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg bg-primary-light text-primary">
                         <span class="dashicons dashicons-groups w-6 h-6"></span>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary font-medium"><?php esc_html_e( 'Total Entries', 'wph-ai-chatbot' ); ?></p>
                        <p class="text-3xl font-bold text-text-primary"><?php echo esc_html( number_format_i18n( $total_entries ) ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Chatbot Status Card -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg <?php echo $bot_enabled ? 'bg-green-100 text-success' : 'bg-red-100 text-danger'; ?>">
                        <span class="dashicons <?php echo $bot_enabled ? 'dashicons-yes-alt' : 'dashicons-no'; ?> w-6 h-6"></span>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary font-medium"><?php esc_html_e( 'Chatbot Status', 'wph-ai-chatbot' ); ?></p>
                        <p class="text-3xl font-bold text-text-primary"><?php echo $bot_enabled ? esc_html__( 'Online', 'wph-ai-chatbot' ) : esc_html__( 'Offline', 'wph-ai-chatbot' ); ?></p>
                    </div>
                </div>
                 <?php if ( ! $bot_enabled ) : ?>
                    <p class="text-sm text-danger mt-2"><?php printf( __( 'Enable the bot in <a href="%s">Settings</a>.', 'wph-ai-chatbot' ), esc_url( admin_url( 'admin.php?page=wph-chatbot-settings' ) ) ); ?></p>
                 <?php endif; ?>
            </div>

             <!-- Placeholder Card (Example) -->
            <div class="bg-white p-6 rounded-lg shadow border border-dashed border-border-color">
                 <div class="flex items-center gap-4 text-text-muted">
                     <div class="p-3 rounded-lg bg-bg-light">
                        <span class="dashicons dashicons-chart-bar w-6 h-6"></span>
                     </div>
                     <div>
                        <p class="text-sm font-medium"><?php esc_html_e( 'Future Stat', 'wph-ai-chatbot' ); ?></p>
                        <p class="text-xl font-bold"><?php esc_html_e( 'Coming Soon', 'wph-ai-chatbot' ); ?></p>
                     </div>
                 </div>
            </div>

        </div><!-- /.grid -->

        <!-- Quick Links Card -->
        <?php
        wph_chatbot_render_card( __( 'Quick Links', 'wph-ai-chatbot' ), function() {
            ?>
            <tr class="quick-links-row"> <?php // Use a row for layout, not table cells here ?>
                <td colspan="2"> <?php // Span across both columns ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wph_entries' ) ); ?>" class="p-4 border border-border-color rounded-lg hover:bg-bg-light hover:shadow-sm transition-all block">
                            <h3 class="text-base font-semibold text-primary mb-1"><?php esc_html_e( 'View Entries', 'wph-ai-chatbot' ); ?></h3>
                            <p class="text-sm text-text-secondary"><?php esc_html_e( 'See all leads and conversations captured by the bot.', 'wph-ai-chatbot' ); ?></p>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wph-chatbot-train' ) ); ?>" class="p-4 border border-border-color rounded-lg hover:bg-bg-light hover:shadow-sm transition-all block">
                            <h3 class="text-base font-semibold text-primary mb-1"><?php esc_html_e( 'Train Bot', 'wph-ai-chatbot' ); ?></h3>
                            <p class="text-sm text-text-secondary"><?php esc_html_e( 'Update your bot\'s knowledge and organization info.', 'wph-ai-chatbot' ); ?></p>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wph-chatbot-settings' ) ); ?>" class="p-4 border border-border-color rounded-lg hover:bg-bg-light hover:shadow-sm transition-all block">
                            <h3 class="text-base font-semibold text-primary mb-1"><?php esc_html_e( 'Configure Settings', 'wph-ai-chatbot' ); ?></h3>
                            <p class="text-sm text-text-secondary"><?php esc_html_e( 'Adjust API keys, appearance, and other core settings.', 'wph-ai-chatbot' ); ?></p>
                        </a>
                    </div>
                </td>
            </tr>
            <?php
        });
        ?>

        <?php
        // Close the page wrapper started in wph_chatbot_form_wrapper_start
        // Since this page doesn't have a form, we just need the closing div
        echo '</div><!-- /.wrap -->';
    }
}


/**
 * Renders the "Train Bot" settings page content.
 */
if ( ! function_exists( 'wph_chatbot_train_bot_page' ) ) {
    function wph_chatbot_train_bot_page() {
        // Start form wrapper for the 'wph_chatbot_train_group' settings
        wph_chatbot_form_wrapper_start(
            'wph-chatbot-train',
            __( 'Train Your Chatbot', 'wph-ai-chatbot' ),
            'wph_chatbot_train_group' // Settings group name
        );

        // Bot Knowledge Base Card
        wph_chatbot_render_card( __( 'Bot Knowledge Base', 'wph-ai-chatbot' ), function() {
            wph_chatbot_render_textarea_field(
                'wph_organization_info',
                __( 'Organization Information and Train AI', 'wph-ai-chatbot' ),
                __( 'Provide all information about your company, products, and services. This is the primary knowledge base for the AI.', 'wph-ai-chatbot' ),
                15 // Rows
            );
            wph_chatbot_render_textarea_field(
                'wph_welcome_message',
                __( 'Welcome Message', 'wph-ai-chatbot' ),
                __( 'The first message the chatbot sends to the user.', 'wph-ai-chatbot' ),
                3 // Rows
            );
            wph_chatbot_render_textarea_field(
                'wph_fallback_responses',
                __( 'Fallback Response', 'wph-ai-chatbot' ), // Singular
                __( "The message the bot sends when it doesn't understand or can't answer a query.", 'wph-ai-chatbot' ),
                3 // Rows
            );
        });

        // Initial Query Buttons Card
        wph_chatbot_render_card( __( 'Initial Query Buttons', 'wph-ai-chatbot' ), function() {
            ?>
            <tr valign="top">
                <th scope="row" class="w-1/3">
                    <label class="text-base font-semibold text-text-primary"><?php esc_html_e( 'Quick-Reply Buttons', 'wph-ai-chatbot' ); ?></label>
                    <p class="text-sm text-text-muted mt-2 font-normal"><?php esc_html_e( 'Buttons appearing at the start of the chat. Users click these to send the text as their first message. Leave blank to hide.', 'wph-ai-chatbot' ); ?></p>
                </th>
                <td class="w-2/3">
                    <div class="flex flex-col gap-4">
                        <?php
                        // Loop to render 5 button text fields
                        for ( $i = 1; $i <= 5; $i++ ) {
                            $option_name = "wph_button_{$i}_query";
                            $default_text = ( $i <= 2 ) ? ( $i === 1 ? 'I want your help !!' : 'I want some Discounts' ) : ''; // Defaults only for first two
                            $value = get_option( $option_name, $default_text );
                            ?>
                            <input
                                type="text"
                                name="<?php echo esc_attr( $option_name ); ?>"
                                value="<?php echo esc_attr( $value ); ?>"
                                placeholder="<?php printf( esc_attr__( 'Button %d Text', 'wph-ai-chatbot' ), $i ); ?>"
                                class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                            />
                            <?php
                        }
                        ?>
                    </div>
                     <p class="text-sm text-text-muted mt-2"><?php esc_html_e( 'Example: If you enter "Tell me about pricing", clicking the button will send "Tell me about pricing" as the user\'s message.', 'wph-ai-chatbot' ); ?></p>
                </td>
            </tr>
            <?php
        });

        // Close the form wrapper
        wph_chatbot_form_wrapper_end();
    }
}


/**
 * Renders the main "Settings" page content.
 */
if ( ! function_exists( 'wph_chatbot_settings_page' ) ) {
    function wph_chatbot_settings_page() {
        // Start form wrapper for the 'wph_chatbot_settings_group'
        wph_chatbot_form_wrapper_start(
            'wph-chatbot-settings',
            __( 'Shuriken AI Chatbot Settings', 'wph-ai-chatbot' ),
            'wph_chatbot_settings_group' // Settings group name
        );

        // API & General Settings Card
        wph_chatbot_render_card( __( 'API & General Settings', 'wph-ai-chatbot' ), function() {
            wph_chatbot_render_text_field(
                'wph_gemini_api_key',
                __( 'Gemini API Key', 'wph-ai-chatbot' ),
                __( 'Get your API key from Google AI Studio.', 'wph-ai-chatbot' ),
                __( 'Enter your Gemini API Key', 'wph-ai-chatbot' ),
                'password' // Input type
            );
            wph_chatbot_render_checkbox_field(
                'wph_chatbot_enabled',
                __( 'Chatbot Status', 'wph-ai-chatbot' ), // Changed label
                __( 'Enable the chatbot on your website.', 'wph-ai-chatbot' )
            );
            wph_chatbot_render_select_field(
                'wph_chatbot_position',
                __( 'Chatbot Position', 'wph-ai-chatbot' ),
                [ // Options array
                    'bottom-right' => __( 'Bottom Right', 'wph-ai-chatbot' ),
                    'bottom-left'  => __( 'Bottom Left', 'wph-ai-chatbot' ),
                ],
                __( 'Choose where the chatbot launcher appears.', 'wph-ai-chatbot' )
            );
        });

        // Appearance Card
        wph_chatbot_render_card( __( 'Chatbot Appearance', 'wph-ai-chatbot' ), function() {
            wph_chatbot_render_text_field(
                'wph_chatbot_header_title',
                __( 'Header Title', 'wph-ai-chatbot' ),
                __( 'The title displayed at the top of the chat window.', 'wph-ai-chatbot' ),
                __( 'Shuriken AI Chatbot', 'wph-ai-chatbot' ) // Default placeholder
            );
            wph_chatbot_render_color_field(
                'wph_chatbot_theme_color',
                __( 'Theme Color', 'wph-ai-chatbot' ),
                __( 'Controls the header, buttons, and border color.', 'wph-ai-chatbot' ),
                '#00665E' // Default color
            );
            wph_chatbot_render_image_uploader_field(
                'wph_user_image',
                __( 'User Avatar Image', 'wph-ai-chatbot' ),
                __( 'Upload an image for the user\'s avatar in the chat.', 'wph-ai-chatbot' )
            );
            wph_chatbot_render_image_uploader_field(
                'wph_bot_image',
                __( 'Bot Avatar Image', 'wph-ai-chatbot' ),
                __( 'Upload an image for the bot\'s avatar.', 'wph-ai-chatbot' )
            );
        });

        // Close the form wrapper
        wph_chatbot_form_wrapper_end();
    }
}


/**
 * Register settings, sections, and fields for the settings API.
 */
if ( ! function_exists( 'wph_chatbot_settings_init' ) ) {
    function wph_chatbot_settings_init() {

        // --- Register Settings for 'wph_chatbot_settings_group' ---
        register_setting( 'wph_chatbot_settings_group', 'wph_gemini_api_key', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ] );
        register_setting( 'wph_chatbot_settings_group', 'wph_chatbot_enabled', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint', // Ensures 0 or 1
            'default'           => 1, // Default to enabled
        ] );
        register_setting( 'wph_chatbot_settings_group', 'wph_chatbot_position', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_key', // Sanitize to 'bottom-right' or 'bottom-left'
            'default'           => 'bottom-right',
        ] );
        register_setting( 'wph_chatbot_settings_group', 'wph_user_image', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw', // Allows saving URLs
            'default'           => '',
        ] );
        register_setting( 'wph_chatbot_settings_group', 'wph_bot_image', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        ] );
        register_setting( 'wph_chatbot_settings_group', 'wph_chatbot_header_title', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => __( 'Shuriken AI Chatbot', 'wph-ai-chatbot' ), // Default translatable title
        ] );
        register_setting( 'wph_chatbot_settings_group', 'wph_chatbot_theme_color', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#00665E',
        ] );

        // --- Register Settings for 'wph_chatbot_train_group' ---
        register_setting( 'wph_chatbot_train_group', 'wph_welcome_message', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( 'Hi, How are you?', 'wph-ai-chatbot' ),
        ] );
        register_setting( 'wph_chatbot_train_group', 'wph_fallback_responses', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( "I'm sorry, I didn't catch that. Could you rephrase? Feel free to ask something else.", 'wph-ai-chatbot' ),
        ] );
        register_setting( 'wph_chatbot_train_group', 'wph_organization_info', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => __( 'No Company Information, Continue with your own knowledge', 'wph-ai-chatbot' ),
        ] );
        // Register button query settings
        for ( $i = 1; $i <= 5; $i++ ) {
            $default_text = ( $i <= 2 ) ? ( $i === 1 ? 'I want your help !!' : 'I want some Discounts' ) : '';
            register_setting( 'wph_chatbot_train_group', "wph_button_{$i}_query", [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => $default_text,
            ] );
        }

        // Note: Sections and fields are not formally added here because
        // we are rendering them manually within the callback functions using utility functions.
        // If using add_settings_section and add_settings_field, they would go here.
    }
}
add_action( 'admin_init', 'wph_chatbot_settings_init' );

// Deprecated Sanitization Function (no longer directly used by register_setting)
/*
if ( ! function_exists( 'wph_sanitize_apostrophe' ) ) {
    function wph_sanitize_apostrophe( $input ) {
        // This is generally not a recommended sanitization method.
        // Use sanitize_text_field, sanitize_textarea_field, etc. instead.
        return str_replace( '"', "'", $input );
    }
}
*/
?>
