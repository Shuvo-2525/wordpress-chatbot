<?php
// Add a top-level menu and submenus
add_action('admin_menu', 'wph_chatbot_menu');

function wph_chatbot_menu() {
    // --- CHANGE: Renamed Menu Titles ---
    add_menu_page(
        'Shuriken AI Chatbot', // Page title
        'Shuriken AI Chatbot', // Menu title
        'manage_options', // Capability
        'wph-chatbot', // Menu slug (Keep slug the same for compatibility)
        'wph_chatbot_main_page', // Function to display the main page content
        // 'dashicons-smiley', // Icon URL
        plugins_url('assets/images/shuriken-menu-icon.png', CHATBOT_PLUGIN_DIR . 'wph-chatbot.php'), // Icon URL
        6 // Position
    );

    // Add the dashboard submenu
    add_submenu_page(
        'wph-chatbot', // Parent slug
        'Shuriken AI Chatbot Dashboard', // Page title
        'Dashboard', // Menu title (Keep short for sidebar)
        'manage_options', // Capability
        'wph-chatbot', // Menu slug (same as parent)
        'wph_chatbot_main_page' // Function
    );

    // Add the entries submenu
   add_submenu_page(
        'wph-chatbot', // Parent slug
        'Shuriken Entries', // Page title
        'Entries', // Menu title (Keep short for sidebar)
        'manage_options', // Capability
        'edit.php?post_type=wph_entries' // Redirects to the CPT's main page
    );

    // Add the "Train Bot" submenu
    add_submenu_page(
        'wph-chatbot', // Parent slug
        'Train Shuriken AI Bot', // Page title
        'Train Bot', // Menu title (Keep short for sidebar)
        'manage_options', // Capability
        'wph-chatbot-train', // Menu slug
        'wph_chatbot_train_bot_page' // Function
    );

    // Add the settings submenu
    add_submenu_page(
        'wph-chatbot', // Parent slug
        'Shuriken AI Chatbot Settings', // Page title
        'Settings', // Menu title (Keep short for sidebar)
        'manage_options', // Capability
        'wph-chatbot-settings', // Menu slug
        'wph_chatbot_settings_page' // Function
    );

    // Remove the default duplicate submenu created by add_menu_page
    remove_submenu_page('wph-chatbot', 'wph-chatbot');
    // Re-add the dashboard at the top
    add_submenu_page(
        'wph-chatbot',
        'Shuriken AI Chatbot Dashboard', // Page title
        'Dashboard', // Menu title
        'manage_options',
        'wph-chatbot',
        'wph_chatbot_main_page',
        0 // Position 0
    );
}

