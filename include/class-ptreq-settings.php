<?php

/**
 * Plugin Settings for Post Title Required
 *
 * Provides an admin settings page to configure title length limit,
 * allowed post types, and invisible characters to ignore.
 *
 * References:
 * - https://developer.wordpress.org/reference/functions/register_setting/
 * - https://developer.wordpress.org/reference/hooks/admin_menu/
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}


if (! class_exists('PTREQ_SETTINGS')) {

    /**
     * Class PTREQ_SETTINGS
     *
     * Handles plugin settings, admin page, and sanitization.
     */
    class PTREQ_SETTINGS {

        public static $admin_page_slug = "post-title-required";

        /**
         * Constructor.
         *
         * Registers plugin action links, settings, and admin menu.
         */
        function __construct() {
            add_filter('plugin_action_links_' . PTREQ_BASENAME, [$this, 'ptreq_settings_link']);
            add_action('admin_init', [$this, 'ptreq_settings_init']);
            add_action('admin_menu', [$this, 'ptreq_settings_submenu']);
        }

        /**
         * Get the URL for the settings page
         *
         * @return string The URL for the settings page
         */
        public static function get_settings_page_url() {
            return 'options-general.php?page=' . self::$admin_page_slug;
        }


        /**
         * Add "Settings" link in the plugin action links.
         *
         * @param array $links Default plugin action links.
         * @return array Modified plugin action links with settings link added.
         */
        public function ptreq_settings_link($links) {
            $settings_link = '<a href="' . self::get_settings_page_url() . '">Settings</a>';
            array_unshift($links, $settings_link);
            return $links;
        }


        /**
         * Register and define the plugin settings.
         *
         * Includes character limit, allowed post types, and ignored characters.
         *
         * @return void
         */
        function ptreq_settings_init() {
            // Sanitize the character limit as an integer
            register_setting('ptreq_settings_group', 'ptreq_character_limit', [
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 100
            ]);

            // Sanitize the post types as an array of strings
            register_setting('ptreq_settings_group', 'ptreq_post_types', [
                'type' => 'array',
                'sanitize_callback' => [$this, 'ptreq_sanitize_post_types'],
                'default' => []
            ]);

            // Ignore characters
            register_setting('ptreq_settings_group', 'ptreq_ignore_chars', [
                'type' => 'array',
                'sanitize_callback' => [$this, 'ptreq_sanitize_ignore_chars'],
                'default' => ['&shy;', '&nbsp;', '&#8203;'],
            ]);
        }


        /**
         * Register submenu page under "Settings".
         *
         * @return void
         */
        function ptreq_settings_submenu() {
            add_options_page(
                'Post Title Required ', // Page title.
                'Post Title Required ', // Menu title.
                'manage_options',     // Capability required to see the menu.
                self::$admin_page_slug, // Menu slug.
                [$this, 'ptreq_setting_page_callback'] // Function to display the page content.
            );
        }

        /**
         * Render the settings page content.
         *
         * Uses WordPress metabox structure for UI consistency.
         *
         * @return void
         */
        function ptreq_setting_page_callback() {

            // Register metaboxes right before rendering (since add_meta_boxes won't fire)
            add_meta_box(
                'ptreq_general_settings',
                'General Settings',
                [$this, 'render_ptreq_general_settings_box'],
                'ptreq_settings_page',
                'normal',
                'default'
            ); ?>
            <div class="wrap">
                <h1 class="wp-heading-inline">Post Title Required</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ptreq_settings_group');
                    ?>
                    <div id="poststuff">
                        <div id="post-body" class="metabox-holder columns-2">
                            <div id="post-body-content">
                                <?php
                                do_meta_boxes('ptreq_settings_page', 'normal', null);
                                submit_button();
                                ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php
        }

        /**
         * Render the "General Settings" metabox.
         *
         * Displays options for post types, character limit, and ignored characters.
         *
         * @return void
         */
        function render_ptreq_general_settings_box() {
            // Current values
            $post_types_option = get_option('ptreq_post_types', []);
            $char_limit = get_option('ptreq_character_limit', 100);;
            $ignore_chars_options = get_option('ptreq_ignore_chars', PTREQ_CHECK_SETTINGS::get_ignore_char_options('unicode'));

            // Post types
            $post_types = get_post_types(['public' => true], 'objects');

            // Ignore character lists
            $all_ignore_chars_options = PTREQ_CHECK_SETTINGS::get_ignore_char_options('key_label')

        ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="ptreq_post_types">
                            Select Post Types To Apply Title Character Limit.
                        </label>
                    </th>
                    <td>
                        <?php
                        foreach ($post_types  as $key => $value) {
                            $checked = in_array($value->name, $post_types_option) ? 'checked' : '';
                        ?>
                            <label for="post-type-<?php echo esc_attr($key); ?>">
                                <input type="checkbox" name="ptreq_post_types[]" id="post-type-<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value->name); ?>" <?php echo esc_attr($checked); ?> <?php checked(in_array($value->name, $post_types_option)); ?>>
                                <?php echo esc_attr($value->label); ?>
                            </label>
                        <?php
                        }
                        echo '<p class="description">Title required character limit will only apply to selected post type. If all post type are unchecked, it will apply to all post type.</p>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ptreq_character_limit">Post Title Character Limit</label>
                    </th>
                    <td>
                        <input type="number" name="ptreq_character_limit" id="ptreq_character_limit" value="<?php echo esc_attr($char_limit); ?>" class="regular-text" placeholder="100">
                        <p class="description">Default title character limit is 100.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Ignore Title Characters</label></th>
                    <td>
                        <ul style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px 20px; list-style: none; margin: 0; padding: 0;">
                            <?php foreach ($all_ignore_chars_options as $entity => $label): ?>
                                <li>
                                    <label for="ptreq_ignore_chars_<?php echo esc_attr($entity) ?>">
                                        <input
                                            type="checkbox"
                                            name="ptreq_ignore_chars[]"
                                            id="ptreq_ignore_chars_<?php echo esc_attr($entity); ?>"
                                            value="<?php echo esc_attr($entity); ?>"
                                            <?php checked(in_array($entity, $ignore_chars_options, true)); ?>>
                                        <?php echo ($label);?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="description">Select invisible characters/entities to ignore when counting title length.</p>
                    </td>
                </tr>
            </table>
<?php
        }

        /**
         * Sanitize selected post types.
         *
         * @param mixed $input Input data.
         * @return array Sanitized array of post type names.
         */
        function ptreq_sanitize_post_types($input) {
            if (!is_array($input)) return [];
            return array_map('sanitize_text_field', $input);
        }

        /**
         * Sanitize selected ignored characters.
         *
         * @param mixed $input Input data.
         * @return array Sanitized array of unicode keys.
         */
        function ptreq_sanitize_ignore_chars($input) {
            if (!is_array($input)) return [];
            return array_map('sanitize_text_field', $input);
        }

        /**
         * 
         * ===== END ======
         */
    }
}
