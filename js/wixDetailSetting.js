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

	$('.decidefileDoc_tabbox:first').show();
	$('#decidefileDoc_tab li:first').addClass('active');
	$('#decidefileDoc_tab li').click(function() {
		$('#decidefileDoc_tab li').removeClass('active');
		$(this).addClass('active');
		$('.decidefileDoc_tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();
		return false;
	});

/***********************************************************************************************************/

			//Tab 1
	
	/***********************************************************************************************************/
	var decideLinkArray = new Object();
	var defaultLinkArray = new Object();
	var oldBody = '';
	var newBody = '';
	var innerLinkArray = '';
	var doc_id = '';
	var doc_title = '';

	$('.doc_page').on('click', function(event) {
		event.preventDefault();
		
		doc_id = $(this).attr('id');
		doc_title = $(this).text();
		var url = $(this).attr('href');

		//クリックされたドキュメントの色を変更
		$.each($(this).parent().parent().siblings('tr'), function(index, el) {
			$(this).children().css('background-color', '');
		});
		$(this).parent().css('background-color', 'Yellow');
		if ( doc_id in defaultLinkArray || doc_id in decideLinkArray ) {
			$(this).css('color', 'Red');
		}

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
console.log(json['innerLinkArray']);
				newBody = json['html'];
				innerLinkArray = json['innerLinkArray'];

				//iframeへの挿入
				$('#doc_iframe')[0].contentDocument.location.replace(url);

				$('#doc_iframe').off();				
				$('#doc_iframe').on('load', function(event) {
					event.preventDefault();

					//Decide処理可能Bodyに変更
					var subject_obj = $('#doc_iframe').contents().find('.entry-content').eq(0);
					subject_obj.children().remove();
					subject_obj.append(newBody);

					oldBody = $.trim( $('#doc_iframe').contents().find('.entry-content').text() );

					//ポップアップの処理
					$('#doc_iframe').contents().find('.wix-authorLink').mouseover(function() {
						popupMenu($(this));

						//ポップアップのクリックイベント
						$('#doc_iframe').contents().find('.wix-pre-authorLink').off();
						$('#doc_iframe').contents().find('.wix-pre-authorLink').click(function(){

							var start = $(this).parents('span').prev().attr('start');
							var keyword, target, end;
							keyword = $(this).parents('span').prev().html();
							target = $(this).html();
							end = parseInt(start) + keyword.length;

							if ( doc_id in decideLinkArray ) {
								var tmpArray = decideLinkArray[doc_id];
								if ( start in tmpArray ){
									delete tmpArray[start];
								}
								tmpArray[start] = {'keyword':keyword,'target':target,'end':end};
								decideLinkArray[doc_id] = tmpArray;
							} else {
								var tmpArray = new Object();
								tmpArray[start] = {'keyword':keyword,'target':target,'end':end};
								decideLinkArray[doc_id] = tmpArray;
							}

							console.log(decideLinkArray);

							//クリックされたキーワードの色変更
							$(this).parents('span')
									.prev()
									.css('background-color', 'Red');

							$.each($('.decide_entrys_tab2_table_keyword_span'), function(index, elm) {
								var tmp_start = $(this).attr('start_end').split(':')[0];

								if ( tmp_start == start ) {
									$(this).css('background-color', 'Red');

									var subject_el = $(this)
															.parent()
															.next()
															.find('.decide_entrys_tab2_table_targets_tr');
									$.each(subject_el, function(index, el) {
										var e = $(this).find('.decide_entrys_tab2_table_target_a');
										var title;

										if ( typeof e.attr('href') === 'undefined' ) {
											if ( e.attr('no_attach') == target ) {
												e.parent()
													.next()
													.children()
													.prop('checked', true); 
												title = e.text();
												decideLinkArray[doc_id][start]['title'] = title;
												decideLinkArray[doc_id][start]['doc_title'] = doc_title;
											}

										} else {
											if ( e.attr('href') == target ) {
												e.parent()
													.next()
													.children()
													.prop('checked', true); 
												title = e.text();
												decideLinkArray[doc_id][start]['title'] = title;
												decideLinkArray[doc_id][start]['doc_title'] = doc_title;
											}
										}
										
									});

								}
							});

							//docの文字色を赤くする
							$.each($('.doc_page'), function(index, el) {
								if ( $(el).attr('id') == doc_id )
									$(el).css('color', 'Red'); 
							});

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

					//既存Decide情報の表示
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

							var top_table = $("<TABLE />",{
								id: 'exisitng_latest_decidefile_top_table',
							});
							var top_thead = $("<THEAD />", {
								id: 'exisitng_latest_decidefile_top_table_thead',
							}).appendTo(top_table);
							$("<TH />", {
								text: '最新設定情報'
							}).css({'white-space': 'nowrap'})
							.appendTo(top_thead);
							var top_tbody = $("<TBODY />", {
								id: 'exisitng_latest_decidefile_top_table_tbody',
							}).appendTo(top_table);
							var top_tr = $("<TR />", {}).appendTo(top_tbody);
							var top_td = $("<TD />", {}).appendTo(top_tr);
							var table = $("<TABLE />",{
								id: 'exisitng_latest_decidefile_table'
							}).appendTo(top_td);

							if ( json['latest_decideinfo'].length != 0 ) {
								var th = $("<TH />", {
									class: 'exisitng_latest_decidefile_th',
									text: '単語'
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
									if ( elm['target'] == 'no_attach' ) {
										var a = $("<A />", {
											id: 'exisitng_latest_decidefile_a' + index,
											class: 'exisitng_latest_decidefile_a',
											text: 'リンク生成しない',
										}).css({
											'width': '100%',
										}).appendTo(td);
									} else {
										var a = $("<A />", {
											id: 'exisitng_latest_decidefile_a' + index,
											class: 'exisitng_latest_decidefile_a',
											href: elm['target'],
											target: 'blank',
											text: elm['title'],
										}).css({
											'width': '100%',
										}).appendTo(td);

									}


									var surword = '';
									if ( start == 0 ) {
										if ( end+5 <= oldBody.length ) 
											surword = oldBody.substr(end, 10);
										else
											surword = oldBody.substr(end, oldBody.length-end);
									} else if ( start < 4 ) {
										if ( end+5 <= oldBody.length ) {
											surword = oldBody.substr(0, start);
											surword = surword + ', ' + oldBody.substr(end+1, 5);
										} else {
											surword = oldBody.substr(0, start);
											surword = surword + ', ' + oldBody.substr(end+1, oldBody.length-end);
										}
									} else {
										if ( end+5 <= oldBody.length ) {
											surword = oldBody.substr(start-3, 3);
											surword = surword + ', ' + oldBody.substr(end+1, 5);
										} else {
											surword = oldBody.substr(start-3, 3);
											surword = surword + ', ' + oldBody.substr(end+1, oldBody.length-end);
										}
									}
									// if ( start == 0 ) {
									// 	if ( end+10 <= oldBody.length ) 
									// 		surword = oldBody.substr(end, 10);
									// 	else
									// 		surword = oldBody.substr(end, oldBody.length-end);
									// } else if ( start < 4 ) {
									// 	if ( end+10 <= oldBody.length ) 
									// 		surword = oldBody.substr(0, 10);
									// 	else
									// 		surword = oldBody.substr(0, oldBody.length-end);
									// } else {
									// 	if ( end+10 <= oldBody.length ) 
									// 		surword = oldBody.substr(start-3, 10);
									// 	else
									// 		surword = oldBody.substr(start-3, oldBody.length-end);
									// }

									td = $("<TD />", {
										id: 'exisitng_latest_decidefile_surword_td' + index,
										class: 'exisitng_latest_decidefile_td',
										text: surword
									}).css({
										'width': '30%',
									}).appendTo(tr);

									index++;
								});

							} else {
								var th = $("<TH />", {
									class: 'exisitng_latest_decidefile_th',
									text: '詳細設定は過去に行われていません。'
								}).css({
									'width': '20%',
								}).appendTo(table);
							}

							$('#existing_latest_decidefile').append(top_table);
						},

						error: function(xhr, textStatus, errorThrown){
							alert('wixSetting.js Error');
						}
					});
					
					//既にdoc_idのdefaultLinkArrayやdecideLinkArrayがあるなら色づけ
					if ( doc_id in defaultLinkArray ) {
						$.each(defaultLinkArray[doc_id], function(keyword_id, elm) {
							$.each($('.decide_entrys_tab1_table_keyword_span'), function(index, el) {
								if ( $(el).attr('keyword_id') == keyword_id ) {

									var subject_el = $(this)
														.parent()
														.next()
														.find('.decide_entrys_tab1_table_targets_tr');
									
									$.each(subject_el, function(i, e) {
										if ( $(e).find('.decide_entrys_tab1_table_target_a').text() == elm['title'] ) {
											$(this)
												.find('.decide_entrys_tab1_table_target_radio_input')
												.prop('checked', true);

												return false;
										}
										
									});

								}
							});	
						});
					}

					if ( doc_id in decideLinkArray ) {
						$.each(decideLinkArray[doc_id], function(start, elm) {

							$.each($('#doc_iframe').contents().find('.wix-authorLink'), function(index, el) {
								if ( start == $(this).attr('start') ) {
									$(this).css('background-color', 'Red');
								}
							});
							$.each($('.decide_entrys_tab2_table_keyword_span'), function(index, el) {
								var tmp_start = $(el).attr('start_end').split(':')[0];
								if ( start == tmp_start ) {
									$(this).css('background-color', 'Red');

									var subject_el = $(this)
														.parent()
														.next()
														.find('.decide_entrys_tab2_table_targets_tr');
									$.each(subject_el, function(index, el) {
										var e = $(this).find('.decide_entrys_tab2_table_target_a');

										if ( typeof e.attr('href') === 'undefined' ) {
											if ( e.attr('no_attach') == elm['target'] ) {
												e.parent()
													.next()
													.children()
													.prop('checked', true);
											}

										} else {
											if ( e.attr('href') == elm['target'] ) {
												e.parent()
													.next()
													.children()
													.prop('checked', true);
											}

										}
									});
								}
							});
							// $.each($('.decide_entrys_tab2_table_target_a'), function(index, el) {
							// 	if ( typeof $(this).attr('href') === 'undefined' ) {
							// 		if ( $(this).attr('no_attach') == elm['target'] ) {
							// 			$(this)
							// 				.parent()
							// 				.next()
							// 				.children()
							// 				.prop('checked', true);
							// 		}

							// 	} else {
							// 		if ( $(this).attr('href') == elm['target'] ) {
							// 			$(this)
							// 				.parent()
							// 				.next()
							// 				.children()
							// 				.prop('checked', true);
							// 		}

							// 	}
							// });
						});
					}
				});
			},

			error: function(xhr, textStatus, errorThrown){
				alert('wixSetting.js Error');
			}
		}).done(function(e) {
// console.log(e);

			$('#decide_entrys_tab1_table').children('tbody').remove();
			$('#decide_entrys_tab2_table').children('tbody').remove();
			$('#decide_entrys_tab1').children('button').remove();
			$('#decide_entrys_tab2').children('button').remove();

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
// console.log(json['entrys']);
					if ( json['entrys'].length != 0 ) {
						if ( json['no_selection_option'].length != 0 ) {
							if ( json['no_selection_option'] == 'no_selection_morphological_analysis' ) {
								alert('設定画面における【形態素解析】項目を入力してください');
							
							} else if ( json['no_selection_option'] == 'no_selection_recommend_support' ) {
								alert('設定画面における【自動生成・推薦支援】項目を入力することを勧めます');

							} else {
								alert('設定画面における【形態素解析】と【自動生成・推薦支援】の2項目を入力してください');

							}
						}

						//Default設定
						var tbody = $("<TBODY />",{});

						var count = 0;
						$.each(json['entrys'], function(keyword, elm) {
							var tr = $("<TR />", {
								id: 'decide_entrys_tab1_table_tr'+count,
								class: 'decide_entrys_tab1_table_tr'
							}).appendTo(tbody);
							var td = $("<TD />", {
								id: 'decide_entrys_tab1_table_keyword_td' + count,
								class: 'decide_entrys_tab1_table_keyword_td',
								keyword_id: elm['keyword_id'],
							}).appendTo(tr);
							var span = $("<SPAN />", {
								id: 'decide_entrys_tab1_table_keyword_span' + count,
								class: 'decide_entrys_tab1_table_keyword_span',
								keyword_id: elm['keyword_id'],
								text: keyword
							}).appendTo(td);
							var td2 = $("<TD />", {
								id: 'decide_entrys_tab1_table_targets_td' + count,
								class: 'decide_entrys_tab1_table_targets_td',
								keyword_id: elm['keyword_id'],
							}).appendTo(tr);

							$.each(elm, function(key, el) {
								if ( key == 'targets' ) {
									var table2 = $("<TABLE />",{
										id: 'decide_entrys_tab1_table_targets_table'
									}).appendTo(td2);

									var no_attach_index = 0;
									$.each(el, function(index, e) {
										tr = $("<TR />", {
											id: 'decide_entrys_tab1_table_targets_tr' + index,
											class: 'decide_entrys_tab1_table_targets_tr'
										}).appendTo(table2);

										td = $("<TD />", {
											id: 'decide_entrys_tab1_table_target_td' + index,
											class: 'decide_entrys_tab1_table_target_td',
										}).appendTo(tr);

										if ( 'doc_id' in e ) {
											var a = $("<A />", {
												id: 'decide_entrys_tab1_table_target_a' + index,
												class: 'decide_entrys_tab1_table_target_a',
												href: e['url'],
												text: e['title'],
												doc_id: e['doc_id'],
											}).appendTo(td);
										} else {
											var a = $("<A />", {
												id: 'decide_entrys_tab1_table_target_a' + index,
												class: 'decide_entrys_tab1_table_target_a',
												href: e['url'],
												target: 'blank',
												text: e['url'],
											}).appendTo(td);
										}

										td = $("<TD />", {
											id: 'decide_entrys_tab1_table_target_radio_td' + index,
											class: 'decide_entrys_tab1_table_target_radio_td',
										}).appendTo(tr);
										var input = $("<input />", {
											type: 'radio',
											id: 'decide_entrys_tab1_table_target_radio_input' + index,
											class: 'decide_entrys_tab1_table_target_radio_input',
											name: 'default_decide_entry[' + count + ']',
											value: count+':'+index,
										}).appendTo(td);
										
										no_attach_index = index;
									});

									//no_attach用
									tr = $("<TR />", {
										id: 'decide_entrys_tab1_table_targets_tr' + no_attach_index,
										class: 'decide_entrys_tab1_table_targets_tr'
									}).appendTo(table2);

									td = $("<TD />", {
										id: 'decide_entrys_tab1_table_target_td' + no_attach_index,
										class: 'decide_entrys_tab1_table_target_td',
									}).appendTo(tr);
									var a = $("<A />", {
										id: 'decide_entrys_tab1_table_target_a' + no_attach_index,
										class: 'decide_entrys_tab1_table_target_a',
										text: 'この単語にリンク生成しない',
										no_attach: 'no_attach'
									}).appendTo(td);

									td = $("<TD />", {
										id: 'decide_entrys_tab1_table_target_radio_td' + no_attach_index,
										class: 'decide_entrys_tab1_table_target_radio_td',
									}).appendTo(tr);
									var input = $("<input />", {
										type: 'radio',
										id: 'decide_entrys_tab1_table_target_radio_input' + no_attach_index,
										class: 'decide_entrys_tab1_table_target_radio_input',
										name: 'default_decide_entry[' + count + ']',
										value: count + ':' + no_attach_index,
									}).appendTo(td);


								}

							});

							count++;
							
						});
						$('#decide_entrys_tab1_table').append(tbody);


						//詳細設定							
						var tbody = $("<TBODY />",{});
						var count = 0;
						$.each(innerLinkArray, function(start, element) {
							var tr = $("<TR />", {
								id: 'decide_entrys_tab2_table_tr'+count,
								class: 'decide_entrys_tab2_table_tr'
							}).appendTo(tbody);
							var td = $("<TD />", {
								id: 'decide_entrys_tab2_table_keyword_td' + count,
								class: 'decide_entrys_tab2_table_keyword_td',
								keyword_id: json['entrys'][element['keyword']]['keyword_id'],
							}).appendTo(tr);
							var span = $("<SPAN />", {
								id: 'decide_entrys_tab2_table_keyword_span' + count,
								class: 'decide_entrys_tab2_table_keyword_span',
								keyword_id: json['entrys'][element['keyword']]['keyword_id'],
								start_end: start + ':' + element['end'],
								nextStart: element['nextStart'],
								value: element['keyword'],
								html: start + '~' + element['end'] + '文字目の<br>' + '<strong>' + element['keyword'] + '</strong>'
							}).appendTo(td);
							var td2 = $("<TD />", {
								id: 'decide_entrys_tab2_table_targets_td' + count,
								class: 'decide_entrys_tab2_table_targets_td',
								keyword_id: json['entrys'][element['keyword']]['keyword_id'],
							}).appendTo(tr);

							$.each(json['entrys'][element['keyword']], function(k, el) {
								if ( k == 'targets' ) {
									var table2 = $("<TABLE />",{
										id: 'decide_entrys_tab2_table_targets_table'
									}).appendTo(td2);

									var no_attach_index = 0;
									$.each(el, function(index, e) {
										tr = $("<TR />", {
											id: 'decide_entrys_tab2_table_targets_tr' + index,
											class: 'decide_entrys_tab2_table_targets_tr'
										}).appendTo(table2);

										td = $("<TD />", {
											id: 'decide_entrys_tab2_table_target_td' + index,
											class: 'decide_entrys_tab2_table_target_td',
										}).appendTo(tr);

										if ( 'doc_id' in e ) {
											var a = $("<A />", {
												id: 'decide_entrys_tab2_table_target_a' + index,
												class: 'decide_entrys_tab2_table_target_a',
												href: e['url'],
												text: e['title'],
												doc_id: e['doc_id'],
											}).appendTo(td);
										} else {
											var a = $("<A />", {
												id: 'decide_entrys_tab2_table_target_a' + index,
												class: 'decide_entrys_tab2_table_target_a',
												href: e['url'],
												target: 'blank',
												text: e['url'],
											}).appendTo(td);
										}

										td = $("<TD />", {
											id: 'decide_entrys_tab2_table_target_radio_td' + index,
											class: 'decide_entrys_tab2_table_target_radio_td',
										}).appendTo(tr);
										var input = $("<input />", {
											type: 'radio',
											id: 'decide_entrys_tab2_table_target_radio_input' + index,
											class: 'decide_entrys_tab2_table_target_radio_input',
											name: 'detail_decide_entry[' + count + ']',
											value: count+':'+index,
										}).appendTo(td);
										
										no_attach_index = index;
									});

									//no_attach用
									tr = $("<TR />", {
										id: 'decide_entrys_tab2_table_targets_tr' + no_attach_index,
										class: 'decide_entrys_tab2_table_targets_tr'
									}).appendTo(table2);

									td = $("<TD />", {
										id: 'decide_entrys_tab2_table_target_td' + no_attach_index,
										class: 'decide_entrys_tab2_table_target_td',
									}).appendTo(tr);
									var a = $("<A />", {
										id: 'decide_entrys_tab2_table_target_a' + no_attach_index,
										class: 'decide_entrys_tab2_table_target_a',
										text: 'この単語にリンク生成しない',
										no_attach: 'no_attach'
									}).appendTo(td);

									td = $("<TD />", {
										id: 'decide_entrys_tab2_table_target_radio_td' + no_attach_index,
										class: 'decide_entrys_tab2_table_target_radio_td',
									}).appendTo(tr);
									var input = $("<input />", {
										type: 'radio',
										id: 'decide_entrys_tab2_table_target_radio_input' + no_attach_index,
										class: 'decide_entrys_tab2_table_target_radio_input',
										name: 'detail_decide_entry[' + count + ']',
										value: count + ':' + no_attach_index,
									}).appendTo(td);
								}

							});

							count++;

						});
						$('#decide_entrys_tab2_table').append(tbody);

						//設定解除ボタンの作成
						$('<button />', {
							id: 'decide_entrys_tab1_button',
							text: "Default設定の全解除",
							click : function(event) {
								if ( doc_id in defaultLinkArray ) {
									delete defaultLinkArray[doc_id];
									$.each($('.decide_entrys_tab1_table_target_radio_input'), function(index, el) {
										$(this).prop('checked', false);
									});

									$.each($('.decide_entrys_tab1_table_keyword_span'), function(index, el) {
										$(this).css('background-color', '');
									});
									$.each($('#doc_iframe').contents().find('.wix-authorLink'), function(index, el) {
										if ( $(this).css('background-color') !== 'rgb(255, 0, 0)' )
											$(el).css('background-color', '');
									});

									//docの文字色をなくす
									$.each($('.doc_page'), function(index, el) {
										if ( $(el).attr('id') == doc_id )
											$(el).css('color', '#0073aa'); 
									});

								}
							}
						}).appendTo('#decide_entrys_tab1');

						$('<button />', {
							id: 'decide_entrys_tab2_button',
							text: "詳細設定の全解除",
							click : function(event) {
								if ( doc_id in decideLinkArray ) {
									delete decideLinkArray[doc_id];
									$.each($('.decide_entrys_tab2_table_target_radio_input'), function(index, el) {
										$(this).prop('checked', false);
									});

									$.each($('.decide_entrys_tab2_table_keyword_span'), function(index, el) {
										$(this).css('background-color', '');
									});
									$.each($('#doc_iframe').contents().find('.wix-authorLink'), function(index, el) {
										$(el).css('background-color', '');
									});

									//docの文字色をなくす
									$.each($('.doc_page'), function(index, el) {
										if ( $(el).attr('id') == doc_id )
											$(el).css('color', '#0073aa'); 
									});

								}
							}
						}).appendTo('#decide_entrys_tab2');

					}

					//キーワードと、インラインフレーム内の連携
					$('.decide_entrys_tab1_table_keyword_span').on('click', function(event) {
						event.preventDefault();
						
						var keyword = $(this).text();

						$.each($('.decide_entrys_tab1_table_keyword_span'), function(index, el) {
							if ( $(this).css('background-color') !== 'rgb(255, 0, 0)' ) {
								$(el).css('background-color', '');
							}	
						});
						if ( $(this).css('background-color') !== 'rgb(255, 0, 0)' ) {
							$(this).css('background-color', 'Aqua');
						}

						$.each($('#doc_iframe').contents().find('.wix-authorLink'), function(index, el) {
							if ( $(el).css('background-color') !== 'rgb(255, 0, 0)' )
								$(el).css('background-color', '');

							if ( keyword == $(this).text() ) {
								if ( $(this).css('background-color') !== 'rgb(255, 0, 0)' )
									$(this).css('background-color', 'Aqua');
							}
						});
					});
					$('.decide_entrys_tab2_table_keyword_span').on('click', function(event) {
						event.preventDefault();
						
						var keyword = $(this).val()[0];
						var start = $(this).attr('start_end').split(':')[0];

						
						$.each($('.decide_entrys_tab2_table_keyword_span'), function(index, el) {
							if ( $(this).css('background-color') !== 'rgb(255, 0, 0)' ) {
								$(el).css('background-color', '');
							}	
						});
						if ( $(this).css('background-color') !== 'rgb(255, 0, 0)' ) {
							$(this).css('background-color', 'Aqua');
						}
						

						$.each($('#doc_iframe').contents().find('.wix-authorLink'), function(index, el) {
							if ( $(el).css('background-color') !== 'rgb(255, 0, 0)' )
								$(el).css('background-color', '');

							if ( start == $(this).attr('start') ) {
								if ( $(this).css('background-color') !== 'rgb(255, 0, 0)' )
									$(this).css('background-color', 'Aqua');
							}
						});

					});

					//ターゲット選択イベント
					$('.decide_entrys_tab1_table_target_radio_input').on('change', function(event) {
						event.preventDefault();

						var name = $(this).attr('name');
						var keyword = $(this)
											.parents('.decide_entrys_tab1_table_tr')
											.find('.decide_entrys_tab1_table_keyword_span')
											.text();
						var keyword_id = $(this)
											.parents('.decide_entrys_tab1_table_tr')
											.find('.decide_entrys_tab1_table_keyword_span')
											.attr('keyword_id');
						var target = $(this)
											.parent()
											.prev()
											.children('.decide_entrys_tab1_table_target_a')
											.attr('href');

						if ( typeof target === 'undefined'  ) {
							target = $(this)
											.parent()
											.prev()
											.children('.decide_entrys_tab1_table_target_a')
											.attr('no_attach');
						}
						var title = $(this)
										.parent()
										.prev()
										.children('.decide_entrys_tab1_table_target_a')
										.text();
							
						if ( doc_id in defaultLinkArray ) {
							var tmpArray = defaultLinkArray[doc_id];
							if ( keyword_id in tmpArray) 
								delete tmpArray[keyword_id];
							tmpArray[keyword_id] = {'keyword': keyword, 
													'target': target, 
													'name': name, 
													'title': title, 
													'doc_title': doc_title};
							defaultLinkArray[doc_id] = tmpArray;

						} else {
							var tmpArray = new Object();
							tmpArray[keyword_id] = {'keyword': keyword, 
													'target': target, 
													'name': name, 
													'title': title, 
													'doc_title': doc_title};
							defaultLinkArray[doc_id] = tmpArray;

						}

						//docの文字色を赤くする
						$.each($('.doc_page'), function(index, el) {
							if ( $(el).attr('id') == doc_id )
								$(el).css('color', 'Red'); 
						});

console.log(defaultLinkArray);

					});

					$('.decide_entrys_tab2_table_target_radio_input').on('change', function(event) {
						event.preventDefault();

						var name = $(this).attr('name');
						var keyword = $(this)
											.parents('.decide_entrys_tab2_table_tr')
											.find('.decide_entrys_tab2_table_keyword_span')
											.val();

						var target = $(this)
											.parent()
											.prev()
											.children('.decide_entrys_tab2_table_target_a')
											.attr('href');

						if ( typeof target === 'undefined'  ) {
							target = $(this)
											.parent()
											.prev()
											.children('.decide_entrys_tab2_table_target_a')
											.attr('no_attach');
						}

						var start = $(this)
										.parents('.decide_entrys_tab2_table_tr')
										.find('.decide_entrys_tab2_table_keyword_span')
										.attr('start_end')
										.split(':')[0];
						var end = $(this)
										.parents('.decide_entrys_tab2_table_tr')
										.find('.decide_entrys_tab2_table_keyword_span')
										.attr('start_end')
										.split(':')[1];
						var title = $(this)
											.parent()
											.prev()
											.children('.decide_entrys_tab2_table_target_a')
											.text();


						if ( doc_id in decideLinkArray ) {
							var tmpArray = decideLinkArray[doc_id];
							if ( start in tmpArray )
								delete tmpArray[start];

							tmpArray[start] = {'keyword':keyword[0],'target':target,'end':end, 'doc_title': doc_title, 'title': title};
							decideLinkArray[doc_id] = tmpArray;
						} else {
							var tmpArray = new Object();
							tmpArray[start] = {'keyword':keyword[0],'target':target,'end':end, 'doc_title': doc_title, 'title': title};
							decideLinkArray[doc_id] = tmpArray;
						}

console.log(decideLinkArray);

						//テーブル側の該当キーワードの色付け
						$(this)
							.parents('.decide_entrys_tab2_table_tr')
							.find('.decide_entrys_tab2_table_keyword_span')
							.css('background-color', 'Red');

						//インラインフレーム側の該当キーワードの色付け
						$.each($('#doc_iframe').contents().find('.wix-authorLink'), function(index, el) {
							if ( start == $(this).attr('start') ) {
								$(this).css('background-color', 'Red');
								return false;
							}
						});
						//docの文字色を赤くする
						$.each($('.doc_page'), function(index, el) {
							if ( $(el).attr('id') == doc_id )
								$(el).css('color', 'Red'); 
						});

					});

				},

				error: function(xhr, textStatus, errorThrown){
					alert('wixSetting.js Error');
				}
			});
		});	
	});

	$('#add_decidefile').on('click', function(event) {
		event.preventDefault();
		if ( Object.keys(defaultLinkArray).length > 0 || Object.keys(decideLinkArray).length > 0 ) {
			//モーダル要素作成
			var content = $("<div />", {
				id: 'add_decidefile_div'
			});
			var table = $("<TABLE />",{
				class: 'add_decidefile_table'
			}).appendTo(content);
			var thead = $("<THEAD />", {
				class: 'add_decidefile_thead'
			}).appendTo(table);
			var thead_tr = $("<TR />", {
				class: 'add_decidefile_thead_tr'
			}).appendTo(table);
			$("<TH />", {
				class: 'add_decidefile_thead_docTh',
				text: 'ドキュメント'
			}).css({'white-space': 'nowrap'}).appendTo(thead_tr);
			$("<TH />", {
				class: 'add_decidefile_thead_keywordTh',
				text: '単語'
			}).css({'white-space': 'nowrap'}).appendTo(thead_tr);
			$("<TH />", {
				class: 'add_decidefile_thead_targetTh',
				text: 'リンク先URL'
			}).css({'white-space': 'nowrap'}).appendTo(thead_tr);
			var tbody = $("<TBODY />", {
				class: 'add_decidefile_tbody'
			}).appendTo(table);
			var tr = $("<TR />", {
				class: 'add_decidefile_tr'
			}).appendTo(tbody);
			var td = $("<TD />", {
				class: 'add_decidefile_td',
				colspan: '3'
			}).appendTo(tr);
			var inner_table = $("<TABLE />",{
				class: 'add_decidefile_inner_table'
			}).appendTo(td);
			var inner_tbody = $("<TBODY />", {
				class: 'add_decidefile_inner_tbody'
			}).appendTo(inner_table);
			
			if ( Object.keys(defaultLinkArray).length > 0 ) {
				var doc_td;
				var keyword_td;
				var target_td;
				//keyword
				var keyword_table;
				var keyword_table_tr;
				var keyword_table_setting_td;
				var keyword_table_keyword_td;
				var keyword_table_inner_table;
				//target
				var target_table;
				var target_table_tr;
				var target_table_td;
				var target_table_inner_table;

				$.each(defaultLinkArray, function(document_id, elm) {
					var inner_table_tr = $("<TR />", {
						class: 'add_decidefile_inner_table_tr'
					}).appendTo(inner_tbody);
					var inner_table_doc_td = $("<TD />", {
						class: 'add_decidefile_inner_table_doc_td',
					}).appendTo(inner_table_tr);
					var inner_table_entry_td = $("<TD />", {
						class: 'add_decidefile_inner_table_entry_td',
						colspan: '2'
					}).appendTo(inner_table_tr);

					var type_entry_table = $("<TABLE />",{
						class: 'add_decidefile_type_entry_table'
					}).appendTo(inner_table_entry_td);
					var type_entry_table_tr = $("<TR />", {
						class: 'add_decidefile_type_entry_table_tr'
					}).appendTo(type_entry_table);
					var type_entry_table_default_td = $("<TD />", {
						class: 'add_decidefile_type_entry_table_default_td',
						text: 'Default設定'
					}).appendTo(type_entry_table_tr);
					var type_entry_table_entry_td = $("<TD />", {
						class: 'add_decidefile_type_entry_table_entry_td',
					}).appendTo(type_entry_table_tr);

					var entry_table = $("<TABLE />",{
						class: 'add_decidefile_entry_table'
					}).appendTo(type_entry_table_entry_td);
					var count = 0;
					$.each(elm, function(keyword_id, el) {
						if ( count == 0 ) {
							inner_table_doc_td.text(el['doc_title']);
						}
						var entry_table_tr = $("<TR />", {
							class: 'add_decidefile_entry_table_tr'
						}).appendTo(entry_table);
						var entry_table_keyword_td = $("<TD />", {
							class: 'add_decidefile_entry_table_keyword_td',
							text: el['keyword']
						}).appendTo(entry_table_tr);
						var entry_table_target_td = $("<TD />", {
							class: 'add_decidefile_entry_table_target_td',
							text: el['title']
						}).appendTo(entry_table_tr);

						count++;
					});

					if ( Object.keys(decideLinkArray).length > 0 ) {
						$.each(decideLinkArray, function(doc_id, elm) {
							if ( document_id == doc_id ) {
								var type_entry_table_tr = $("<TR />", {
									class: 'add_decidefile_type_entry_table_tr'
								}).appendTo(type_entry_table);
								var type_entry_table_detail_td = $("<TD />", {
									class: 'add_decidefile_type_entry_table_detail_td',
									text: '詳細設定'
								}).appendTo(type_entry_table_tr);
								var type_entry_table_entry_td = $("<TD />", {
									class: 'add_decidefile_type_entry_table_entry_td',
								}).appendTo(type_entry_table_tr);

								var entry_table = $("<TABLE />",{
									class: 'add_decidefile_entry_table'
								}).appendTo(type_entry_table_entry_td);

								$.each(elm, function(start, el) {
									var entry_table_tr = $("<TR />", {
										class: 'add_decidefile_entry_table_tr'
									}).appendTo(entry_table);
									var entry_table_keyword_td = $("<TD />", {
										class: 'add_decidefile_entry_table_keyword_td',
										html: start + '~' + el['end'] + '文字目の<br>' + el['keyword']
									}).appendTo(entry_table_tr);
									var entry_table_target_td = $("<TD />", {
										class: 'add_decidefile_entry_table_target_td',
										text: el['title']
									}).appendTo(entry_table_tr);
								});
							}
							
						});
					}

				});

			} else if ( Object.keys(decideLinkArray).length > 0 ) {
				var doc_td;
				var keyword_td;
				var target_td;
				//keyword
				var keyword_table;
				var keyword_table_tr;
				var keyword_table_setting_td;
				var keyword_table_keyword_td;
				var keyword_table_inner_table;
				//target
				var target_table;
				var target_table_tr;
				var target_table_td;
				var target_table_inner_table;

				$.each(decideLinkArray, function(doc_id, elm) {
					var inner_table_tr = $("<TR />", {
						class: 'add_decidefile_inner_table_tr'
					}).appendTo(inner_tbody);
					var inner_table_doc_td = $("<TD />", {
						class: 'add_decidefile_inner_table_doc_td',
					}).appendTo(inner_table_tr);
					var inner_table_entry_td = $("<TD />", {
						class: 'add_decidefile_inner_table_entry_td',
						colspan: '2'
					}).appendTo(inner_table_tr);

					var type_entry_table = $("<TABLE />",{
						class: 'add_decidefile_type_entry_table'
					}).appendTo(inner_table_entry_td);
					var type_entry_table_tr = $("<TR />", {
						class: 'add_decidefile_type_entry_table_tr'
					}).appendTo(type_entry_table);
					var type_entry_table_detail_td = $("<TD />", {
						class: 'add_decidefile_type_entry_table_detail_td',
						text: '詳細設定'
					}).appendTo(type_entry_table_tr);
					var type_entry_table_entry_td = $("<TD />", {
						class: 'add_decidefile_type_entry_table_entry_td',
					}).appendTo(type_entry_table_tr);

					var entry_table = $("<TABLE />",{
						class: 'add_decidefile_entry_table'
					}).appendTo(type_entry_table_entry_td);
					var count = 0;
					$.each(elm, function(start, el) {
						if ( count == 0 ) {
							inner_table_doc_td.text(el['doc_title']);
						}
						var entry_table_tr = $("<TR />", {
							class: 'add_decidefile_entry_table_tr'
						}).appendTo(entry_table);
						var entry_table_keyword_td = $("<TD />", {
							class: 'add_decidefile_entry_table_keyword_td',
							html: start + '~' + el['end'] + '文字目の<br>' + el['keyword']
						}).appendTo(entry_table_tr);
						var entry_table_target_td = $("<TD />", {
							class: 'add_decidefile_entry_table_target_td',
							text: el['title']
						}).appendTo(entry_table_tr);

						count++;
					});
					
				});

			}

			//モーダル作成
			var pop = new $pop(content, {
				type: 'inline',
				title: 'リンク先詳細設定',
				width: 800,
				modal: true,
				windowmode: false,
				close: true
			});
			$('.pWindow tbody').ready(function() {
				if ( $('.pWindow').position().top < 35 ) {
					$('.pWindow').offset({top: 40});
				}
			});
			var buttonDiv = $("<div />", {
				id: 'popButtonDiv'
			}).appendTo(content);
			$('<button />', {
				text: "中止",
				click : function(event) {
					pop.close();
				}
			}).appendTo(buttonDiv);
			$('<button />', {
				text: "データ更新",
				click: function(event) {
					var data = {
						'action': 'wix_setting_createDecidefile',
						'defaultLinkArray': defaultLinkArray,
						'decideLinkArray': decideLinkArray,
					};
					$.ajax({
						async: true,
						dataType: "json",
						type: "POST",
						url: ajaxurl,
						data: data,

						success: function(json) {
// console.log(json['test']);
							
							pop.close();

							var form = $('#default_detail_decideForm');
							var input = $("<input />", {
								type: 'text',
								class: 'default_detail_decideInfo',
								name: 'default_detail_decideInfo[0]',
								value: 'success',
							}).css({
								'display': 'none'
							}).appendTo(form);	
							$('.default_detail_decideButton').click();

						},

						error: function(xhr, textStatus, errorThrown){
							alert('wixSetting.js Error');
							pop.close();
						}
					});
		
				}
			}).appendTo(buttonDiv);

		}

	});

