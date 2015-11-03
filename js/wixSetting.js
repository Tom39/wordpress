jQuery(function($) {

	//タブ機能
	$('.tabbox:first').show();
	$('#tab li:first').addClass('active');
	$('#tab li').click(function() {
		$('#tab li').removeClass('active');
		$(this).addClass('active');
		$('.tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();

		//WixFileコンテンツにcheckが付いてる時は、"Check All"のチェックを消さない
/*
*
*
*
*
*
*
*
*/

		return false;
	});

	$('.wixfile_tabbox:first').show();
	$('#wixfile_tab li:first').addClass('active');
	$('#wixfile_tab li').click(function() {
		$('#wixfile_tab li').removeClass('active');
		$(this).addClass('active');
		$('.wixfile_tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();

		//WixFileコンテンツにcheckが付いてる時は、"Check All"のチェックを消さない
		var index = $('#wixfile_tab li').index(this) + 1;
		if ( $('#wixfile_tab' + index).find('table tr td input:checkbox').is(':checked') == true ) {
			$('.wixfile_entry_allcheck').prop('checked', true);
		} else {
			$('.wixfile_entry_allcheck').prop('checked', false);
		}

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

	$('.second_wixfile_tabbox:first').show();
	$('#second_wixfile_tab li:first').addClass('active');
	$('#second_wixfile_tab li').click(function() {
		$('#second_wixfile_tab li').removeClass('active');
		$(this).addClass('active');
		$('.second_wixfile_tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();

		//WixFileコンテンツにcheckが付いてる時は、"Check All"のチェックを消さない
		var index = $('#second_wixfile_tab li').index(this) + 1;
		if ( $('#second_wixfile_tab' + index).find('table tr td input:checkbox').is(':checked') == true ) {
			$('.wixfile_entry_allcheck').prop('checked', true);
		} else {
			$('.wixfile_entry_allcheck').prop('checked', false);
		}

		return false;
	});


	//パターンファイルのフォーム追加
	$('#add_patternFile').click(function() {
		var parentElementName = '#wix_settings_form #pattern_filename ';

		//フォームを追加
		var pattern_filename_len = $(parentElementName + 'li').length;
		var insertElement = '<li><input type="text" name="pattern[' + pattern_filename_len + ']"> <input type="text" name="filename[' + pattern_filename_len + ']"></li>';
		$(parentElementName).append(insertElement);

		// 削除ボタンの一旦全消去し、配置し直す
		$(parentElementName + 'input[type="button"]').remove();

		var delete_btn = ' <input type="button" value="Delete" class="button button-primary button-large">';
		$(parentElementName + 'li').each(function(index) {
			$(parentElementName + 'li').eq(index).append(delete_btn);
		});


	});

	// 削除ボタンを押した場合の処理
	$(document).on('click', '#wix_settings_form #pattern_filename input[type="button"]', function(e) {
		var parentElementName = '#wix_settings_form #pattern_filename ';

		//フォームを削除
		var idx = $(e.target).parent().index();
		$('#wix_settings_form #pattern_filename li').eq(idx).remove();

		// フォームがひとつになるなら、削除ボタンは不要なので消去
		if ($(parentElementName + 'li').length == 1) $(parentElementName + 'input[type="button"]').remove();

		// フォームの番号を振り直す
		$(parentElementName + 'li').each(function(index) {
			 $(this).children('input:text:eq(0)').attr('name', 'pattern[' + index + ']');
			 $(this).children('input:text:eq(1)').attr('name', 'filename[' + index + ']');
		});

	});

	//WIXFileコンテンツの"編集"イベント
	$('.wixfile_entry_edit').click(function() {
		$(this)
			.parent()
			.hide()
			.next()
			.show('slow/400/fast', function() {
				$(this)
					.find('.wixfile_keyword_edit input[type=text]')
					.focus();

				$('.wixfile_entry_decide').off();
				$(this)
					.find('.wixfile_entry_decide')
					.click(function(event) {
						var former_keyword, former_target;
						var update_keyword, update_target;
						var org_keyword, org_target;

						$.each($(this).nextAll(), function(index, element) {
							//編集したテキストの中身に<div></div>変更用
							if ( index == 0 ) {
								update_keyword = $(element).children().val();
								former_keyword = $(this).parent().prev().find('.wixfile_keyword span').text();
							} else if ( index == 1 ) {
								update_target = $(element).children().val();
								former_target = $(this).parent().prev().find('.wixfile_target span a').attr('href');
							}
						});
/***************************************************************************************************************/
						//キーワードかターゲットに変更があったら更新用要素追加
						var count = 0;
						var flag = false;
						if ( (update_keyword != former_keyword) || (update_target != former_target) ) { 
							//つまりhiddenしてるtr
							var hidden_tr_checker = $(this).parent().next('input:text');

							if ( hidden_tr_checker.size() == 0 ) {
								//ここに入るのは"編集時のみ"
								$.each($('.update_element'), function(){ 
									if ( $(this).attr('name').indexOf('update_keywords') != -1 ) 
										count++;
								});
								// count = ($('.update_element').length) / 2;

								org_keyword = $(this)
													.parent()
													.prev()
													.find('.wixfile_keyword span')
													.text();

								org_target = $(this)
													.parent()
													.prev()
													.find('.wixfile_target span a')
													.attr('href');
								//オリジナルエントリ要素を追加
								$(this)
									.parent()
									.after( '<input type="text" id=org_update_element' 
												+ count 
												+ ' class="org_update_element" name="org_update_keywords[' 
												+ count 
												+ ']" value="' 
												+ org_keyword 
												+ '" style="display:none">')
									.after( '<input type="text" id=org_update_element' 
												+ count 
												+ ' class="org_update_element" name="org_update_targets[' 
												+ count 
												+ ']" value="' 
												+ org_target 
												+ '" style="display:none">');

							} else {

								$.each($(this).parent().nextAll(), function(index, el) {
									if ( index == 2 ) org_target = $(this).val();
									else if ( index == 3 ) org_keyword = $(this).val();
									else if ( index == 4) return false;									
								});

								if ( hidden_tr_checker.attr('name').indexOf('update') != -1 ) {
									count = hidden_tr_checker
														.attr('id')
														.substr( 'update_element'.length );
									flag = false;
								} else if ( hidden_tr_checker.attr('name').indexOf('insert') != -1 ) {
									count = hidden_tr_checker
														.attr('id')
														.substr( 'insert_element'.length );
									flag = true;
								}
							}

							//結果的に、元々のエントリから変化なかった時は、更新用要素を削除
							if ( (update_keyword == org_keyword) && (update_target == org_target) ) {
								//更新用要素を削除
								$.each($(this).parent().nextAll(), function(index, el) {
									if ( $(this).get()[0].localName == 'input' )
										$(this).remove();
									else 
										return false;
								});

							} else {

								if ( flag == false ) {
									$.each($(this).parent().nextAll('input:text'), function(index, el) {
										if ( $(this).attr('class') == 'update_element' ) $(this).remove();
										else return false;
									});
									$(this)
										.parent()
										.after( '<input type="text" id="update_element' 
													+ count 
													+ '" class="update_element" name="update_keywords[' 
													+ count 
													+ ']" value="' 
													+ update_keyword 
													+ '" style="display:none">')
										.after( '<input type="text" id="update_element' 
													+ count 
													+ '" class="update_element" name="update_targets[' 
													+ count 
													+ ']" value="' 
													+ update_target 
													+ '" style="display:none">');
								
								} else {
									$.each($(this).parent().nextAll('input:text'), function(index, el) {
										if ( $(this).attr('class') == 'update_element') $(this).remove();
										else return false;
									});

									$(this)
										.parent()
										.after( '<input type="text" id="insert_element' 
													+ count 
													+ '" class="update_element" name="insert_keywords[' 
													+ count 
													+ ']" value="' 
													+ update_keyword 
													+ '" style="display:none">')
										.after( '<input type="text" id="insert_element' 
													+ count 
													+ '" class="update_element" name="insert_targets[' 
													+ count 
													+ ']" value="' 
													+ update_target 
													+ '" style="display:none">');
								}								
							}
							//表示部分を書き換え
							$(this)
								.parent()
								.prev()
								.find('.wixfile_keyword span')
								.text(update_keyword);

							if ( update_target.length < 28 ) {
								$(this)
									.parent()
									.prev()
									.find('.wixfile_target span a')
									.text(update_target)
									.attr('href', update_target);
							} else {
								$(this)
									.parent()
									.prev()
									.find('.wixfile_target span a')
									.text(update_target.substr(0, 27) + '...')
									.attr('href', update_target);
							}
						}


















						// if ( (update_keyword != former_keyword) || (update_target != former_target) ) { 

						// 	var count = ($('.update_element').length) / 2;
						// 	if ( $(this).parent().next('input:text').size() == 0 ) {
						// 		org_keyword = $(this)
						// 							.parent()
						// 							.prev()
						// 							.find('.wixfile_keyword span')
						// 							.text();

						// 		org_target = $(this)
						// 							.parent()
						// 							.prev()
						// 							.find('.wixfile_target span a')
						// 							.attr('href');

						// 		//オリジナルエントリ要素を追加
						// 		$(this)
						// 			.parent()
						// 			.after( '<input type="text" id=org_update_element' 
						// 						+ count 
						// 						+ ' class="org_update_element" name="org_update_keywords[' 
						// 						+ count 
						// 						+ ']" value="' 
						// 						+ org_keyword 
						// 						+ '" style="display:none">')
						// 			.after( '<input type="text" id=org_update_element' 
						// 						+ count 
						// 						+ ' class="org_update_element" name="org_update_targets[' 
						// 						+ count 
						// 						+ ']" value="' 
						// 						+ org_target 
						// 						+ '" style="display:none">');

						// 	} else {
						// 		org_keyword = $(this)
						// 							.parent()
						// 							.siblings('.org_update_element')
						// 							.eq(1)
						// 							.val();

						// 		org_target = $(this)
						// 							.parent()
						// 							.siblings('.org_update_element')
						// 							.eq(0)
						// 							.val();

						// 		count = $(this)
						// 					.parent()
						// 					.next('input:text')
						// 					.attr('id')
						// 					.substr( 'update_element'.length );
						// 	}

						// 	//結果的に、元々のエントリから変化なかった時は、更新用要素を削除
						// 	if ( (update_keyword == org_keyword) && (update_target == org_target) ) {
						// 		//更新用要素を削除
						// 		$.each($(this).parent().nextAll(), function(index, el) {
						// 			if ( $(this).get()[0].localName == 'input' )
						// 				$(this).remove();
						// 			else 
						// 				return false;
						// 		});

						// 	} else {
						// 		$.each($(this).parent().nextAll('input:text'), function(index, el) {
						// 			if ( $(this).attr('class') == 'update_element' || $(this).attr('class') == 'insert_element') $(this).remove();
						// 			else return false;
						// 		});
						// 		$(this)
						// 			.parent()
						// 			.after( '<input type="text" id="update_element' 
						// 						+ count 
						// 						+ '" class="update_element" name="update_keywords[' 
						// 						+ count 
						// 						+ ']" value="' 
						// 						+ update_keyword 
						// 						+ '" style="display:none">')
						// 			.after( '<input type="text" id="update_element' 
						// 						+ count 
						// 						+ '" class="update_element" name="update_targets[' 
						// 						+ count 
						// 						+ ']" value="' 
						// 						+ update_target 
						// 						+ '" style="display:none">');
						// 	}
						// 	//表示部分を書き換え
						// 	$(this)
						// 		.parent()
						// 		.prev()
						// 		.find('.wixfile_keyword span')
						// 		.text(update_keyword);

						// 	if ( update_target.length < 28 ) {
						// 		$(this)
						// 			.parent()
						// 			.prev()
						// 			.find('.wixfile_target span a')
						// 			.text(update_target)
						// 			.attr('href', update_target);
						// 	} else {
						// 		$(this)
						// 			.parent()
						// 			.prev()
						// 			.find('.wixfile_target span a')
						// 			.text(update_target.substr(0, 27) + '...')
						// 			.attr('href', update_target);
						// 	}
						// }
/***************************************************************************************************************/
						//表示
						$(this)
							.parent()
							.hide()
							.prev()
							.show();
					});
			});
	});

	//WIXファイル編集フォームでのEnter禁止
	$('.wixfile_keyword_edit, .wixfile_target_edit').keypress(function(e){
		if( (e.which == 13) || (e.keyCode == 13) )
			return false;
	});

	$('.wixfile_keyword_edit input:text, .wixfile_target_edit input:text').focus(function(event) {
		 $(this).select();
	});

	//WIXFileコンテンツの"削除"イベント
	$('.wixfile_entry_delete').click(function(event) {
		var content = '';
		$.each($(this).nextAll(), function(index, el) {
			if ( index == 0 )
				content =  $(this).text();
			if ( index == 1 )
				content = content + '<br>と<br>' + $(this).text() + '<br>を削除しますがよろしいですか？';
		});;

		var pop = new $pop(content , {
			type: 'confirm',
			title: 'WIXファイルデータ削除',
			YES: function () {
				//DB内のWIXファイル削除用要素を作成
				var count = ($('.delete_element').length) / 2;
				var delete_keyword, delete_target;

				$.each($(event.target).parents('td').nextAll(), function(index, el) {
					if ( index == 0 ) 
						delete_keyword = $(this).text();
					if ( index == 1 )
						delete_target = $(this).find('a').attr('href');
				});

				//更新をして、やっぱ削除するって時は、更新用に作った要素を削除
				if ( $(event.target).parents('tr').next().next('.update_element').length != 0 ) {
					var roop = 0
					while ( roop < 4 ) {
						$(event.target)
							.parents('tr')
							.next()
							.next()
							.remove();
						roop++;
					}
				}
			

				$(event.target)
					.parents('tr')
					.next()
					.after( '<input type="text" id="delete_element' 
								+ count 
								+ '" class="delete_element" name="delete_keywords[' 
								+ count
								+ ']" value="' 
								+ delete_keyword 
								+ '" style="display:none">' );
				$(event.target)
					.parents('tr')
					.next()
					.after( '<input type="text" id="delete_element' 
								+ count 
								+ '" class="delete_element" name="delete_targets[' 
								+ count
								+ ']" value="' 
								+ delete_target 
								+ '" style="display:none">' );

				//(隠し)エントリ削除
		  		$(event.target)
					.parents('tr')
					.next()
					.remove();

				$(event.target)
					.parents('tr')
					.remove();
				
			},
			NO: function () {
				return false;
			},
			close: true,
			resize: true
		});
	});

	//セレクトボックス"全選択"イベント
	$('.wixfile_entry_allcheck').click(function(event) {
		if ( $('.wixfile_tabbox').is(':visible') == true ) {
			$.each($('.wixfile_tabbox'), function(index, el) {
				if ( $(this).css('display') == 'block' ) {
					var items = $(this).find('table tr td input:checkbox');
					if ( $(event.target).is(':checked') ) //"Check All"にチェックが付いているか否か
						items.prop('checked', true);
					else
						items.prop('checked', false);

					return false;
				}
			});
		} else if ( $('.second_wixfile_tabbox').is(':visible') == true ) {
			$.each($('.second_wixfile_tabbox'), function(index, el) {
				if ( $(this).css('display') == 'block' ) {
					var items = $(this).find('table tr td input:checkbox');
					if ( $(event.target).is(':checked') ) //"Check All"にチェックが付いているか否か
						items.prop('checked', true);
					else
						items.prop('checked', false);

					return false;
				}
			});
		}
	});

	//複数エントリ一括削除
	$('.wixfile_entry_batch_delete').click(function(event) {
		var content = '';

		if ( $('.wixfile_tabbox').is(':visible') == true ) {
			$.each($('.wixfile_tabbox'), function(i, el) {
				if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
					var deleteArray = new Array();

					$.each($(this).find('table tr td input:checkbox'), function(j, elm) { //今見えてるエントリ群
						if ( $(this).is(':checked') ) { //チェックが付いている要素
							var keyword = '';

							$.each($(this).parents('td').nextAll(), function(index, element) {
								if ( index == 2 ) {
									keyword = $(this).find('span').text();
								} else if ( index == 3 ) {
									var target = $(this).find('span a').attr('href');
									if ( keyword in deleteArray ) {
										var tmpArray = deleteArray[keyword];
										tmpArray.push( target );
										deleteArray[keyword] = tmpArray;
									} else {
										deleteArray[keyword] = [target];
									}
								}
							});
						
						}
					});

					if ( Object.keys(deleteArray).length != 0 ) {

						content = $("<div />", {
							id: 'delete_popTableDiv'
						});
						var table = $("<TABLE />",{
							class: 'delete_popTable'
						}).appendTo(content);
						var tr = $("<TR />", {
							class: 'delete_popTr'
						}).appendTo(table);
						$("<TH />", {
							text: 'キーワード'
						}).css({'white-space': 'nowrap'}).appendTo(tr);
						$("<TH />", {
							text: 'リンク先URL'
						}).css({'white-space': 'nowrap'}).appendTo(tr);

						for (var keyword in deleteArray) {
							var targetArray = deleteArray[keyword];

							var tr = $("<TR />").appendTo(table);
							var td = $("<TD />",{
								class: 'delete_keywordTd',
								text: keyword,
							}).appendTo(tr);
							var td2 = $("<TD />").appendTo(tr);

							for (var index in targetArray) {
								$("<div />", {
									class: 'delete_targetTd',
									text: targetArray[index],
								}).css({'white-space': 'nowrap'}).appendTo(td2);

							}
						}
					}

					return false;
				}
			});

			if ( content != '' ) {
				var pop = new $pop(content.html() , {
					type: 'confirm',
					title: 'WIXファイルデータ削除',
					YES: function () {
						$.each($('.wixfile_tabbox'), function(i, el) {
							if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
								var wixContents = $(this);

								$.each($(this).find('table tr td input:checkbox'), function(j, elm) { //今見えてるエントリ群
									if ( $(this).is(':checked') ) { //チェックが付いている要素
										var entryTr = $(this).parents('tr');
										
										//DB内のWIXファイル削除用要素を作成
										var count = ($('.delete_element').length) / 2;
										var delete_keyword, delete_target;

										$.each(entryTr.children(), function(index, el) {
											if ( index == 3 ) 
												delete_keyword = $(el).text();
											if ( index == 4 )
												delete_target = $(el).find('a').attr('href');
										});

										// //更新をして、やっぱ削除するって時は、更新用に作った要素を削除
										if ( entryTr.next().next('.update_element').length != 0 ) {
											var roop = 0
											while ( roop < 4 ) {
												entryTr
													.next()
													.next()
													.remove();
												roop++;
											}
										}
									
										entryTr
											.next()
											.after( '<input type="text" id="delete_element' 
														+ count 
														+ '" class="delete_element" name="delete_keywords[' 
														+ count
														+ ']" value="' 
														+ delete_keyword 
														+ '" style="display:none">' );
										entryTr
											.next()
											.after( '<input type="text" id="delete_element' 
														+ count 
														+ '" class="delete_element" name="delete_targets[' 
														+ count
														+ ']" value="' 
														+ delete_target 
														+ '" style="display:none">' );

										//(隠し)エントリ削除
										entryTr
											.next()
											.remove();
										entryTr
											.remove();

									}
								});
								
								$('#wixfile_entry_allcheck').prop('checked', false);	
							}
						});
					},
					NO: function () {
						return false;
					},
					close: true,
					resize: true
				});
			}
		
		} else if ( $('.second_wixfile_tabbox').is(':visible') == true ) {
			$.each($('.second_wixfile_tabbox'), function(i, el) {
				if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
					var deleteArray = new Array();

					$.each($(this).find('table tr td input:checkbox'), function(j, elm) { //今見えてるエントリ群
						if ( $(this).is(':checked') ) { //チェックが付いている要素
							var keyword = '';

							$.each($(this).parents('td').nextAll(), function(index, element) {
								if ( index == 2 ) {
									keyword = $(this).find('span').text();
								} else if ( index == 3 ) {
									var target = $(this).find('span a').attr('href');
									if ( keyword in deleteArray ) {
										var tmpArray = deleteArray[keyword];
										tmpArray.push( target );
										deleteArray[keyword] = tmpArray;
									} else {
										deleteArray[keyword] = [target];
									}
								}
							});
						
						}
					});

					if ( Object.keys(deleteArray).length != 0 ) {

						content = $("<div />", {
							id: 'delete_popTableDiv'
						});
						var table = $("<TABLE />",{
							class: 'delete_popTable'
						}).appendTo(content);
						var tr = $("<TR />", {
							class: 'delete_popTr'
						}).appendTo(table);
						$("<TH />", {
							text: 'キーワード'
						}).css({'white-space': 'nowrap'}).appendTo(tr);
						$("<TH />", {
							text: 'リンク先URL'
						}).css({'white-space': 'nowrap'}).appendTo(tr);

						for (var keyword in deleteArray) {
							var targetArray = deleteArray[keyword];

							var tr = $("<TR />").appendTo(table);
							var td = $("<TD />",{
								class: 'delete_keywordTd',
								text: keyword,
							}).appendTo(tr);
							var td2 = $("<TD />").appendTo(tr);

							for (var index in targetArray) {
								$("<div />", {
									class: 'delete_targetTd',
									text: targetArray[index],
								}).css({'white-space': 'nowrap'}).appendTo(td2);

							}
						}
					}

					return false;
				}
			});

			if ( content != '' ) {
				var pop = new $pop(content.html() , {
					type: 'confirm',
					title: 'WIXファイルデータ削除',
					YES: function () {
						$.each($('.second_wixfile_tabbox'), function(i, el) {
							if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
								var wixContents = $(this);

								$.each($(this).find('table tr td input:checkbox'), function(j, elm) { //今見えてるエントリ群
									if ( $(this).is(':checked') ) { //チェックが付いている要素
										var entryTr = $(this).parents('tr');
										
										//DB内のWIXファイル削除用要素を作成
										var count = ($('.delete_element').length) / 2;
										var delete_keyword, delete_target;

										$.each(entryTr.children(), function(index, el) {
											if ( index == 3 ) 
												delete_keyword = $(el).text();
											if ( index == 4 )
												delete_target = $(el).find('a').attr('href');
										});

										// //更新をして、やっぱ削除するって時は、更新用に作った要素を削除
										if ( entryTr.next().next('.update_element').length != 0 ) {
											var roop = 0
											while ( roop < 4 ) {
												entryTr
													.next()
													.next()
													.remove();
												roop++;
											}
										}
									
										entryTr
											.next()
											.after( '<input type="text" id="delete_element' 
														+ count 
														+ '" class="delete_element" name="delete_keywords[' 
														+ count
														+ ']" value="' 
														+ delete_keyword 
														+ '" style="display:none">' );
										entryTr
											.next()
											.after( '<input type="text" id="delete_element' 
														+ count 
														+ '" class="delete_element" name="delete_targets[' 
														+ count
														+ ']" value="' 
														+ delete_target 
														+ '" style="display:none">' );

										//(隠し)エントリ削除
										entryTr
											.next()
											.remove();
										entryTr
											.remove();

									}
								});
								
								$('#wixfile_entry_allcheck').prop('checked', false);	
							}
						});
					},
					NO: function () {
						return false;
					},
					close: true,
					resize: true
				});
			}
		} 

	});

/******************↓ここ直す*************************************************************************************************************/
	//複数エントリ一括編集
	$('.wixfile_entry_batch_edit').click(function(event) {
		var content = '';

		if ( $('.wixfile_tabbox').is(':visible') == true ) {
			$.each($('.wixfile_tabbox'), function(i, el) {
				if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
					var editArray = new Array();

					$.each($(this).find('table tr td input:checkbox'), function(j, elm) { //今見えてるエントリ群
						if ( $(this).is(':checked') ) { //チェックが付いている要素
							var keyword = '';
							var entryId = '';

							$.each($(this).parents('td').nextAll(), function(index, element) {
								if ( index == 2 ) {
									keyword = $(this).find('span').text();
									entryId = $(this).parent('tr').attr('id');
								} else if ( index == 3 ) {
									var target = $(this).find('span a').attr('href');
									editArray[entryId] = {
															'keyword': keyword,
															'target': target
															};
								}
							});
						
						}
					});

					if ( Object.keys(editArray).length != 0 ) {
						content = $("<div />", {
							id: 'edit_popTableDiv'
						});
						var tableDiv = $("<div />", {
							id: 'edit_popTableDiv'
						}).appendTo(content);
						var table = $("<TABLE />",{
							id: 'edit_popTable',
							class: 'edit_popTable'
						}).appendTo(tableDiv);
						var tr = $("<TR />", {
							id: 'edit_popTableTr',
							class: 'edit_popTableTr'
						}).appendTo(table);
						$("<TH />", {
							text: 'キーワード'
						}).css({'white-space': 'nowrap'}).appendTo(tr);
						$("<TH />", {
							text: 'リンク先URL'
						}).css({'white-space': 'nowrap'}).appendTo(tr);

						for (var entryId in editArray) {
							var keyword = editArray[entryId]['keyword'];
							var target = editArray[entryId]['target'];

							var tr = $("<TR />", {
								class: 'edit_entryTr'
							}).appendTo(table);

							var td = $("<TD />", {
								class: 'edit_keywordTd'
							}).appendTo(tr);
							var input = $("<input />", {
								type: 'text',
								class: 'editKeyword',
								value: keyword,
							}).appendTo(td);
							var input = $("<input />", {
								type: 'text',
								class: 'former_editKeyword',
								alt: entryId,
								value: keyword,
							}).css('display', 'none').appendTo(td);

							var td2 = $("<TD />", {
								class: 'edit_targetTd'
							}).appendTo(tr);
							var input = $("<input />", {
								type: 'text',
								class: 'editTarget',
								value: target,
							}).appendTo(td2);
							var input = $("<input />", {
								type: 'text',
								class: 'former_editTarget',
								alt: entryId,
								value: target,
							}).css('display', 'none').appendTo(td2);
						}
					}

					return false;
				}
			});

			if ( content != '' ) {
				var pop = new $pop(content, {
					type: 'inline',
					title: 'WIXファイルデータ編集',
					width: 500,
					modal: true,
					windowmode: false,
					close: true
				});

				var buttonDiv = $("<div />", {
					id: 'popButtonDiv'
				}).appendTo(content);
				$('<button />', {
					text: "データ更新",
					click: function(event) {
						var former_keyword, former_target;
						var update_keyword, update_target;
						var org_keyword, org_target;
						var entryId;

						$.each($('.wixfile_tabbox'), function(i, el) {
							if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
								var wixContents = $(this);

								$.each($('.edit_entryTr'), function(index, el) {
									update_keyword = $(this).find('.editKeyword').val();
									update_target = $(this).find('.editTarget').val();

									former_keyword = $(this).find('.former_editKeyword').val();
									former_target = $(this).find('.former_editTarget').val();
									entryId = $(this).find('.former_editTarget').attr('alt');

									var subectElement = $(wixContents).find('#' + entryId);


									//キーワードかターゲットに変更があったら更新用要素追加
									if ( (update_keyword != former_keyword) || (update_target != former_target) ) {
										var count = (subectElement.find('.update_element').length) / 2;

										if ( subectElement.next().next('input:text').size() == 0 ) {
											org_keyword = former_keyword;
											org_target = former_target;

											//オリジナルエントリ要素を追加
											subectElement
												.next()
												.after( '<input type="text" id=org_update_element' 
															+ count 
															+ ' class="org_update_element" name="org_update_keywords[' 
															+ count 
															+ ']" value="' 
															+ org_keyword 
															+ '" style="display:none">')
												.after( '<input type="text" id=org_update_element' 
															+ count 
															+ ' class="org_update_element" name="org_update_targets[' 
															+ count 
															+ ']" value="' 
															+ org_target 
															+ '" style="display:none">');

										} else {
											org_keyword = subectElement
																.siblings('.org_update_element')
																.eq(1)
																.val();

											org_target = subectElement
																.siblings('.org_update_element')
																.eq(0)
																.val();

											count = subectElement
														.next()
														.next('input:text')
														.attr('id')
														.substr( 'update_element'.length );
										}

										//結果的に、元々のエントリから変化なかった時は、更新用要素を削除
										if ( (update_keyword == org_keyword) && (update_target == org_target) ) {
											//更新用要素を削除
											$.each(subectElement.nextAll(), function(index, el) {
												if ( index == 0 ) return true; 
												if ( $(this).get()[0].localName == 'input' )
													$(this).remove();
												else 
													return false;
											});

										} else {
											//更新用要素を削除・追加
											subectElement
												.nextAll('input:text')
												.remove('.update_element');
											subectElement
												.next()
												.after( '<input type="text" id="update_element' 
															+ count 
															+ '" class="update_element" name="update_keywords[' 
															+ count 
															+ ']" value="' 
															+ update_keyword 
															+ '" style="display:none">')
												.after( '<input type="text" id="update_element' 
															+ count 
															+ '" class="update_element" name="update_targets[' 
															+ count 
															+ ']" value="' 
															+ update_target 
															+ '" style="display:none">');
										}
										//表示部分を書き換え
										subectElement
											.find('.wixfile_keyword span')
											.text(update_keyword);

										if ( update_target.length < 28 ) {
											subectElement
												.find('.wixfile_target span a')
												.text(update_target)
												.attr('href', update_target);
										} else {
											subectElement
												.find('.wixfile_target span a')
												.text(update_target.substr(0, 27) + '...')
												.attr('href', update_target);
										}

										//hiddenの方も書き換え
										subectElement
											.next()
											.find('.wixfile_keyword_edit input[type="text"]')
											.val(update_keyword);
										subectElement
											.next()
											.find('.wixfile_target_edit input[type="text"]')
											.val(update_target);
									}
								});
							}
						});
						pop.close();
					}
				}).appendTo(buttonDiv);
				$('<button />', {
					text: "中止",
					click : function(event) {
						pop.close();
					}
				}).appendTo(buttonDiv);
			}
		
		} else if ( $('.second_wixfile_tabbox').is(':visible') == true ) {
			$.each($('.second_wixfile_tabbox'), function(i, el) {
				if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
					var editArray = new Array();

					$.each($(this).find('table tr td input:checkbox'), function(j, elm) { //今見えてるエントリ群
						if ( $(this).is(':checked') ) { //チェックが付いている要素
							var keyword = '';
							var entryId = '';

							$.each($(this).parents('td').nextAll(), function(index, element) {
								if ( index == 2 ) {
									keyword = $(this).find('span').text();
									entryId = $(this).parent('tr').attr('id');
								} else if ( index == 3 ) {
									var target = $(this).find('span a').attr('href');
									editArray[entryId] = {
															'keyword': keyword,
															'target': target
															};
								}
							});
						
						}
					});

					if ( Object.keys(editArray).length != 0 ) {
						content = $("<div />", {
							id: 'edit_popTableDiv'
						});
						var tableDiv = $("<div />", {
							id: 'edit_popTableDiv'
						}).appendTo(content);
						var table = $("<TABLE />",{
							id: 'edit_popTable',
							class: 'edit_popTable'
						}).appendTo(tableDiv);
						var tr = $("<TR />", {
							id: 'edit_popTableTr',
							class: 'edit_popTableTr'
						}).appendTo(table);
						$("<TH />", {
							text: 'キーワード'
						}).css({'white-space': 'nowrap'}).appendTo(tr);
						$("<TH />", {
							text: 'リンク先URL'
						}).css({'white-space': 'nowrap'}).appendTo(tr);

						for (var entryId in editArray) {
							var keyword = editArray[entryId]['keyword'];
							var target = editArray[entryId]['target'];

							var tr = $("<TR />", {
								class: 'edit_entryTr'
							}).appendTo(table);

							var td = $("<TD />", {
								class: 'edit_keywordTd'
							}).appendTo(tr);
							var input = $("<input />", {
								type: 'text',
								class: 'editKeyword',
								value: keyword,
							}).appendTo(td);
							var input = $("<input />", {
								type: 'text',
								class: 'former_editKeyword',
								alt: entryId,
								value: keyword,
							}).css('display', 'none').appendTo(td);

							var td2 = $("<TD />", {
								class: 'edit_targetTd'
							}).appendTo(tr);
							var input = $("<input />", {
								type: 'text',
								class: 'editTarget',
								value: target,
							}).appendTo(td2);
							var input = $("<input />", {
								type: 'text',
								class: 'former_editTarget',
								alt: entryId,
								value: target,
							}).css('display', 'none').appendTo(td2);
						}
					}

					return false;
				}
			});

			if ( content != '' ) {
				var pop = new $pop(content, {
					type: 'inline',
					title: 'WIXファイルデータ編集',
					width: 500,
					modal: true,
					windowmode: false,
					close: true
				});

				var buttonDiv = $("<div />", {
					id: 'popButtonDiv'
				}).appendTo(content);
				$('<button />', {
					text: "データ更新",
					click: function(event) {
						var former_keyword, former_target;
						var update_keyword, update_target;
						var org_keyword, org_target;
						var entryId;

						$.each($('.second_wixfile_tabbox'), function(i, el) {
							if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
								var wixContents = $(this);

								$.each($('.edit_entryTr'), function(index, el) {
									update_keyword = $(this).find('.editKeyword').val();
									update_target = $(this).find('.editTarget').val();

									former_keyword = $(this).find('.former_editKeyword').val();
									former_target = $(this).find('.former_editTarget').val();
									entryId = $(this).find('.former_editTarget').attr('alt');

									var subectElement = $(wixContents).find('#' + entryId);


									//キーワードかターゲットに変更があったら更新用要素追加
									if ( (update_keyword != former_keyword) || (update_target != former_target) ) {
										var count = (subectElement.find('.update_element').length) / 2;

										if ( subectElement.next().next('input:text').size() == 0 ) {
											org_keyword = former_keyword;
											org_target = former_target;

											//オリジナルエントリ要素を追加
											subectElement
												.next()
												.after( '<input type="text" id=org_update_element' 
															+ count 
															+ ' class="org_update_element" name="org_update_keywords[' 
															+ count 
															+ ']" value="' 
															+ org_keyword 
															+ '" style="display:none">')
												.after( '<input type="text" id=org_update_element' 
															+ count 
															+ ' class="org_update_element" name="org_update_targets[' 
															+ count 
															+ ']" value="' 
															+ org_target 
															+ '" style="display:none">');

										} else {
											org_keyword = subectElement
																.siblings('.org_update_element')
																.eq(1)
																.val();

											org_target = subectElement
																.siblings('.org_update_element')
																.eq(0)
																.val();

											count = subectElement
														.next()
														.next('input:text')
														.attr('id')
														.substr( 'update_element'.length );
										}

										//結果的に、元々のエントリから変化なかった時は、更新用要素を削除
										if ( (update_keyword == org_keyword) && (update_target == org_target) ) {
											//更新用要素を削除
											$.each(subectElement.nextAll(), function(index, el) {
												if ( index == 0 ) return true; 
												if ( $(this).get()[0].localName == 'input' )
													$(this).remove();
												else 
													return false;
											});

										} else {
											//更新用要素を削除・追加
											subectElement
												.nextAll('input:text')
												.remove('.update_element');
											subectElement
												.next()
												.after( '<input type="text" id="update_element' 
															+ count 
															+ '" class="update_element" name="update_keywords[' 
															+ count 
															+ ']" value="' 
															+ update_keyword 
															+ '" style="display:none">')
												.after( '<input type="text" id="update_element' 
															+ count 
															+ '" class="update_element" name="update_targets[' 
															+ count 
															+ ']" value="' 
															+ update_target 
															+ '" style="display:none">');
										}
										//表示部分を書き換え
										subectElement
											.find('.wixfile_keyword span')
											.text(update_keyword);

										if ( update_target.length < 28 ) {
											subectElement
												.find('.wixfile_target span a')
												.text(update_target)
												.attr('href', update_target);
										} else {
											subectElement
												.find('.wixfile_target span a')
												.text(update_target.substr(0, 27) + '...')
												.attr('href', update_target);
										}

										//hiddenの方も書き換え
										subectElement
											.next()
											.find('.wixfile_keyword_edit input[type="text"]')
											.val(update_keyword);
										subectElement
											.next()
											.find('.wixfile_target_edit input[type="text"]')
											.val(update_target);
									}
								});
							}
						});
						pop.close();
					}
				}).appendTo(buttonDiv);
				$('<button />', {
					text: "中止",
					click : function(event) {
						pop.close();
					}
				}).appendTo(buttonDiv);
			}
		} 

	});


	//エントリ追加
	$('#add_wixfile_entry').click(function(event) {
		//エントリ情報取得
		var keyword = '';
		var target = '';
		var entryElement = $(this).siblings('fieldset').children('input[type="text"]');
		$.each(entryElement, function(index, el) {
			if ( index == 0 ) keyword = $(this).val();
			if ( index == 1 ) target = $(this).val();
		});

		if ( keyword != '' && target != '' ) {
			//対象wixfile_tabbox
			var subjectTable = $('.wixfile_tabbox:last tbody');
			var tmp_subjectTable = subjectTable;
			console.log(subjectTable);

			if ( subjectTable.find('tr').size() == 0 ) {
				console.log('subjectTableを最後から2番目に切り替えます');
				subjectTable = tmp_subjectTable.parents('.wixfile_tabbox').prev().find('table tbody');
			}

			//現在の最後のエントリ要素取得
			var existingEntry_lastElement = subjectTable.find('tr:last');
			console.log( '最後尾のエントリは' );
			console.log( existingEntry_lastElement );

			//挿入エントリに使用するNewIDを作成
			var newId = parseInt(existingEntry_lastElement
									.attr('id')
									.substr('wixfile_entry_hidden'.length) )
									+ 1;
			var newClass = '';
			if ( newId % 2 == 0 ) newClass = 'wixfile_even';
			else newClass = 'wixfile_odd';

			//現在の最後尾エントリ要素を複製し調整
			var newHiddenElement = existingEntry_lastElement
										.clone(true)
										.attr({
											'id': 'wixfile_entry_hidden' + newId,
											'class': newClass
										});

			var newElement = existingEntry_lastElement
										.prev()
										.clone(true)
										.attr({
											'id': 'wixfile_entry' + newId,
											'class': newClass,
										});

			//複製した要素の子要素を調整
			$.each(newHiddenElement.children('td'), function(index, el) {
				if ( index == 1 ) {
					$(this).attr('id', 'wixfile_entry_decide' + newId);

				} else if ( index == 2 ) {
					$(this).attr('id', 'wixfile_keyword_edit' + newId);
					$(this).children('input').val(keyword);

				} else if ( index == 3 ) {
					$(this).attr('id', 'wixfile_target_edit' + newId);
					$(this).children('input').val(target);

				}
			});
			$.each(newElement.children('td'), function(index, el) {
				if ( index == 1 ) {
					$(this).attr('id', 'wixfile_entry_edit' + newId);

				} else if ( index == 2 ) {
					$(this).attr('id', 'wixfile_entry_delete' + newId);

				} else if ( index == 3 ) {
					$(this).attr('id', 'wixfile_keyword' + newId);
					$(this).children('span').text(keyword);

				} else if ( index == 4 ) {
					$(this).attr('id', 'wixfile_target' + newId);
					if ( target.length < 29 ) {
						$(this).find('span a')
									.attr('href', target)
									.text(target);
					} else {
						$(this).find('span a')
									.attr('href', target)
									.text(target.substr(0, 27) + '...');
					}
				}
			});


			if ( tmp_subjectTable.find('tr').size() != 0 ) {
				//もし最後尾タブが20エントリ(hidden含め40)で一杯だったら、新規タブを作ってそこに挿入
				if ( subjectTable.find('tr').length < 40 ) {
					console.log('subjectTableのTRエントリは40より少ない');
					//DB挿入用の隠し要素4つも挿入
					var count = 0;
					$.each($('.update_element'), function(){ 
						if ( $(this).attr('name').indexOf('insert') != -1 ) 
							count++;
					});
					count = count / 2; //keywordとtarget両方換算してしまっている
					console.log( 'countは' );
					console.log(count);

					if ( existingEntry_lastElement.next('input:text').size() == 0 ) {
						existingEntry_lastElement
										.after( '<input type="text" id=org_update_element' 
													+ count 
													+ ' class="org_update_element" name="org_insert_keywords[' 
													+ count 
													+ ']" value="' 
													+ keyword 
													+ '" style="display:none">')
										.after( '<input type="text" id=org_update_element' 
													+ count 
													+ ' class="org_update_element" name="org_insert_targets[' 
													+ count 
													+ ']" value="' 
													+ target 
													+ '" style="display:none">')
										.after( '<input type="text" id="insert_element' 
													+ count 
													+ '" class="update_element" name="insert_keywords[' 
													+ count 
													+ ']" value="' 
													+ keyword 
													+ '" style="display:none">')
										.after( '<input type="text" id="insert_element' 
													+ count 
													+ '" class="update_element" name="insert_targets[' 
													+ count 
													+ ']" value="' 
													+ target 
													+ '" style="display:none">')
										.after( newHiddenElement )
										.after( newElement );
					} else {
						//更新要素だったら
						if ( existingEntry_lastElement.next('input:text').attr('class').indexOf('update') != -1 ) {
							existingEntry_lastElement
											.next().next().next().next()
											.after( '<input type="text" id=org_update_element' 
														+ count 
														+ ' class="org_update_element" name="org_insert_keywords[' 
														+ count 
														+ ']" value="' 
														+ keyword 
														+ '" style="display:none">')
											.after( '<input type="text" id=org_update_element' 
														+ count 
														+ ' class="org_update_element" name="org_insert_targets[' 
														+ count 
														+ ']" value="' 
														+ target 
														+ '" style="display:none">')
											.after( '<input type="text" id="insert_element' 
														+ count 
														+ '" class="update_element" name="insert_keywords[' 
														+ count 
														+ ']" value="' 
														+ keyword 
														+ '" style="display:none">')
											.after( '<input type="text" id="insert_element' 
														+ count 
														+ '" class="update_element" name="insert_targets[' 
														+ count 
														+ ']" value="' 
														+ target 
														+ '" style="display:none">')
											.after( newHiddenElement )
											.after( newElement );
						} else if ( existingEntry_lastElement.next('input:text').attr('class').indexOf('delete') != -1 ){
							//削除要素だったら
							console.log( '最後尾エントリの下には削除要素があります');
							existingEntry_lastElement
											.next().next()
											.after( '<input type="text" id=org_update_element' 
														+ count 
														+ ' class="org_update_element" name="org_insert_keywords[' 
														+ count 
														+ ']" value="' 
														+ keyword 
														+ '" style="display:none">')
											.after( '<input type="text" id=org_update_element' 
														+ count 
														+ ' class="org_update_element" name="org_insert_targets[' 
														+ count 
														+ ']" value="' 
														+ target 
														+ '" style="display:none">')
											.after( '<input type="text" id="insert_element' 
														+ count 
														+ '" class="update_element" name="insert_keywords[' 
														+ count 
														+ ']" value="' 
														+ keyword 
														+ '" style="display:none">')
											.after( '<input type="text" id="insert_element' 
														+ count 
														+ '" class="update_element" name="insert_targets[' 
														+ count 
														+ ']" value="' 
														+ target 
														+ '" style="display:none">')
											.after( newHiddenElement )
											.after( newElement );
						}
					}

				} else {
					//wixfile_tabbox部を複製
					var existingWixfile_tabbox_last = $('.wixfile_tabbox:last');
					var newTabboxId = parseInt(existingWixfile_tabbox_last
											.attr('id')
											.substr('wixfile_tab'.length) )
											+ 1;
					var newWixfile_tabbox = existingWixfile_tabbox_last
															.clone(true)
															.attr({
																'id': 'wixfile_tab' + newTabboxId,
															});
					newWixfile_tabbox
								.children('table')
									.attr('id', 'wixfile_table' + + newTabboxId);

					//wixfileタブ部を複製
					var existingWixfile_tab_last;
					var newWixfile_tab;
					$.each($('.tabbox'), function(i, el) {
						if ( $(this).css('display') == 'block' ) {
							existingWixfile_tab_last = $(this).find('.wixfile .wixfile_tab li:last');
							newWixfile_tab = existingWixfile_tab_last.clone(true);
							$('#wixfile_tab li').removeClass('selected active');

							newWixfile_tab
									.find('a')
										.attr('href', '#wixfile_tab' + newTabboxId)
										.text('タブ' + newTabboxId);
							newWixfile_tab.addClass('active');

							//既存エントリを削除
							$.each(newWixfile_tabbox.find('table tbody tr'), function(index, el) {
								$(this).remove();
							});
							$.each(newWixfile_tabbox.find('table tbody input'), function(index, el) {
								$(this).remove();
							});

							//新エントリ挿入
							var count = 0;
							$.each($('.update_element'), function(){ 
								if ( $(this).attr('name').indexOf('insert') != -1 ) 
									count++;
							});
							count = count / 2; //keywordとtarget両方換算してしまっている

							newWixfile_tabbox
								.find('table tbody')
								.append( newElement )
								.append( newHiddenElement );
							newHiddenElement
								.after( '<input type="text" id=org_update_element' 
											+ count 
											+ ' class="org_update_element" name="org_insert_keywords[' 
											+ count 
											+ ']" value="' 
											+ keyword 
											+ '" style="display:none">')
								.after( '<input type="text" id=org_update_element' 
											+ count 
											+ ' class="org_update_element" name="org_insert_targets[' 
											+ count 
											+ ']" value="' 
											+ target 
											+ '" style="display:none">')
								.after( '<input type="text" id="insert_element' 
											+ count 
											+ '" class="update_element" name="insert_targets[' 
											+ count 
											+ ']" value="' 
											+ target 
											+ '" style="display:none">')
								.after( '<input type="text" id="insert_element' 
											+ count 
											+ '" class="update_element" name="insert_keywords[' 
											+ count 
											+ ']" value="' 
											+ keyword 
											+ '" style="display:none">');


							//tabboxとtabを配置
							existingWixfile_tabbox_last.after( newWixfile_tabbox );
							existingWixfile_tab_last.after( newWixfile_tab );

							//新tabbox表示
							$('.wixfile_tabbox').hide();
							$('#wixfile_tab' + newTabboxId).fadeIn();


							return false;
						}
					});
				}

			} else {
				console.log('subjectTableのTRエントリは満杯だよねそりゃ。');
				//DB挿入用の隠し要素4つも挿入
				var count = 0;
				$.each($('.update_element'), function(){ 
					if ( $(this).attr('name').indexOf('insert') != -1 ) 
						count++;
				});
				count = count / 2; //keywordとtarget両方換算してしまっている
				console.log( 'countは' );
				console.log(count);

				tmp_subjectTable.find('input:last')
								.after( '<input type="text" id=org_update_element' 
											+ count 
											+ ' class="org_update_element" name="org_insert_keywords[' 
											+ count 
											+ ']" value="' 
											+ keyword 
											+ '" style="display:none">')
								.after( '<input type="text" id=org_update_element' 
											+ count 
											+ ' class="org_update_element" name="org_insert_targets[' 
											+ count 
											+ ']" value="' 
											+ target 
											+ '" style="display:none">')
								.after( '<input type="text" id="insert_element' 
											+ count 
											+ '" class="update_element" name="insert_keywords[' 
											+ count 
											+ ']" value="' 
											+ keyword 
											+ '" style="display:none">')
								.after( '<input type="text" id="insert_element' 
											+ count 
											+ '" class="update_element" name="insert_targets[' 
											+ count 
											+ ']" value="' 
											+ target 
											+ '" style="display:none">')
								.after( newHiddenElement )
								.after( newElement );
			}

			//エントリ挿入フォームを空にする
			$.each(entryElement, function(index, el) {
				if ( index == 0 ) $(this).val('');
				if ( index == 1 ) $(this).val('');
			});
			//"挿入成功"を出力する
			$('#entry_insert_result').text('データ追加成功！');

		} else {
			$('#entry_insert_result').text('両方入力してください');
		}


		$('#newKeyword_form, #newTarget_form').click(function(e){
			$('#entry_insert_result').text('');
		});
	});



	$('.doc_page, .wixfile_target').click(function(event) {
		var url = '';

		if ( $(this).attr('class') == 'doc_page' )
			url = $(this).attr('href');
		else
			url = $(this).find('a').attr('href');
		
		$('#doc_iframe_text').val(url);
	});

	$('#doc_iframe_text').focus(function(event) {
		 $(this).select();
	});



	// $('.wixfile_entry_edit')
	// 	.on({
	// 		'click': function() {
	// 			var id_number = $(this).attr('id').substr($(this).attr('class').length);
	// 			$.each($(this).nextAll('td'), function(index, element) {
	// 				if ( index == 0 ) return true; 

	// 				var now_class = $(this).attr('class') + '_now';  
	// 				var now_id = $(this).attr('class') + '_now' + id_number;

	// 				if ( index == 1 ) {
	// 					var text = ( $(this).text() != "" ) ? $(this).text() : $(this).val();//kokotigau
	// 					$(element)
	// 						.children()
	// 						.replaceWith('<input type="text" id="' + now_id + '" class="' + now_class + '" value="' + text + '">');
	// 				} else if ( index == 2 ) {
	// 					var href = $(element).children().children().attr('href');
	// 					$(element)
	// 						.children()
	// 						.replaceWith('<input type="text" id="' + now_id + '" class="' + now_class + '" value="' + href + '">');
	// 				}


	// 				$('.wixfile_keyword_now, .wixfile_target_now')
	// 					.on({
	// 						/* focus+click か mouseupか。とりあえずmouseupだけでいく*/

	// 						// 'focus': function() {
	// 						// 	$(this).select();
	// 						// },
	// 						// 'click': function() {
	// 						// 	$(this).select();
	// 						// 	return false;
	// 						// },
	// 						'mouseup': function() {
	// 							$(this).select();
	// 						}
	// 					});
	// 			});

	// 			// $(this).off();
	// 		},

	// 	});


	// $(document).on('click', '#wixfile_settings_form #wixfile_contents input[type="button"]', function(e) {
	// 	var parentElementName = '#wixfile_settings_form #wixfile_contents ';
	// 	var count = 0;

	// 	//フォームを削除
	// 	var idx = $(e.target).parent().index();
	// 	$('#wixfile_settings_form #wixfile_contents tr').eq(idx).remove();

	// 	// フォームがひとつになるなら、削除ボタンは不要なので消去
	// 	if ($(parentElementName + 'tr').length == 2) $(parentElementName + 'input[type="button"]').remove();

	// 	// フォームの番号を振り直す
	// 	$(parentElementName + 'tr').each(function(index) {
	// 		if ( index != 0 ) {
	// 			$(this).children('th').eq(0).children('input:text').attr('name', 'keywords[' + count + ']');
	// 			$(this).children('th').eq(1).children('input:text').attr('name', 'targets[' + count + ']');
	// 			count++;
	// 		}
	// 	});

	// });


	
	//WIX Manual Decideの設定をAjaxで更新
	//今使ってない(2015/10/14)
  //   $('#manual_decide input[type=checkbox]').click(function(){
  //   	var manual_decideFlag = $('.decide_management input[type=checkbox]').prop("checked");

  //   	var data = {
		// 	'action': 'wix_manual_decide',
		// 	'manual_decideFlag' : manual_decideFlag
		// };

		// $.ajax({
		// 	async: true,
		// 	dataType: "json",
		// 	type: "POST",
		// 	url: ajaxurl,
		// 	data: data,

		// 	success: function(json) {
		// 		console.log(json['data']);
		// 	},

		// 	error: function(xhr, textStatus, errorThrown){
		// 		alert('wixSetting.js Error');
		// 	}
		// });

  //   });
