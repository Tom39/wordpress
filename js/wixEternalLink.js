jQuery(function($) {

	if ( !$('.decidemenu')[0] ) {

		$('a[class="wix-authorLink"]').not('[href*="'+location.hostname+'"]')
										.after('<i class="external-link"></i>');

	} else {
		//Decideのポップアップの場合は外部リンクを明示しない
	}
});