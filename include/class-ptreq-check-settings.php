<?php


// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}


if (! class_exists('PTREQ_CHECK_SETTINGS')) {

    /**
     * PTREQ_CHECK_SETTINGS
     */
    class PTREQ_CHECK_SETTINGS {

        /**
         * construction
         */
        function __construct() {
            add_action('admin_enqueue_scripts', [$this, 'ptreq_enqueue_script']);
            add_action('wp_insert_post_data', [$this, 'ptreq_check_title_length_setting'], 10, 3);
        }


        public static function get_allowed_post_types() {
            $allowed_post_types = get_option('ptreq_post_types', []);
            if (empty($allowed_post_types)) {
                // If no post types are selected, return all public post types
                $allowed_post_types = array_keys(get_post_types(['public' => true]));
            }
            return $allowed_post_types;
        }

        /** 
         * Enqueue the JavaScript file for the post title required functionality
         * This function checks if the current page is a post edit or list page,
         * and if the post type is allowed based on the settings.
         * in backend admin area
         */
        function ptreq_enqueue_script() {
            global $pagenow,  $post_type;
            if ($pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php') {
                $allowed_post_types = self::get_allowed_post_types();
                // Check if the current post type is in the allowed post types
                if (in_array($post_type, $allowed_post_types)) {
                    $js_file_path = PTREQ_URL . '/assets/js/post-title-required.js';
                    wp_enqueue_script(
                        'post-title-required-script',
                        $js_file_path,
                        array('jquery'),
                        filemtime(get_stylesheet_directory($js_file_path)),
                        array(
                            'in_footer' => true,
                            'strategy' => 'defer'
                        )
                    );
                    $characterLimit = 100;
                    $ptreq_character_limit = (int) get_option('ptreq_character_limit');
                    if ($ptreq_character_limit) {
                        $characterLimit = $ptreq_character_limit;
                    }
                    wp_localize_script('post-title-required-script', 'data_obj', [
                        'ptreq_character_limit' => $characterLimit
                    ]);
                }
            }
        }


        /**
         * ===================================================
         * Enforce the title character limit when saving posts
         * ===================================================
         */
        function ptreq_check_title_length_setting($data, $postarr, $unsanitized_postarr) {
            try {

                // Skip autosaves, revisions, and deletions
                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $data;
                if (wp_is_post_revision($postarr['ID'])) return $data;
                if ($data['post_status'] == 'trash' || $data['post_status'] == 'draft') {
                    return $data;
                }

                // 
                $post_type = $data['post_type'];
                $character_limit = (int)get_option('ptreq_character_limit', 100);
                $selected_post_types = self::get_allowed_post_types();

                // Check if the current post type is one of the selected types
                if (in_array($post_type, $selected_post_types)) {
                    $title_length = (int)mb_strlen(trim($data['post_title']));

                    // If the title is shorter than the required limit, prevent saving and show an error
                    if ($title_length > $character_limit) {
                        // Display error message
                        wp_die(
                            sprintf(
                                'The title is too long! It must be at maximum %d characters long. Please correct it.',
                                esc_attr($character_limit)
                            ),
                            'Title Too long'
                        );
                    }
                    if (!$title_length) {
                        wp_die(__('Title is required.'));
                    }
                }
                return $data;
            } catch (\Throwable $th) {
                error_log($th->getMessage());
            }
        }

        /**
         * ==== END ====
         */
    }
}