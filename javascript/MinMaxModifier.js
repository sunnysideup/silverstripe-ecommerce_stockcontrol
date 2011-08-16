


var MinMaxModifier = {

	show_message: false,

	add_item: function (fieldSelector, min, max, msg) {
		jQuery(fieldSelector).blur(
			function() {
				var updated = false;
				if(max > 0) {
					if(jQuery(this).val() > max) {
						jQuery(this).val(max);
						updated = true;
					}
				}
				if(min > 0) {
					if(jQuery(this).val() < min) {
						jQuery(this).val(min);
						updated = true;
					}
				}
				if(updated) {
					if(MinMaxModifier.show_message) {
						alert(msg);
					}
					jQuery(this).change();
				}
			}
		);
		jQuery(fieldSelector).blur();
		MinMaxModifier.show_message = true;
	}
}