// Enqueue assets for our new admin UI
add_action('admin_enqueue_scripts', 'wph_chatbot_admin_enqueue_assets');
function wph_chatbot_admin_enqueue_assets($hook_suffix) {
    // Only load on our plugin's pages
    $plugin_pages = [
        'toplevel_page_wph-chatbot', // Main dashboard page
        'shuriken-ai-chatbot_page_wph-chatbot-settings', // Settings page (using renamed parent slug temporarily if needed, check actual hook)
        'shuriken-ai-chatbot_page_wph-chatbot-train',   // Train page (using renamed parent slug temporarily if needed, check actual hook)
        'edit.php', // For Entries list
        'post.php', // For Entry detail
        // -- Check actual hook suffixes if the above don't work after renaming --
        // Example: If the main page hook becomes 'toplevel_page_shuriken-ai-chatbot' adjust accordingly
        'toplevel_page_wph-chatbot',
        'wph-chatbot_page_wph-chatbot-settings',
        'wph-chatbot_page_wph-chatbot-train',
    ];

    // Get current screen info
    $screen = get_current_screen();
    $current_hook = $screen ? $screen->id : '';

    // Check if we are on a plugin page or editing a wph_entries CPT
    $is_plugin_page = in_array($current_hook, $plugin_pages);
    // Check post type specifically for edit/post screens
    $is_cpt_page = ($screen && $screen->post_type === 'wph_entries');


    if ($is_plugin_page || $is_cpt_page) {
        // Enqueue Tailwind CSS
        wp_enqueue_script('wph-chatbot-tailwind', 'https://cdn.tailwindcss.com', [], null, false);

        // Enqueue WordPress Media Uploader scripts
        wp_enqueue_media();

        // Enqueue WP Color Picker styles and scripts
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script(
            'wph-chatbot-admin-scripts',
            CHATBOT_PLUGIN_URL . 'assets/js/chatbot-admin-scripts.js',
            ['jquery', 'wp-color-picker'], // Add wp-color-picker dependency
            CHATBOT_PLUGIN_VERSION,
            true
        );

        // Enqueue our admin CSS for UI tweaks
        wp_enqueue_style(
            'wph-chatbot-admin-styles',
            CHATBOT_PLUGIN_URL . 'assets/css/chatbot-admin-styles.css',
            [],
            CHATBOT_PLUGIN_VERSION
        );

        // Pass data to our admin script
        wp_localize_script('wph-chatbot-admin-scripts', 'wphChatbotAdmin', [
            'uploaderTitle' => 'Select an Image',
            'uploaderButton' => 'Use this Image',
        ]);
    }
}


// --- Utility function for rendering settings form wrapper ---
function wph_chatbot_form_wrapper_start($page_slug, $page_title, $settings_group) {
    ?>
    <div class="wrap wph-chatbot-admin-wrap font-sans text-text-primary">
        <script>
            // Configure Tailwind for our admin page based on the style guide
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                        },
                        colors: {
                            'primary': '#3E64DE',
                            'primary-light': '#F0F3FF',
                            'primary-hover': '#2F50BB',
                            'success': '#27AE60',
                            'warning': '#FDB32C',
                            'danger': '#EB5757',
                            'info': '#2D9CDB',
                            'text-primary': '#1A2133',
                            'text-secondary': '#5B616F',
                            'text-muted': '#9FA3AB',
                            'border-color': '#E3E5E8',
                            'bg-muted': '#F8F9FA',
                            'bg-light': '#FAFAFB',
                        },
                        borderRadius: {
                            'DEFAULT': '8px',
                            'lg': '16px',
                            'sm': '4px',
                        },
                        boxShadow: {
                            'DEFAULT': '0px 2px 4px 0px rgba(118, 126, 148, 0.08), 0px 0px 2px 0px rgba(139, 147, 171, 0.12)',
                        }
                    }
                }
            }
        </script>

        <form method="post" action="options.php">
            <?php
            settings_fields($settings_group);
            ?>

            <header class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-text-primary"><?php echo esc_html($page_title); ?></h1>
                </div>
                <div>
                    <?php submit_button('Save Changes', 'primary large'); ?>
                </div>
            </header>

            <?php
            // Sections rendered below
            ?>
    <?php
}

// REVERT: Simplified closing wrapper
function wph_chatbot_form_wrapper_end() {
    ?>
        </form> <!-- This closing tag matches the opening tag in wrapper_start -->

        <!-- NEW: Footer Added -->
        <footer class="mt-12 pt-4 border-t border-border-color text-center text-text-muted text-sm">
            Customized and Improved By <a href="https://shurikenit.com" target="_blank" class="text-primary hover:underline font-medium">ShurikenIT</a>
        </footer>
    </div>
    <?php
}

// --- Utility function for rendering a card ---
function wph_chatbot_render_card($title, $content_callback) {
    ?>
    <div class="bg-white rounded-lg shadow mt-8">
        <div class="p-6 border-b border-border-color">
            <h2 class="text-xl font-semibold text-text-primary">
                <?php echo esc_html($title); ?>
            </h2>
        </div>
        <div class="p-6">
            <table class="form-table wph-chatbot-form-table">
                <?php $content_callback(); ?>
            </table>
        </div>
    </div>
    <?php
}

