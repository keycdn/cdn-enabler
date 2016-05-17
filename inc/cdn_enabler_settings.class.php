<?php

/**
* CDN_Enabler_Settings
*
* @since 0.0.1
*/

class CDN_Enabler_Settings
{


	/**
	* register settings
	*
	* @since   0.0.1
	* @change  0.0.1
	*/

	public static function register_settings()
	{
		register_setting(
			'cdn_enabler',
			'cdn_enabler',
			array(
				__CLASS__,
				'validate_settings'
			)
		);
	}


	/**
	* validation of settings
	*
	* @since   0.0.1
	* @change  1.0.3
	*
	* @param   array  $data  array with form data
	* @return  array         array with validated values
	*/

	public static function validate_settings($data)
	{
		return array(
			'url'		=> esc_url($data['url']),
			'dirs'		=> esc_attr($data['dirs']),
			'excludes'	=> esc_attr($data['excludes']),
			'relative'	=> (int)($data['relative']),
			'https'		=> (int)($data['https'])
		);
	}


	/**
	* add settings page
	*
	* @since   0.0.1
	* @change  0.0.1
	*/

	public static function add_settings_page()
	{
		$page = add_options_page(
			'CDN Enabler',
			'CDN Enabler',
			'manage_options',
			'cdn_enabler',
			array(
				__CLASS__,
				'settings_page'
			)
		);
	}


	/**
	* settings page
	*
	* @since   0.0.1
	* @change  1.0.3
	*
	* @return  void
	*/

	public static function settings_page()
	{ ?>
		<div class="wrap">
			<h2>
				<?php _e("CDN Enabler Settings", "cdn-enabler"); ?>
			</h2>

			<div class="notice notice-info">
				<p><?php printf( __('Combine CDN Enabler with <b><a href="%s">%s</a></b> for even faster WordPress performance.', 'cdn-enabler'), 'https://www.keycdn.com?utm_source=wp-admin&utm_medium=plugins&utm_campaign=cdn-enabler', 'KeyCDN'); ?></p>
			</div>

			<form method="post" action="options.php">
				<?php settings_fields('cdn_enabler') ?>

				<?php $options = CDN_Enabler::get_options() ?>

				<table class="form-table">

					<tr valign="top">
						<th scope="row">
							<?php _e("CDN URL", "cdn-enabler"); ?>
						</th>
						<td>
							<fieldset>
								<label for="cdn_enabler_url">
									<input type="text" name="cdn_enabler[url]" id="cdn_enabler_url" value="<?php echo $options['url']; ?>" size="64" class="regular-text code" />
								</label>

								<p class="description">
									<?php _e("Enter the CDN URL without trailing", "cdn-enabler"); ?> <code>/</code>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php _e("Included Directories", "cdn-enabler"); ?>
						</th>
						<td>
							<fieldset>
								<label for="cdn_enabler_dirs">
									<input type="text" name="cdn_enabler[dirs]" id="cdn_enabler_dirs" value="<?php echo $options['dirs']; ?>" size="64" class="regular-text code" />
									<?php _e("Default: <code>wp-content,wp-includes</code>", "cdn-enabler"); ?>
								</label>

								<p class="description">
									<?php _e("Assets in these directories will be pointed to the CDN URL. Enter the directories separated by", "cdn-enabler"); ?> <code>,</code>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php _e("Exclusions", "cdn-enabler"); ?>
						</th>
						<td>
							<fieldset>
								<label for="cdn_enabler_excludes">
									<input type="text" name="cdn_enabler[excludes]" id="cdn_enabler_excludes" value="<?php echo $options['excludes']; ?>" size="64" class="regular-text code" />
									<?php _e("Default: <code>.php</code>", "cdn-enabler"); ?>
								</label>

								<p class="description">
									<?php _e("Enter the exclusions (directories or extensions) separated by", "cdn-enabler"); ?> <code>,</code>
								</p>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php _e("Relative Path", "cdn-enabler"); ?>
						</th>
						<td>
							<fieldset>
								<label for="cdn_enabler_relative">
									<input type="checkbox" name="cdn_enabler[relative]" id="cdn_enabler_relative" value="1" <?php checked(1, $options['relative']) ?> />
									<?php _e("Enable CDN for relative paths (default: enabled).", "cdn-enabler"); ?>
								</label>
							</fieldset>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php _e("CDN HTTPS", "cdn-enabler"); ?>
						</th>
						<td>
							<fieldset>
								<label for="cdn_enabler_https">
									<input type="checkbox" name="cdn_enabler[https]" id="cdn_enabler_https" value="1" <?php checked(1, $options['https']) ?> />
									<?php _e("Enable CDN for HTTPS connections (default: disabled).", "cdn-enabler"); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</table>

				<?php submit_button() ?>
			</form>
		</div><?php
	}
}
