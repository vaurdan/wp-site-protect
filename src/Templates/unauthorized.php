<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="pt-PT" prefix="og: http://ogp.me/ns#">
<head>
	<?php wp_head() ?>
</head>
<body id="error-page">

<?php if( \mowta\SiteProtect\WPSPSettings::display_title() ): ?>
<h1><?php bloginfo( 'name' ) ?></h1>
<?php endif; ?>

<?php
echo "<p>". wp_kses_post( \mowta\SiteProtect\WPSPSettings::get_password_content() ) . "</p>";

	wpsp_auth_form();
?>

</body>
</html>
