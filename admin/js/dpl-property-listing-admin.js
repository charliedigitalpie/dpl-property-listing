jQuery(document).ready(function($){

	/**
	 * Collect the selected
	 * property IDs and send to
	 * for API call
	 */
	$(".sync-to-db-btn").click(function(){
		var selected_listings = [];
		$(".dpl-property-checkbox:checked").each(function(){
			selected_listings.push($(this).val());
	    });
	    var updated_selected_listings = selected_listings.join(",");		
		$('.dpl-listing-ids').val(updated_selected_listings);

		$(".submit_listing_ids_form").submit();

		$(".dpl-custom-property-loading-sync").show();

	});
	
});
