jQuery(document).ready(function() {	
	function stockalert_all_value(){
		var values = [];
		var email = jQuery("#sa-new-email").val();
		jQuery('.sa-old-input-wrap').each(function(){
			var oldemail = jQuery(this).find("#sa-email").val();
			var oldprice = jQuery(this).find("#sa-price").val();
			values.push({
				"email" : oldemail,
			});
		});
		values.push({
			"email" : email,
		});
	    jQuery('#sa-all-data').val(JSON.stringify(values));
	}

	jQuery('#sa-submit').on('click', function(){
		var email = jQuery("#sa-new-email").val();
		if(email == ''){
			alert('Please Enter Email');
		}else{
			stockalert_all_value();
			var product_id = jQuery('#sa-new-product-id').val();
			var alldata = jQuery('#sa-all-data').val();
			var data = {
				'action' : 'new_email',
				'email' : email,
				'productid' : product_id,
			    'alldata' : alldata
			};
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('.responce').remove();
				var obj = jQuery.parseJSON( response );
				jQuery('.sa-old-all-data .sa-old-input-wrap').remove();
				jQuery('#sa-all-data').remove();
				jQuery('#sa-new-email').val('');
				jQuery('.sa-old-all-data').append(obj.all_data);
				jQuery('.sa-email-input').append(obj.json_data);
				jQuery('.sa-email-input').append('<span class="responce">'+obj.resultmsg+'</span>');
				jQuery('.responce').fadeIn('slow', function () {
				    jQuery(this).delay(5000).fadeOut('slow');
				});
			});
		}    
	});

	

});