// --- Utility function for rendering form fields ---
// (No changes needed in these helper functions: wph_chatbot_render_text_field, wph_chatbot_render_color_field, etc.)
// ... [Keep all wph_chatbot_render_*_field functions as they were] ...
function wph_chatbot_render_text_field($name, $label, $description = '', $placeholder = '', $type = 'text') {
    ?>
    <tr valign="top">
        <th scope="row" class="w-1/3">
            <label for="<?php echo esc_attr($name); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html($label); ?></label>
        </th>
        <td class="w-2/3">
            <input
                type="<?php echo esc_attr($type); ?>"
                id="<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr(get_option($name)); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
            />
            <?php if ($description) : ?>
                <p class="text-sm text-text-muted mt-2"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
function wph_chatbot_render_color_field($name, $label, $description = '', $default_color = '#00665E') {
    $color = get_option($name, $default_color);
    ?>
    <tr valign="top">
        <th scope="row" class="w-1/3">
            <label for="<?php echo esc_attr($name); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html($label); ?></label>
        </th>
        <td class="w-2/3">
            <input
                type="text"
                id="<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($color); ?>"
                class="wph-color-picker"
                data-default-color="<?php echo esc_attr($default_color); ?>"
            />
            <?php if ($description) : ?>
                <p class="text-sm text-text-muted mt-2"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
function wph_chatbot_render_textarea_field($name, $label, $description = '', $rows = 5) {
    ?>
    <tr valign="top">
        <th scope="row" class="w-1/3">
            <label for="<?php echo esc_attr($name); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html($label); ?></label>
        </th>
        <td class="w-2/3">
            <textarea
                id="<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>"
                rows="<?php echo esc_attr($rows); ?>"
                class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
            ><?php echo esc_textarea(get_option($name)); ?></textarea>
            <?php if ($description) : ?>
                <p class="text-sm text-text-muted mt-2"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
function wph_chatbot_render_checkbox_field($name, $label, $description = '') {
    ?>
    <tr valign="top">
        <th scope="row" class="w-1/3">
            <label for="<?php echo esc_attr($name); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html($label); ?></label>
        </th>
        <td class="w-2/3">
            <label class="flex items-center">
                <input
                    type="checkbox"
                    id="<?php echo esc_attr($name); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    <?php checked(1, get_option($name, 1), true); ?>
                    value="1"
                    class="h-5 w-5 rounded border-border-color text-primary focus:ring-primary"
                />
                <span class="ml-3 text-base text-text-secondary"><?php echo esc_html($description); ?></span>
            </label>
        </td>
    </tr>
    <?php
}
function wph_chatbot_render_select_field($name, $label, $options, $description = '') {
    ?>
    <tr valign="top">
        <th scope="row" class="w-1/3">
            <label for="<?php echo esc_attr($name); ?>" class="text-base font-semibold text-text-primary"><?php echo esc_html($label); ?></label>
        </th>
        <td class="w-2/3">
            <select
                id="<?php echo esc_attr($name); ?>"
                name="<?php echo esc_attr($name); ?>"
                class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
            >
                <?php foreach ($options as $value => $text) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected(get_option($name), $value); ?>>
                        <?php echo esc_html($text); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($description) : ?>
                <p class="text-sm text-text-muted mt-2"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
function wph_chatbot_render_image_uploader_field($name, $label, $description = '') {
    $image_url = get_option($name, '');
    ?>
    <tr valign="top">
        <th scope="row" class="w-1/3">
            <label class="text-base font-semibold text-text-primary"><?php echo esc_html($label); ?></label>
        </th>
        <td class="w-2/3">
            <div class="flex items-center gap-4">
                <div class="w-20 h-20 bg-bg-light border border-border-color rounded-lg flex items-center justify-center overflow-hidden">
                    <?php if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="Preview" class="wph-image-uploader-preview" id="<?php echo esc_attr($name); ?>_preview" style="max-width: 100%; height: auto; object-fit: cover;">
                    <?php else : ?>
                        <img src="" alt="Preview" class="wph-image-uploader-preview hidden" id="<?php echo esc_attr($name); ?>_preview" style="max-width: 100%; height: auto; object-fit: cover;">
                        <span class="text-text-muted" id="<?php echo esc_attr($name); ?>_placeholder">No image</span>
                    <?php endif; ?>
                </div>
                <div>
                    <input type="hidden" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($image_url); ?>" />
                    <button type="button" class="wph-image-upload-button button button-secondary">
                        Upload Image
                    </button>
                    <button type="button" class="wph-image-remove-button button button-secondary" style="<?php echo $image_url ? '' : 'display:none;'; ?>">
                        Remove
                    </button>
                    <?php if ($description) : ?>
                        <p class="text-sm text-text-muted mt-2"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </td>
    </tr>
    <?php
}

// ==================================================================
// PAGE CALLBACK FUNCTIONS
// ==================================================================

// Main dashboard page
function wph_chatbot_main_page() {
    // Get total entries count
    $entry_count = wp_count_posts('wph_entries');
    $total_entries = $entry_count->publish ?? 0;

    // Get bot status
    $bot_enabled = get_option('wph_chatbot_enabled', 1);

    ?>
    <div class="wrap wph-chatbot-admin-wrap font-sans text-text-primary">
         <script>
            // Configure Tailwind for our admin page based on the style guide
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'sans-serif'],
                        },
                        colors: {
                            'primary': '#3E64DE',
                            'primary-light': '#F0F3FF',
                            'primary-hover': '#2F50BB',
                            'success': '#27AE60',
                            'warning': '#FDB32C',
                            'danger': '#EB5757',
                            'info': '#2D9CDB',
                            'text-primary': '#1A2133',
                            'text-secondary': '#5B616F',
                            'text-muted': '#9FA3AB',
                            'border-color': '#E3E5E8',
                            'bg-muted': '#F8F9FA',
                            'bg-light': '#FAFAFB',
                        },
                        borderRadius: {
                            'DEFAULT': '8px',
                            'lg': '16px',
                            'sm': '4px',
                        },
                        boxShadow: {
                            'DEFAULT': '0px 2px 4px 0px rgba(118, 126, 148, 0.08), 0px 0px 2px 0px rgba(139, 147, 171, 0.12)',
                        }
                    }
                }
            }
        </script>

        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-8">
            <div>
                 <!-- --- CHANGE: Renamed Page Title --- -->
                <h1 class="text-3xl font-bold text-text-primary">
                    Shuriken AI Chatbot Dashboard
                </h1>
                <p class="text-text-secondary mt-1">
                    Welcome! Here's an overview of your chatbot's activity.
                </p>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg bg-primary-light text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary font-medium">Total Entries</p>
                        <p class="text-3xl font-bold text-text-primary"><?php echo esc_html($total_entries); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-lg <?php echo $bot_enabled ? 'bg-green-100 text-success' : 'bg-red-100 text-danger'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path><path d="<?php echo $bot_enabled ? 'm9 12 2 2 4-4' : 'm15 9-6 6m0-6 6 6'; ?>"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm text-text-secondary font-medium">Chatbot Status</p>
                        <p class="text-3xl font-bold text-text-primary"><?php echo $bot_enabled ? 'Online' : 'Offline'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links Card -->
        <div class="bg-white rounded-lg shadow mt-8">
            <div class="p-6 border-b border-border-color">
                <h2 class="text-xl font-semibold text-text-primary">
                    Quick Links
                </h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="<?php echo admin_url('edit.php?post_type=wph_entries'); ?>" class="p-4 border border-border-color rounded-lg hover:bg-bg-light hover:shadow-sm transition-all">
                    <h3 class="text-base font-semibold text-primary">View Entries</h3>
                    <p class="text-sm text-text-secondary mt-1">See all the leads and conversations captured by the bot.</p>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wph-chatbot-train'); ?>" class="p-4 border border-border-color rounded-lg hover:bg-bg-light hover:shadow-sm transition-all">
                    <h3 class="text-base font-semibold text-primary">Train Bot</h3>
                    <p class="text-sm text-text-secondary mt-1">Update your bot's knowledge and organization info.</p>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wph-chatbot-settings'); ?>" class="p-4 border border-border-color rounded-lg hover:bg-bg-light hover:shadow-sm transition-all">
                    <h3 class="text-base font-semibold text-primary">Configure Settings</h3>
                    <p class="text-sm text-text-secondary mt-1">Adjust API keys, appearance, and other core settings.</p>
                </a>
            </div>
        </div>

         <!-- NEW: Footer Added -->
        <footer class="mt-12 pt-4 border-t border-border-color text-center text-text-muted text-sm">
            Customized and Improved By <a href="https://shurikenit.com" target="_blank" class="text-primary hover:underline font-medium">ShurikenIT</a>
        </footer>
    </div>
    <?php
}

