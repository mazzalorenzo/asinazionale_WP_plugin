<?php

namespace Asinazionale\Pages;

class Admin {
    /**
     * Admin settings: register options and add settings page under Settings.
     */

    public function init() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_settings']);
    }

    public function admin_menu() {
        add_options_page(
            'ASI Nazionale',
            'ASI Nazionale',
            'manage_options',
            'asinazionale-settings',
            [$this, 'settings_page']
        );
    }

    public function admin_settings() {
        register_setting('asinazionale_settings_group', 'asinazionale_user', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));
        register_setting('asinazionale_settings_group', 'asinazionale_pass', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));
    }

    function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>ASI Nazionale — Impostazioni</h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields('asinazionale_settings_group');
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="asinazionale_user">Username</label></th>
                        <td><input name="asinazionale_user" type="text" id="asinazionale_user" value="<?php echo esc_attr(get_option('asinazionale_user', '')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="asinazionale_pass">Password</label></th>
                        <td><input name="asinazionale_pass" type="password" id="asinazionale_pass" value="<?php echo esc_attr(get_option('asinazionale_pass', '')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}


