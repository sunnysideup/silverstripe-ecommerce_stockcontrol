
jQuery(document).ready(
    function()
    {
        if(typeof MinMaxModifierData !== 'undefined') {
            for(var i = 0; i < MinMaxModifierData.length; i++) {
                MinMaxModifier.add_item(
                    MinMaxModifierData[i]
                );
            }
        }
    }
)

var MinMaxModifier = {

    show_message: true,

    /**
     * object requires:
     * - selector
     * - min
     * - max
     * - msg
     * @param {[type]} object [description]
     */
    add_item: function (object) {
        jQuery(object.selector).blur(
            function() {
                MinMaxModifier.update_field(object);
            }
        );
        jQuery(object.selector).blur();
        //MinMaxModifier.show_message = true;
    },

    update_field: function(object) {
        var updated = false;
        if(object.max > 0) {
            if(jQuery(object.selector).val() > object.max) {
                jQuery(object.selector).val(object.max);
                updated = true;
            }
        }
        if(object.min > 0) {
            if(jQuery(object.selector).val() < object.min) {
                jQuery(object.selector).val(object.min);
                updated = true;
            }
        }
        if(updated) {
            if(this.show_message) {
                alert(object.msg);
            }
            jQuery(object.selector).change();
        }
    }
}
