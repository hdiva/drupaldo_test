(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.todoBehavior = {
    attach: function (context, settings) {
      jQuery('input:checkbox').change(function(){
    	if(jQuery(this).is(":checked")) {
	        jQuery(this).parent().addClass('selected'); 
    	}
		else {
			jQuery(this).parent().removeClass('selected');
		}
	  });
    }
  };
})(jQuery, Drupal, drupalSettings);
