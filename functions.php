<?php
namespace {

	use mowta\SiteProtect\SiteProtect;

	function wpsp_auth_form() {
		SiteProtect::authenticate_form();
	}

	function wpsp_reset_password_form() {
		SiteProtect::reset_password_form();
	}

}
