<?php


// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}


if (! class_exists('PTREQ_CHECK_SETTINGS')) {

    /**
     * Handles the Post Title Required (PTR) plugin Check and validation.
     */
    class PTREQ_CHECK_SETTINGS {

        /**
         * Constructor.
         * Registers hooks for enqueueing scripts, checking title length, and AJAX handling.
         */
        function __construct() {
            add_action('admin_enqueue_scripts', [$this, 'ptreq_enqueue_script']);
            add_action('wp_insert_post_data', [$this, 'ptreq_check_title_length_setting'], 10, 3);
            add_action('wp_ajax_ptreq_getActualTitleLength', [$this, 'ptreq_getActualTitleLength']);
        }

        /**
         * Get allowed post types from settings.
         *
         * @return array List of allowed post types.
         */
        public static function get_allowed_post_types() {
            $allowed_post_types = get_option('ptreq_post_types', []);
            if (empty($allowed_post_types)) {
                // If no post types are selected, return all public post types
                $allowed_post_types = array_keys(get_post_types(['public' => true]));
            }
            return $allowed_post_types;
        }


        /**
         * AJAX callback: Get the actual visible title length.
         *
         * @return void
         */
        function ptreq_getActualTitleLength() {
            // Verify _nonce
            if (!isset($_POST['_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_nonce'])), 'ptrq_titlelength')) {
                echo json_encode(
                    [
                        'status' => false,
                    ]
                );
                wp_die();
            }
            // 
            $title = '';
            if (isset($_POST['ptrq_title'])) {
                $title = sanitize_text_field(wp_unslash($_POST['ptrq_title']));
            }

            echo json_encode(
                [
                    'status' => true,
                    'title' => $title,
                    'length' => self::get_visible_length_with_ignore($title)
                ]
            );
            wp_die();
        }

        /**
         * Get ignore character options.
         *
         * @param string $returntype Type of data to return: '', 'key', 'key_label', 'key_htmlnum', or 'unicode'.
         * @return array The ignore character options or derived list based on $returntype.
         */
        public static function get_ignore_char_options($returntype = '') {
            $ignorecharoptions = [
                'u00AD' => [
                    'htmlname' => '&shy;',
                    'htmlnum'  => '&#173;',
                    'label'    => 'Soft Hyphen [&amp;shy;] [&amp#173;]',
                ],
                'u00A0' => [
                    'htmlname' => '&nbsp;',
                    'htmlnum'  => '&#160;',
                    'label'    => 'Non-breaking Space [&amp;nbsp;] [&amp#160;]',
                ],
                'u200B' => [
                    'htmlname' => '&ZeroWidthSpace;',
                    'htmlnum'  => '&#8203;',
                    'label'    => 'Zero-Width Space [&amp;ZeroWidthSpace;] [&amp;#8203;]',
                ],
                'u200E' => [
                    'htmlname' => '&lrm;',
                    'htmlnum'  => '&#8206;',
                    'label'    => 'Left-to-Right Mark [&amp;lrm;] [&amp;#8206;]',
                ],
                'u200F' => [
                    'htmlname' => '&rlm;',
                    'htmlnum'  => '&#8207;',
                    'label'    => 'Right-to-Left Mark [&amp;rlm;] [&amp;#8207;]',
                ],
                'u202F' => [
                    'htmlname' => '&nbsp;',
                    'htmlnum'  => '&#8239;',
                    'label'    => 'Narrow No-Break Space [&amp;#8239;]',
                ],
                'u205F' => [
                    'htmlname' => '&#8287;',
                    'htmlnum'  => '&#8287;',
                    'label'    => 'Medium Mathematical Space [&amp;#8287;]',
                ],
                'u3000' => [
                    'htmlname' => '&#12288;',
                    'htmlnum'  => '&#12288;',
                    'label'    => 'Ideographic Space [&amp;#12288;]',
                ]
            ];


            if ($returntype == 'key' || $returntype == 'unicode') {
                $ignorecharoptions = array_keys($ignorecharoptions);
            } else  if ($returntype == 'key_label') {
                foreach ($ignorecharoptions as $key => &$data) {
                    $data = isset($data['label']) ? $data['label'] : '';
                }
            } else  if ($returntype == 'key_htmlnum') {
                foreach ($ignorecharoptions as $key => &$data) {
                    $data = isset($data['htmlnum']) ? $data['htmlnum'] : '';
                }
            }
            return $ignorecharoptions;
        }

        /**
         * Enqueue the JavaScript file for enforcing post title requirements.
         *
         * Checks if the current page is a post edit or list page,
         * and only enqueues the script if the post type is allowed.
         *
         * @return void
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
                    wp_localize_script('post-title-required-script', 'ptreqAjax', [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'action_name' => 'ptreq_getActualTitleLength',
                        'nonce'    => wp_create_nonce('ptrq_titlelength'),
                        'ptreq_character_limit' => $characterLimit,
                    ]);
                }
            }
        }


        /**
         * Enforce the title character limit when saving posts.
         *
         * @param array $data                 Sanitized post data.
         * @param array $postarr              Raw post array.
         * @param array $unsanitized_postarr  Original unsanitized post array.
         * @return array Modified post data.
         */
        function ptreq_check_title_length_setting($data, $postarr, $unsanitized_postarr) {
            try {

                // Skip autosaves, revisions, and deletions
                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $data;
                if (wp_is_post_revision($postarr['ID'])) return $data;
                if (in_array($data['post_status'], ['trash', 'draft'], true)) {
                    return $data;
                }

                // 
                $post_type = $data['post_type'];
                $character_limit = (int)get_option('ptreq_character_limit', 100);
                $selected_post_types = self::get_allowed_post_types();

                // Check if the current post type is one of the selected types
                if (in_array($post_type, $selected_post_types)) {
                    $title_length = self::get_visible_length_with_ignore($data['post_title']);
                    // If the title is shorter than the required limit, prevent saving and show an error
                    if ($title_length > $character_limit) {
                        // Display error message
                        wp_die(
                            sprintf(
                                esc_html('The title is too long! It must be at maximum %d characters long. Please correct it.'),
                                esc_attr($character_limit)
                            ),
                            esc_html('Title Too Long')
                        );
                    }
                    if (!$title_length) {
                        wp_die(esc_html('Title is required.'));
                    }
                }
                return $data;
            } catch (\Throwable $th) {
                wp_die(esc_html('something went wrong in post title required.'));
            }
        }

        /**
         * Get visible length of a title after removing ignored characters.
         *
         * @param string $title The post title.
         * @return int The visible character length.
         */
        public static function get_visible_length_with_ignore($title) {
            // 1. Decode HTML entities in title
            $decodedTitle = html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // 2. Get ignore list keys from options (['u00AD','u00A0',...])
            $ignoreKeys = get_option('ptreq_ignore_chars', self::get_ignore_char_options('unicode'));

            // 3. Map keys to actual characters
            $ignoreCharMap = self::get_ignore_char_options();

            foreach ($ignoreKeys as $key) {
                if (!isset($ignoreCharMap[$key])) {
                    continue;
                }
                $char = html_entity_decode($ignoreCharMap[$key]['htmlname'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (mb_strpos($decodedTitle, $char) !== false) {
                    // Remove it
                    $decodedTitle = str_replace($char, '', $decodedTitle);
                }
            }
            return mb_strlen($decodedTitle, 'UTF-8');
        }

        /**
         * ==== END ====
         */
    }
}
