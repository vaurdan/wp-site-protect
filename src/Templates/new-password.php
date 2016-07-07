<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="pt-PT" prefix="og: http://ogp.me/ns#">
<head>
	<?php wp_head() ?>
</head>
<body id="error-page">

<h1><?php bloginfo( 'name' ) ?></h1>
<h2><?php esc_html_e('Reset password', 'wp_site_protect')?></h2>
<p><?php esc_html_e('Before you continue, you must reset the password to something different. The password should be at least Medium or Strong.', 'wp_site_protect'); ?></p>

<?php
	use mowta\SiteProtect\SiteProtect as SiteProtect;
	SiteProtect::reset_password_form();
?>

</body>
</html>
