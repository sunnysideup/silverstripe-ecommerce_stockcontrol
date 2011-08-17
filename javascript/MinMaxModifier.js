


var MinMaxModifier = {

	show_message: false,

	add_item: function (fieldSelector, min, max, msg) {
		jQuery(fieldSelector).blur(
			function() {
				MinMaxModifier.update_field(fieldSelector, min, max, msg);
			}
		);
		jQuery(fieldSelector).blur();
		//MinMaxModifier.show_message = true;
	},

	update_field: function(fieldSelector, min, max, msg) {
		var updated = false;
		if(max > 0) {
			if(jQuery(fieldSelector).val() > max) {
				jQuery(fieldSelector).val(max);
				updated = true;
			}
		}
		if(min > 0) {
			if(jQuery(fieldSelector).val() < min) {
				jQuery(fieldSelector).val(min);
				updated = true;
			}
		}
		if(updated) {
			if(MinMaxModifier.show_message) {
				alert(msg);
			}
			jQuery(fieldSelector).change();
		}
	}
	
}
