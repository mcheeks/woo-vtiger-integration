<?php

function wvi_settings_setup() {
	add_menu_page('WooCommerce vTiger Settings', 'WooCommerce vTiger Settings', 'manage_options', 'wvi-settings-page', 'wvi_settings_page');
}
add_action('admin_menu', 'wvi_settings_setup');

function wvi_settings_init() {

	register_setting('wviPlugin', 'wvi_api_settings', 'wvi_api_validate');
	add_settings_section(
		'wvi_api_wviPlugin_section',
		__('vTiger API Settings', 'wvi'),
		'wvi_api_settings_section_callback',
		'wviPlugin'
	);
	add_settings_field(
			'wvi_api_vtiger_url',
			__('vTiger URL', 'wvi'),
			'wvi_api_vtiger_url_render',
			'wviPlugin',
			'wvi_api_wviPlugin_section'
		);
	add_settings_field(
		'wvi_api_username',
		__('vTiger Username', 'wvi'),
		'wvi_api_username_render',
		'wviPlugin',
		'wvi_api_wviPlugin_section'
	);
	add_settings_field(
		'wpi_api_accesskey',
		__('vTiger Access Key', 'wvi'),
		'wvi_api_accesskey_render',
		'wviPlugin',
		'wvi_api_wviPlugin_section'
	);
	add_settings_section(
		'wvi_db_settings_section',
		__('vTiger Database Settings', 'wvi'),
		'wvi_db_settings_callback',
		'wviPlugin'
	);

	add_settings_field(
		'wvi_db_username',
		__('vTiger Database Username', 'wvi'),
		'wvi_db_username_render',
		'wviPlugin',
		'wvi_db_settings_section'
	);

	add_settings_field(
		'wvi_db_pw',
		__('vTiger Database Password'),
		'wvi_db_pw_render',
		'wviPlugin',
		'wvi_db_settings_section'
	);

	add_settings_field(
		'wvi_db_name',
		__('vTiger Database Name'),
		'wvi_db_name_render',
		'wviPlugin',
		'wvi_db_settings_section'
	);
}
add_action('admin_init', 'wvi_settings_init');

function wvi_api_username_render() {
	$options = get_option('wvi_api_settings');
	// print_r($options);
	?>
	<input type='text' name='wvi_api_settings[wvi_api_username]' value='<?php echo $options['wvi_api_username']; ?>'>
	<?php
}

function wvi_api_accesskey_render() {
	$options = get_option('wvi_api_settings');
	?>
	<input type='password' name='wvi_api_settings[wvi_api_accesskey]' value='<?php echo $options['wvi_api_accesskey']; ?>'>
	<?php
}

function wvi_api_vtiger_url_render() {
	$options = get_option('wvi_api_settings');
	//echo $options['wvi_api_vtiger_url'];
	?>
	<input type="text" name='wvi_api_settings[wvi_api_vtiger_url]' value='<?php echo $options['wvi_api_vtiger_url']; ?>'>
	<?php
}

function wvi_db_username_render() {
	$options = get_option('wvi_api_settings');
	?>
	<input type='text' name='wvi_api_settings[wvi_db_username]' value='<?php echo (isset($options['wvi_db_username']) ? $options['wvi_db_username'] : ''); ?>'>
	<?php
}

function wvi_db_pw_render() {
	$options = get_option('wvi_api_settings');
	?>
	<input type='password' name='wvi_api_settings[wvi_db_pw]' value='<?php echo (isset($options['wvi_db_pw']) ?  $options['wvi_db_pw'] : ''); ?>'>
	<?php
}

function wvi_db_name_render() {
	$options = get_option('wvi_api_settings');
	?>
	<input type='text' name='wvi_api_settings[wvi_db_name]' value='<?php echo (isset($options['wvi_db_name']) ? $options['wvi_db_name'] : '');?>'>
	<?php
}


function wvi_api_settings_section_callback() {
	echo __('Enter the settings from your vTiger profile to connect', 'wvi');
}

function wvi_db_settings_callback() {
	echo __('Enter the vTiger Database settings', 'wvi');
}

function wvi_api_validate($input) {
	$output = array();

	foreach($input as $key => $value) {
		if(isset($input[$key])) {
			echo "this is validate function";
			$output[$key] = trim(stripslashes($input[$key]));
		}
	}

	return apply_filters('wpi_api_validate', $output, $input);
}

function wvi_settings_page() {
	?>
	<form action='options.php' method='post'>
		<h1>WooCommerce vTiger Integration Settings</h1>

		<?php 
		settings_fields('wviPlugin');
		do_settings_sections('wviPlugin');
		submit_button();
		?>
	</form>
	<?php
}