// "Train Bot" page
function wph_chatbot_train_bot_page() {
    // --- CHANGE: Renamed Page Title ---
    wph_chatbot_form_wrapper_start('wph-chatbot-train', 'Train Shuriken AI Bot', 'wph_chatbot_train_group');

    wph_chatbot_render_card('Bot Knowledge Base', function() {
        wph_chatbot_render_textarea_field(
            'wph_organization_info',
            'Organization Information and Train AI',
            'Provide all the information about your company, products, and services here. This is the primary knowledge base for the AI.',
            15
        );
        wph_chatbot_render_textarea_field(
            'wph_welcome_message',
            'Welcome Message',
            'The first message the chatbot sends to the user.',
            3
        );
        wph_chatbot_render_textarea_field(
            'wph_fallback_responses',
            'Fallback Responses',
            "The message the bot sends when it doesn't understand a query.",
            3
        );
    });

    wph_chatbot_render_card('Initial Query Buttons', function() {
        ?>
        <tr valign="top">
            <th scope="row" class="w-1/3">
                <label class="text-base font-semibold text-text-primary">Quick-Reply Buttons</label>
                <p class="text-sm text-text-muted mt-2 font-normal">These buttons appear at the start of the chat. Leave blank to hide.</p>
            </th>
            <td class="w-2/3">
                <div class="flex flex-col gap-4">
                    <input
                        type="text"
                        name="wph_button_1_query"
                        value="<?php echo esc_attr(get_option('wph_button_1_query', 'I want your help !!')); ?>"
                        placeholder="Button 1 Text"
                        class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                    <input
                        type="text"
                        name="wph_button_2_query"
                        value="<?php echo esc_attr(get_option('wph_button_2_query', 'I want some Discounts')); ?>"
                        placeholder="Button 2 Text"
                        class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                    <input
                        type="text"
                        name="wph_button_3_query"
                        value="<?php echo esc_attr(get_option('wph_button_3_query', '')); ?>"
                        placeholder="Button 3 Text"
                        class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                    <input
                        type="text"
                        name="wph_button_4_query"
                        value="<?php echo esc_attr(get_option('wph_button_4_query', '')); ?>"
                        placeholder="Button 4 Text"
                        class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                    <input
                        type="text"
                        name="wph_button_5_query"
                        value="<?php echo esc_attr(get_option('wph_button_5_query', '')); ?>"
                        placeholder="Button 5 Text"
                        class="w-full max-w-lg p-3 border border-border-color rounded-lg shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                </div>
            </td>
        </tr>
        <?php
    });

    wph_chatbot_form_wrapper_end(); // Includes the footer
}


