<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="pt-PT" prefix="og: http://ogp.me/ns#">
<head>
	<?php wp_head() ?>
</head>
<body id="error-page">

<h1><?php bloginfo( 'name' ) ?></h1>

<?php
	echo wp_kses_post( \mowta\SiteProtect\WPSPSettings::get_password_content() );

	use mowta\SiteProtect\SiteProtect as SiteProtect;
	SiteProtect::authenticate_form();
?>

</body>
</html>