/***************************************************************************************************************************************************/
    //WIXFileのエントリ候補をwix_document_similarityテーブルから推薦
    $('.wix_similarity_entry').click(function(e) {
    	var doc_id = $(this).attr('id');
    	var data = {
			'action': 'wix_similarity_entry_recommend',
			'doc_id' : doc_id
		};

		$.ajax({
			async: true,
			dataType: "json",
			type: "POST",
			url: ajaxurl,
			data: data,

			success: function(json) {
// console.log(json['entrys']);
				var test = '<tr><th>Keyword in Doc</th><th>Document Title</th></tr>';
				if ( json['entrys'].length != 0 ) {
					$.each(json['entrys'], function(keyword, obj) {
						// console.log(obj);

						var keyword_flag = false;
						$.each(obj, function(index, el) {
							var title = '<a id=' + el.ID 
								+ ' class="wix_similarity_entry" target="target_page" href="' 
								+ el.guid 
								+ '">'
								+ el.post_title 
								+ '</a>';
							if ( keyword_flag == false ) {
								test = test + '<tr><td>' + keyword + '</td>' + '<td>' + title + '</td></tr>';
								keyword_flag = true;
							} else {
								test = test + '<tr><td>' + '' + '</td>' + '<td>' + title + '</td></tr>';
							}
						});
					});
				} else {
					alert('推薦出来る候補エントリがありません');
				}
				$('#similarity_entrys').empty();
				$('#similarity_entrys').append(test);
			},

			error: function(xhr, textStatus, errorThrown){
				alert('wixSetting.js Error');
			}
		});
    });




});