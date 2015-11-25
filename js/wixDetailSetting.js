jQuery(function($) {

	//タブ機能
	$('.tabbox:first').show();
	$('#tab li:first').addClass('active');
	$('#tab li').click(function() {
		$('#tab li').removeClass('active');
		$(this).addClass('active');
		$('.tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();
		return false;
	});

	$('.doc_tabbox:first').show();
	$('#doc_tab li:first').addClass('active');
	$('#doc_tab li').click(function() {
		$('#doc_tab li').removeClass('active');
		$(this).addClass('active');
		$('.doc_tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();
		return false;
	});

	$('.decide_entrys_tabbox:first').show();
	$('#decide_entrys_tab li:first').addClass('active');
	$('#decide_entrys_tab li').click(function() {
		$('#decide_entrys_tab li').removeClass('active');
		$(this).addClass('active');
		$('.decide_entrys_tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();
		return false;
	});	

	var decideLinkArray = new Array();
	$('.doc_page').on('click', function(event) {
		event.preventDefault();
		
		var doc_id = $(this).attr('id');
		var url = $(this).attr('href');
		var data = {
			'action': 'wix_setting_decideBody',
			'doc_id' : doc_id,
			'url' : url,
		};

		$.ajax({
			async: true,
			dataType: "json",
			type: "POST",
			url: ajaxurl,
			data: data,

			success: function(json) {
// console.log(json['html']);
// console.log(json['test']);
// console.log( json['test2'] );

				//iframeへの挿入
				$('#doc_iframe')[0].contentDocument.location.replace(url);

				$('#doc_iframe').on('load', function(event) {
					event.preventDefault();
					
					var subject_obj = $('#doc_iframe').contents().find('.entry-content').eq(0);
					subject_obj.children().remove();
					subject_obj.append(json['html']);

					var decideBody = $.trim( $('#doc_iframe').contents().find('.entry-content').text() );

					//ポップアップの処理
					$('#doc_iframe').contents().find('.wix-authorLink').mouseover(function() {
						popupMenu($(this));

						//ポップアップのクリックイベント
						$('#doc_iframe').contents().find('.wix-pre-authorLink').off();
						$('#doc_iframe').contents().find('.wix-pre-authorLink').click(function(){

							var start = $(this).parents('span').prev().attr('start');
							var keyword, target, end;

							if ( (start in decideLinkArray) == true ){
								delete decideLinkArray[start];
							}
							keyword = $(this).parents('span').prev().html();
							target = $(this).html();
							end = parseInt(start) + keyword.length;

							decideLinkArray[start] = {'keyword':keyword,'target':target,'end':end};

							//クリックされたキーワードの色変更
							$(this).parents('span')
									.prev()
									.css('background', '#ccccff');
						});
					});

					$('#doc_iframe').contents().find('.decidemenu').on({
						'mouseover': function(event) {
							displayMode('block', $(this));
						}, 
						'mouseout': function(event) {
							displayMode('none', $(this));
						}, 
					});


					var data2 = {
						'action': 'wix_existing_decidefile_presentation',
						'doc_id' : doc_id,
					};
					$.ajax({
						async: true,
						dataType: "json",
						type: "POST",
						url: ajaxurl,
						data: data2,

						success: function(json) {
// console.log(json['latest_decideinfo']);
							$('#existing_latest_decidefile').empty();

							var table = $("<TABLE />",{
								id: 'exisitng_latest_decidefile_table'
							}).css({
								'width': '100%',
							});

							if ( json['latest_decideinfo'].length != 0 ) {
								var th = $("<TH />", {
									class: 'exisitng_latest_decidefile_th',
									text: 'Keyword'
								}).css({
									'width': '20%',
								}).appendTo(table);
								th = $("<TH />", {
									class: 'exisitng_latest_decidefile_th',
									text: 'リンク先URL情報'
								}).css({
									'width': '50%',
								}).appendTo(table);
								th = $("<TH />", {
									class: 'exisitng_latest_decidefile_th',
									text: '周辺単語'
								}).css({
									'width': '30%',
								}).appendTo(table);

								var index = 0;
								$.each(json['latest_decideinfo'], function(start, elm) {
									var end = parseInt(elm['end']);

									var tr = $("<TR />", {
										id: 'exisitng_latest_decidefile_tr' + index,
										class: 'exisitng_latest_decidefile_tr',
										start: start,
										name: 'end=' + elm['end'] + ',nextStart=' + elm['nextStart']
									}).css({
										'width': '100%',
									}).appendTo(table);

									var td = $("<TD />", {
										id: 'exisitng_latest_decidefile_keyword_td' + index,
										class: 'exisitng_latest_decidefile_td',
										text: elm['keyword'],
									}).css({
										'width': '20%',
									}).appendTo(tr);
									td = $("<TD />", {
										id: 'exisitng_latest_decidefile_target_td' + index,
										class: 'exisitng_latest_decidefile_td',
									}).css({
										'width': '50%',
									}).appendTo(tr);
									var a = $("<A />", {
										id: 'exisitng_latest_decidefile_a' + index,
										class: 'exisitng_latest_decidefile_a',
										href: elm['target'],
										text: elm['title'],
									}).css({
										'width': '100%',
									}).appendTo(td);


									var surword = '';
									if ( start == 0 ) {
										if ( end+10 <= decideBody.length ) 
											surword = decideBody.substr(end, 10);
										else
											surword = decideBody.substr(end, decideBody.length-end);
									} else if ( start < 4 ) {
										if ( end+10 <= decideBody.length ) 
											surword = decideBody.substr(0, 10);
										else
											surword = decideBody.substr(0, decideBody.length-end);
									} else {
										if ( end+10 <= decideBody.length ) 
											surword = decideBody.substr(start-3, 10);
										else
											surword = decideBody.substr(start-3, decideBody.length-end);
									}

									td = $("<TD />", {
										id: 'exisitng_latest_decidefile_surword_td' + index,
										class: 'exisitng_latest_decidefile_td',
										text: surword
									}).css({
										'width': '30%',
									}).appendTo(tr);

									index++;
								});


								$('.exisitng_latest_decidefile_a').on('click', function(event) {
									event.preventDefault();
									var url = $(this).attr('href');
									$('#doc_iframe')[0].contentDocument.location.replace(url);
								});


							} else {
								var th = $("<TH />", {
									class: 'exisitng_latest_decidefile_th',
									text: '詳細設定は過去に行われていません。'
								}).css({
									'width': '20%',
								}).appendTo(table);
							}


							$('#existing_latest_decidefile').append(table);
							
						},

						error: function(xhr, textStatus, errorThrown){
							alert('wixSetting.js Error');
						}
					});

				});

			},

			error: function(xhr, textStatus, errorThrown){
				alert('wixSetting.js Error');
			}
		});

		var data2 = {
			'action': 'wix_disambiguation_recommend',
			'doc_id' : doc_id,
		};

		$.ajax({
			async: true,
			dataType: "json",
			type: "POST",
			url: ajaxurl,
			data: data2,

			success: function(json) {
console.log(json['entrys']);


			},

			error: function(xhr, textStatus, errorThrown){
				alert('wixSetting.js Error');
			}
		});

		
		

	});


});