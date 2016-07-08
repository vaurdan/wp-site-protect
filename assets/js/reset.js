(function($) {

	$(document).ready(function() {

		// Load the password strenght meter, only if enabled
		if( passwords.strength != 'disabled' ) {
			$('input[type=submit]').attr('disabled','disabled');
			$('body').on('keyup', 'input[name=wpsp_password], input[name=wpsp_password_retyped]',
				function (event) {
					checkPasswordStrength(
						$('input[name=wpsp_password]'),         // First password field
						$('input[name=wpsp_password_retyped]'), // Second password field
						$('#password-strength'),           // Strength meter
						$('input[type=submit]'),           // Submit button
						['black', 'listed', 'word']        // Blacklisted words
					);
				}
			);
		}

		// Get the form
		var form = $('form[name="wpsp_reset"]');
		var submit = form.find("input[type='submit']");

		form.submit( function(e) {
			e.preventDefault();

			$("#wpsp-error").fadeOut();

			var nonce = form.find("input[name='_wpnonce']").val();
			var password = form.find("input[name='wpsp_password']").val();
			var password_repeat = form.find("input[name='wpsp_password_retyped']").val();
			
			if ( password !== password_repeat ) {
				$("#wpsp-error").text( passwords.different_password_error ).fadeIn();
				form.find("input[name^='wpsp_password']").val("");
				form.find("input[name='wpsp_password']").focus();
				return; // Bail if the passwords are different
			}
			
			$.post(
				ajax.ajax_url,
				{
					'action': 'wpsp_reset',
					'_wpnonce': nonce,
					'wpsp_password': password,
					'wpsp_old_hash': $.cookie('wpsp_secret')
				},
				"json")
				.done( function( e ) {
					var response = $.parseJSON(e);

					if( response.error ) {
						$("#wpsp-error").text( response.error ).fadeIn();
						form.find("input[name^='wpsp_password']").val("");
						form.find("input[name='wpsp_password']").focus();
						return;
					}

					if( response.success == true ) {
						console.log( response );
						$.cookie('wpsp_secret', response.hash);
						location.reload();
					}

				} )

			// Make sure to unfocus
			submit.blur();
		});

	});

})( jQuery );


function checkPasswordStrength( $pass1,
								$pass2,
								$strengthResult,
								$submitButton,
								blacklistArray ) {

	var minimum_strength = passwords.strength;

	var pass1 = $pass1.val();
	var pass2 = $pass2.val();

	// Reset the form & meter
	$submitButton.attr( 'disabled', 'disabled' );
	$strengthResult.removeClass( 'short bad good strong' );
	$strengthResult.show();

	// Extend our blacklist array with those from the inputs & site data
	blacklistArray = blacklistArray.concat( wp.passwordStrength.userInputBlacklist() )
	// Extend with the items from WPSP
	blacklistArray = blacklistArray.concat( passwords.blacklist );

	// Get the password strength
	var strength = wp.passwordStrength.meter( pass1, blacklistArray, pass2 );

	// Add the strength meter results
	switch ( strength ) {

		case 2:
			$strengthResult.addClass( 'bad' ).html( pwsL10n.bad );
			break;

		case 3:
			$strengthResult.addClass( 'good' ).html( pwsL10n.good );
			break;

		case 4:
			$strengthResult.addClass( 'strong' ).html( pwsL10n.strong );
			break;

		case 5:
			$strengthResult.addClass( 'short' ).html( pwsL10n.mismatch );
			break;

		default:
			$strengthResult.addClass( 'short' ).html( pwsL10n.short );

	}
	

	// The meter function returns a result even if pass2 is empty,
	// enable only the submit button if the password is strong and
	// both passwords are filled up
	if ( strength >= minimum_strength && '' !== pass2.trim() ) {
		$submitButton.removeAttr( 'disabled' );
	}

	return strength;
}