// Settings page
function wph_chatbot_settings_page() {
     // --- CHANGE: Renamed Page Title ---
    wph_chatbot_form_wrapper_start('wph-chatbot-settings', 'Shuriken AI Chatbot Settings', 'wph_chatbot_settings_group');

    // API Settings Card
    wph_chatbot_render_card('API & General Settings', function() {
        wph_chatbot_render_text_field(
            'wph_gemini_api_key',
            'Gemini API Key',
            'Get your API key from Google AI Studio.',
            'Enter your Gemini API Key',
            'password'
        );
        wph_chatbot_render_checkbox_field(
            'wph_chatbot_enabled',
            'Chatbot On/Off',
            'Enable or disable the chatbot on your website.'
        );
        wph_chatbot_render_select_field(
            'wph_chatbot_position',
            'Chatbot Position',
            [
                'bottom-right' => 'Bottom Right',
                'bottom-left' => 'Bottom Left'
            ],
            'Choose where the chatbot launcher appears.'
        );
    });

    // Appearance Card
    wph_chatbot_render_card('Chatbot Appearance', function() {
        wph_chatbot_render_text_field(
            'wph_chatbot_header_title',
            'Header Title',
            'The title displayed at the top of the chat window.',
            'Shuriken AI Chatbot' // --- CHANGE: Default Header Title ---
        );
        wph_chatbot_render_color_field(
            'wph_chatbot_theme_color',
            'Theme Color',
            'Controls the header, buttons, and border color of the chatbot.',
            '#00665E' // Default teal color
        );
        wph_chatbot_render_image_uploader_field(
            'wph_user_image',
            'User Image',
            'Upload an image to be used as the user\'s avatar in the chat.'
        );
        wph_chatbot_render_image_uploader_field(
            'wph_bot_image',
            'Bot Image',
            'Upload an image to be used as the bot\'s avatar.'
        );
    });

    wph_chatbot_form_wrapper_end(); // Includes the footer
}