/***********************************************************************************************************/

			//Tab 2
	
	/***********************************************************************************************************/

	$('.decidefileDoc_page').on('click', function(event) {
		event.preventDefault();
		var document_id = $(this).attr('id');
		var document_url = $(this).attr('href');
		//iframeへの挿入
		$('#decidefileDoc_iframe')[0].contentDocument.location.replace(document_url);
		
		var data = {
			'action': 'wix_existing_decidefile_presentation',
			'doc_id': document_id,
			'tab': 'detailsettings_tab2',
		};
		$.ajax({
			async: true,
			dataType: "json",
			type: "POST",
			url: ajaxurl,
			data: data,

			success: function(json) {
console.log(json['latest_decideinfo']);

				var body = $.trim( json['body'] );
				$('.latest_decide_table').remove();

				//最新情報を提示
				var table = $("<TABLE />",{
					class: 'latest_decide_table'
				});
				var thead = $("<THEAD />", {
					class: 'latest_decide_thead'
				}).appendTo(table);
				$("<TH />", {
					text: '単語'
				}).css({'white-space': 'nowrap'}).appendTo(thead);
				$("<TH />", {
					text: 'リンク先URL情報'
				}).css({'white-space': 'nowrap'}).appendTo(thead);
				$("<TH />", {
					text: '周辺単語'
				}).css({'white-space': 'nowrap'}).appendTo(thead);
				var tbody = $("<TBODY />", {
					class: 'latest_decide_tbody'
				}).appendTo(table);

				var count = 0;
				$.each(json['latest_decideinfo'], function(start, elm) {
					var end = parseInt(elm['end']);

					var tr = $("<TR />", {
						id: 'latest_decide_tr' + count,
						class: 'latest_decide_tr',
						start_end: start + ':' + elm['end'],
						nextStart: elm['nextStart'],
					}).appendTo(tbody);
					var keyword_td = $("<TD />", {
						class: 'latest_decide_keyword_td',
						text: elm['keyword'],
					}).appendTo(tr);
					var target_td = $("<TD />", {
						class: 'latest_decide_target_td'
					}).appendTo(tr);
					if ( elm['title'] == 'no_attach' ) {
						var target_td_a = $("<A />", {
							id: 'latest_decide_target_td_a' + count,
							class: 'latest_decide_target_td_a',
							text: 'リンク生成しない'
						}).appendTo(target_td);
					} else {
						var target_td_a = $("<A />", {
							id: 'latest_decide_target_td_a' + count,
							class: 'latest_decide_target_td_a',
							target: 'blank',
							href: elm['target'],
							text: elm['title']
						}).appendTo(target_td);
					}

					var surword = '';
					if ( start == 0 ) {
						if ( end+5 <= body.length ) 
							surword = body.substr(end, 10);
						else
							surword = body.substr(end, body.length-end);
					} else if ( start < 4 ) {
						if ( end+5 <= body.length ) {
							surword = body.substr(0, start);
							surword = surword + ', ' + body.substr(end+1, 5);
						} else {
							surword = body.substr(0, start);
							surword = surword + ', ' + body.substr(end+1, body.length-end);
						}
					} else {
						if ( end+5 <= body.length ) {
							surword = body.substr(start-3, 3);
							surword = surword + ', ' + body.substr(end+1, 5);
						} else {
							surword = body.substr(start-3, 3);
							surword = surword + ', ' + body.substr(end+1, body.length-end);
						}
					}
					var surword_td = $("<TD />", {
						class: 'latest_decide_surword_td',
						text: surword
					}).appendTo(tr);


					count++;
				});
				$('#decidefile_latest_table_tbody_td').append(table);
	
				
				//履歴情報		
				var data = {
					'action': 'wix_decidefile_history',
					'doc_id': document_id,
				};
				$.ajax({
					async: true,
					dataType: "json",
					type: "POST",
					url: ajaxurl,
					data: data,

					success: function(json) {
console.log(json['decideinfo']);
						
						$('#decidefile_history_contents').children().remove();
						
						//タブを作成
						var ul = $("<UL />",{
							id: 'decide_history_tab'
						});
						$.each(json['decideinfo'], function(index, elm) {
							var i = index;
							index = parseInt(index) + parseInt(1);

							if ( index == 0 ) {
								var li = $("<LI />",{
									class: 'selected',
								}).appendTo(ul);
							} else {
								var li = $("<LI />",{}).appendTo(ul);
							}
							var a = $("<A />", {
								href: '#decide_history_tab' + index,
								text: 'ver.' + i
							}).appendTo(li);
						});
						$('#decidefile_history_contents').append(ul);

						//最新情報を提示
						var list_div = $("<DIV />", {
							id: 'decide_history_list'
						});
						$.each(json['decideinfo'], function(index, elm) {
							index = parseInt(index) + parseInt(1);

							var div_id = 'decide_history_tab' + index;
							var body = elm['body'];

							if ( index == 1 ) {
								var div = $("<DIV />", {
									id: div_id,
									class: 'decide_history_tabbox'
								}).appendTo(list_div);
							} else {
								var div = $("<DIV />", {
									id: div_id,
									class: 'decide_history_tabbox'
								}).css('display', 'none').appendTo(list_div);
							}

							var table = $("<TABLE />",{
								class: 'decide_history_table'
							}).appendTo(div);
							var thead = $("<THEAD />", {
								class: 'decide_history_thead'
							}).appendTo(table);
							$("<TH />", {
								text: '単語'
							}).css({'white-space': 'nowrap'}).appendTo(thead);
							$("<TH />", {
								text: 'リンク先URL情報'
							}).css({'white-space': 'nowrap'}).appendTo(thead);
							$("<TH />", {
								text: '周辺単語'
							}).css({'white-space': 'nowrap'}).appendTo(thead);
							var tbody = $("<TBODY />", {
								class: 'decide_history_tbody'
							}).appendTo(table);

							$.each(elm['decideInfo'], function(start, el) {

								var end = parseInt(el['end']);

								var tr = $("<TR />", {
									class: div_id + '_tr',
									start_end: start + ':' + el['end'],
									nextStart: el['nextStart'],
								}).appendTo(tbody);
								var keyword_td = $("<TD />", {
									class: div_id + '_keyword_td',
									text: el['keyword'],
								}).appendTo(tr);
								var target_td = $("<TD />", {
									class: div_id + '_target_td'
								}).appendTo(tr);
								if ( el['title'] == 'no_attach' ) {
									var target_td_a = $("<A />", {
										class: div_id + '_target_td_a',
										text: 'リンク生成しない'
									}).appendTo(target_td);
								} else {
									var target_td_a = $("<A />", {
										class: div_id + '_target_td_a',
										target: 'blank',
										href: el['target'],
										text: el['title']
									}).appendTo(target_td);
								}

								var surword = '';
								if ( start == 0 ) {
									if ( end+5 <= body.length ) 
										surword = body.substr(end, 10);
									else
										surword = body.substr(end, body.length-end);
								} else if ( start < 4 ) {
									if ( end+5 <= body.length ) {
										surword = body.substr(0, start);
										surword = surword + ', ' + body.substr(end+1, 5);
									} else {
										surword = body.substr(0, start);
										surword = surword + ', ' + body.substr(end+1, body.length-end);
									}
								} else {
									if ( end+5 <= body.length ) {
										surword = body.substr(start-3, 3);
										surword = surword + ', ' + body.substr(end+1, 5);
									} else {
										surword = body.substr(start-3, 3);
										surword = surword + ', ' + body.substr(end+1, body.length-end);
									}
								}
								var surword_td = $("<TD />", {
									class: 'latest_decide_surword_td',
									text: surword
								}).appendTo(tr);
								
							});

							var input = $("<input />", {
								type: 'button',
								id: 'decide_history_table' + index,
								class: 'decide_history_table_input',
								value: 'この履歴を最新版にする',
								click: function(event) {
									var form = $('#update_decidefileForm');
									var input = $("<input />", {
										type: 'text',
										class: 'update_decidefileInfo',
										name: 'update_decidefileInfo[0]',
										value: document_id,
									}).css({
										'display': 'none'
									}).appendTo(form);	

									$.each($('#decide_history_tab').children('li'), function(index, el) {
										if ( $(this).hasClass('active') ) {
											var version = $(this).find('a').text();
											var input = $("<input />", {
												type: 'text',
												class: 'update_decidefileInfo',
												name: 'update_decidefileInfo[1]',
												value: version,
											}).css({
												'display': 'none'
											}).appendTo(form);	
										}
									});
									$('.update_decidefileButton').click();
						
								}
							}).appendTo(div);
							
						});
						$('#decidefile_history_contents').append(list_div);
						
						//タブ機能
						$('.decide_history_tabbox:first').show();
						$('#decide_history_tab li:first').addClass('active');
						$('#decide_history_tab li').click(function() {
							$('#decide_history_tab li').removeClass('active');
							$(this).addClass('active');
							$('.decide_history_tabbox').hide();
							$($(this).find('a').attr('href')).fadeIn();
							return false;
						});

					},

					error: function(xhr, textStatus, errorThrown){
						alert('wixSetting.js Error');
					}
				});

			},

			error: function(xhr, textStatus, errorThrown){
				alert('wixSetting.js Error');
			}
		});
	});		



});