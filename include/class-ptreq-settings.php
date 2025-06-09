<?php

/**
 * Reference: 
 * https://developer.wordpress.org/reference/functions/register_setting/
 * https://developer.wordpress.org/reference/hooks/admin_menu/ 
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}


if (! class_exists('PTREQ_SETTINGS')) {

    /**
     * PTREQ_SETTINGS
     */
    class PTREQ_SETTINGS {

        public static $admin_page_slug = "post-title-required";

        /**
         * construction
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


        // Hook into the plugin action links filter
        public function ptreq_settings_link($links) {
            // Create the settings link
            $settings_link = '<a href="' . self::get_settings_page_url() . '">Settings</a>';
            // Append the link to the existing links array
            array_unshift($links, $settings_link);
            return $links;
        }

        // Register and define the settings.
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
        }


        // Register the menu page.
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
         * Render the settings page.
         * Callback function to display the content of the submenu page.
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
         * 
         */
        function render_ptreq_general_settings_box() {
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
                        $option = (get_option('ptreq_post_types')) ?: [];
                        $post_types = get_post_types(['public'   => true], 'objects');
                        // unset($post_types['attachment']);
                        foreach ($post_types  as $key => $value) {
                            $checked = '';
                            if (in_array($value->name, $option)) {
                                $checked = 'Checked';
                            }
                        ?>
                            <label for="post-type-<?php echo esc_attr($key); ?>">
                                <input type="checkbox" name="ptreq_post_types[]" id="post-type-<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value->name); ?>" <?php echo esc_attr($checked); ?> <?php checked(in_array($value->name, $option)); ?>>
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
                        <label for="ptreq_character_limit">Minimun Post Title Character Limit</label>
                    </th>
                    <td>
                        <?php $option = (int)get_option('ptreq_character_limit');
                        if (!$option) {
                            $option = 100;
                        } ?>
                        <input type="number" name="ptreq_character_limit" id="ptreq_character_limit" value="<?php echo esc_attr($option); ?>" class="regular-text" placeholder="100">
                        <p class="description">Default title character limit is 100.</p>
                    </td>
                </tr>
            </table>
<?php
        }


        // Sanitize the selected post types
        function ptreq_sanitize_post_types($input) {
            if (!is_array($input)) return [];
            return array_map('sanitize_text_field', $input);
        }

        /**
         * 
         * ===== END ======
         */
    }
}
