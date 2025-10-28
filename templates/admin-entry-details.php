<?php
// Hook to add the meta box
add_action('add_meta_boxes', 'wph_entries_add_meta_box');
function wph_entries_add_meta_box() {
    add_meta_box(
        'wph_entries_meta_box',
        'Entry Details',
        'wph_entries_meta_box_callback',
        'wph_entries',
        'normal',
        'high'
    );
}

// Callback function for rendering the content of the meta box
function wph_entries_meta_box_callback($post) {
    // Retrieve meta values
    $name = get_post_meta($post->ID, '_name', true);
    $email = get_post_meta($post->ID, '_email', true);
    $phone = get_post_meta($post->ID, '_phone', true);
    $query = get_post_meta($post->ID, '_query', true);
    $current_page_url = get_post_meta($post->ID, '_current_page_url', true);
    $user_country = get_post_meta($post->ID, '_user_country', true);
    $chats = get_post_meta($post->ID, '_chats', true);
    $chats_data = json_decode($chats, true);
    $leadId = get_post_meta($post->ID, '_lead_id', true);

    // Get avatar URLs from settings, with fallbacks
    $default_user_avatar = CHATBOT_PLUGIN_URL . 'assets/images/user-avatar.png';
    $default_bot_image = CHATBOT_PLUGIN_URL . 'assets/images/bot-logo.png';
    $user_avatar_url = get_option('wph_user_image', $default_user_avatar);
    $bot_image_url = get_option('wph_bot_image', $default_bot_image);

    // Ensure URLs are valid, even if option is empty
    if (empty($user_avatar_url)) $user_avatar_url = $default_user_avatar;
    if (empty($bot_image_url)) $bot_image_url = $default_bot_image;
    
    ?>
    <!-- Wrapper to integrate with new admin UI -->
    <div class="wph-chatbot-admin-wrap font-sans text-text-primary">
         <script>
            // Configure Tailwind for our admin page
            if (typeof tailwind !== 'undefined') {
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
            }
        </script>

        <main class="flex-1">
            <div class="max-w-7xl mx-auto">
                
                <!-- User Details Card -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-border-color">
                        <h2 class="text-xl font-semibold text-text-primary">
                            User Details
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="text-sm font-medium text-text-muted">Name</label>
                                <p class="text-base text-text-primary font-medium"><?php echo esc_html($name); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-text-muted">Email</label>
                                <p class="text-base text-text-primary font-medium"><?php echo esc_html($email); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-text-muted">Phone</label>
                                <p class="text-base text-text-primary font-medium"><?php echo esc_html($phone); ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-text-muted">User Country</label>
                                <p class="text-base text-text-primary font-medium"><?php echo esc_html($user_country); ?></p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium text-text-muted">Chat Page URL</label>
                                <p class="text-base text-text-primary font-medium truncate">
                                    <a href="<?php echo esc_url($current_page_url); ?>" target="_blank" class="text-primary hover:underline">
                                        <?php echo esc_html($current_page_url); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat History Card -->
                <div class="bg-white rounded-lg shadow mt-8">
                    <div class="p-6 border-b border-border-color">
                        <h2 class="text-xl font-semibold text-text-primary">
                            Chat History
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <?php if (is_array($chats_data) && !empty($chats_data)) : ?>
                            
                            <!-- Initial Query -->
                            <div class="flex items-start gap-4">
                                <img class="w-10 h-10 rounded-full border border-border-color" src="<?php echo esc_url($user_avatar_url); ?>" alt="User Avatar">
                                <div class="flex-1">
                                    <div class="bg-primary text-white p-4 rounded-r-lg rounded-b-lg inline-block max-w-xl">
                                        <p class="text-sm font-bold mb-1">Initial Query:</p>
                                        <p class="text-base"><?php echo nl2br(esc_html($query)); ?></p>
                                    </div>
                                </div>
                            </div>

                            <?php
                            // Skip the first two messages (system prompt and confirmation)
                            $messages_to_skip = 2;
                            foreach ($chats_data as $index => $chat) :
                                if ($index < $messages_to_skip) continue;
                                if (!isset($chat['role']) || !isset($chat['parts']['text'])) continue;
                                
                                $text = nl2br(esc_html($chat['parts']['text'])); // Sanitize and format

                                if ($chat['role'] === 'user') :
                            ?>
                                <!-- User Message -->
                                <div class="flex items-start justify-end gap-4">
                                    <div class="flex-1 text-right">
                                        <div class="bg-primary text-white p-4 rounded-l-lg rounded-b-lg inline-block max-w-xl text-left">
                                            <p class="text-base"><?php echo $text; ?></p>
                                        </div>
                                    </div>
                                    <img class="w-10 h-10 rounded-full border border-border-color" src="<?php echo esc_url($user_avatar_url); ?>" alt="User Avatar">
                                </div>

                            <?php elseif ($chat['role'] === 'model') : ?>
                                <!-- AI Message -->
                                <div class="flex items-start gap-4">
                                    <img class="w-10 h-10 rounded-full border border-border-color" src="<?php echo esc_url($bot_image_url); ?>" alt="Bot Avatar">
                                    <div class="flex-1">
                                        <div class="bg-bg-light border border-border-color p-4 rounded-r-lg rounded-b-lg inline-block max-w-xl">
                                            <p class="text-base text-text-secondary"><?php echo $text; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>

                        <?php else : ?>
                            <p class="text-text-secondary text-center py-4">No chat history available after the initial query.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </main>
    </div>
    <?php
}
?>