function wph_sanitize_apostrophe($input) {
    // This sanitization is a bit aggressive, but keeping it from original code
    // A better approach would be to use standard sanitization like sanitize_text_field
    return str_replace('"', "'", $input);
}

// Register settings
add_action('admin_init', 'wph_chatbot_settings_init');

function wph_chatbot_settings_init() {

    // Settings for the 'wph_chatbot_settings_group'
    register_setting('wph_chatbot_settings_group', 'wph_gemini_api_key', 'sanitize_text_field');
    register_setting('wph_chatbot_settings_group', 'wph_chatbot_enabled', [
        'default' => 1,
        'type' => 'integer',
        'sanitize_callback' => 'absint',
    ]);
    register_setting('wph_chatbot_settings_group', 'wph_chatbot_position', 'sanitize_text_field');
    register_setting('wph_chatbot_settings_group', 'wph_user_image', 'esc_url_raw');
    register_setting('wph_chatbot_settings_group', 'wph_bot_image', 'esc_url_raw');
    register_setting('wph_chatbot_settings_group', 'wph_chatbot_header_title', 'sanitize_text_field');
    register_setting('wph_chatbot_settings_group', 'wph_chatbot_theme_color', 'sanitize_hex_color');

    // Settings for the 'wph_chatbot_train_group'
    register_setting('wph_chatbot_train_group', 'wph_welcome_message', 'sanitize_textarea_field');
    register_setting('wph_chatbot_train_group', 'wph_fallback_responses', 'sanitize_textarea_field');
    register_setting('wph_chatbot_train_group', 'wph_organization_info', 'sanitize_textarea_field');
    register_setting('wph_chatbot_train_group', 'wph_button_1_query', 'sanitize_text_field');
    register_setting('wph_chatbot_train_group', 'wph_button_2_query', 'sanitize_text_field');
    register_setting('wph_chatbot_train_group', 'wph_button_3_query', 'sanitize_text_field');
    register_setting('wph_chatbot_train_group', 'wph_button_4_query', 'sanitize_text_field');
    register_setting('wph_chatbot_train_group', 'wph_button_5_query', 'sanitize_text_field');
}
?>

