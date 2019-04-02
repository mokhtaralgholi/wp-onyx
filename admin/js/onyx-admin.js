(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	 $(document).ready(function(){
		  var syncselected = $('#onyx_sync_every').val();
			//alert(syncselected);
			if(syncselected == 168){
				$('.onyx_sync_weekdays').show();
				$('.onyx_sync_monthdays').hide();
			}else if (syncselected == 720) {
				$('.onyx_sync_weekdays').hide();
				$('.onyx_sync_monthdays').show();
			}else if (syncselected==1 || syncselected==0) {
				 $('.onyx_sync_hours_min').hide();
				 $('.onyx_sync_weekdays').hide();
				 $('.onyx_sync_monthdays').hide();
			}else if (syncselected==24) {
				 $('.onyx_sync_weekdays').hide();
				 $('.onyx_sync_monthdays').hide();
			}

			 $('#onyx_sync_every').change(function(){
				 $('.onyx_sync_hours_min').show();

			 	 var sync = $(this).val();
				 if(sync == 168){
					 $('.onyx_sync_weekdays').show();
					 $('.onyx_sync_monthdays').hide();
				 }else if (sync == 720) {
					 $('.onyx_sync_weekdays').hide();
 					 $('.onyx_sync_monthdays').show();
				 }else if (sync==1 || sync==0) {
				 	  $('.onyx_sync_hours_min').hide();
						$('.onyx_sync_weekdays').hide();
						$('.onyx_sync_monthdays').hide();
				 }else if (sync==24) {
						$('.onyx_sync_weekdays').hide();
						$('.onyx_sync_monthdays').hide();
				 }
			 })
	 })

})( jQuery );
