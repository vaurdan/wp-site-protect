(function($) {

	$(document).ready(function() {

		// Get the form
		var form = $('form[name="wpsp_authenticate"]');
		var submit = form.find("input[type='submit']");

		form.submit( function(e) {
			e.preventDefault();

			$("#wpsp-error").fadeOut();

			var nonce = form.find("input[name='_wpnonce']").val();
			var password = form.find("input[name='wpsp_password']").val();
			
			$.post(
				ajax.ajax_url,
				{
					'action': 'wpsp_authorize',
					'_wpnonce': nonce,
					'wpsp_password': password,
				},
				"json")
			.done( function( e ) {
				var response = $.parseJSON(e);

				if( response.error ) {
					$("#wpsp-error").text( response.error ).fadeIn();
					form.find("input[name='wpsp_password']").val("");
					form.find("input[name='wpsp_password']").focus();
					return;
				}

				if( response.success == true ) {
					console.log( response.hash );
					$.cookie('wpsp_secret', response.hash);
					location.reload();
				}

			} )

			// Make sure to unfocus
			submit.blur();
		});

	});

})( jQuery );
