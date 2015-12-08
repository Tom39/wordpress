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

	$('.third_doc_tabbox:first').show();
	$('#third_doc_tab li:first').addClass('active');
	$('#third_doc_tab li').click(function() {
		$('#third_doc_tab li').removeClass('active');
		$(this).addClass('active');
		$('.third_doc_tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();

		return false;
	});

	$('.recommend_entrys_tabbox:first').show();
	$('#recommend_entrys_tab li:first').addClass('active');
	$('#recommend_entrys_tab li').click(function() {
		$('#recommend_entrys_tab li').removeClass('active');
		$(this).addClass('active');
		$('.recommend_entrys_tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();
		return false;
	});

	//保存をしていない時、ページ離脱に関する警告を出すイベント
	$(window).on('beforeunload', function() {
		if ( $('.update_element').length != 0 || $('.delete_element').length != 0 ) {
			return '保存が完了していません。このまま移動しますか？';
		}
	});
	$('input[type=submit]').on('click', function(event) {
		$(window).off('beforeunload');
	});
/***********************************************************************************************************/

			//Setting Form
	
	/***********************************************************************************************************/
	$('#add_patternFile').on('click', function(event) {
		event.preventDefault();
		// console.log( $('pattern_filename').children('li').children().eq(0).val() );
		var new_form = $('#pattern_filename li:last')
											.clone(true);
		var form_num = $('#pattern_filename li').length;

		$.each(new_form.children(), function(index, el) {
			// console.log($(this).val());
			if ( index == 0 ) 
				$(this).attr('name', 'pattern['+form_num+']');
			else if ( index == 1 )
				$(this).attr('name', 'filename['+form_num+']');

			$(this).val('');
		});
		new_form.insertAfter('#pattern_filename li:last');
	});

	$('input[name=wixfile_autocreate]').on('change', function(event) {
		event.preventDefault();
		
		if ( $(this).attr('id') == 'wixfile_autocreate_on' ) {
			$('#wixfile_autocreate_setting').show('slow/400/fast', function() {});

		} else {
			$('#wixfile_autocreate_setting').hide();
			$.each($('#wixfile_autocreate_setting').find('input'), function(index, el) {
				$(this).prop('checked', false);
			});
		}

	});

	$('#other_option_settings').find('input').on('change', function(event) {
		event.preventDefault();
		if ( $(this).attr('id') == 'morphological_analysis_yahoo' ) {
			var input = $("<input />", {
				type: 'text',
				name: 'yahoo_id',
				id: 'yahoo_id',
				placeholder: 'Yahoo Develper ID'
			}).css({
				'display': 'none'
			});
			$(this).next().after(input);

			//もしIDがあるならtextに挿入
			$('#yahoo_id').ready(function() {
				var data = {
					'action': 'wix_contents_option',
					'contents_option' : 'yahoo_id'
				};
				$.ajax({
					async: true,
					dataType: "json",
					type: "POST",
					url: ajaxurl,
					data: data,

					success: function(json) {
						console.log(json['contents_option']);
						if ( json['contents_option'].length != 0 ) {
							$('#yahoo_id').val(json['contents_option']);
						}
						$('#yahoo_id').show('slow/400/fast', function() {});
					},

					error: function(xhr, textStatus, errorThrown){
						alert('wixSetting.js Error');
					}
				});
			});
			

		} else if ( $(this).attr('id') == 'morphological_analysis_mecab' ) {
			if ( $('#morphological_analysis_mecab').prevAll('input:text').length != 0 ) {
				$.each($('#morphological_analysis_mecab').prevAll('input:text'), function(index, el) {
					$(this).remove();
				});
			}

		} else if ( $(this).attr('id') == 'recommend_support_docsim' ) {
			if ( $('#recommend_support_google').nextAll('input').length != 0 ) {
				$.each($('#recommend_support_google').nextAll('input:text'), function(index, el) {
					$(this).remove();
				});
			}

		} else if ( $(this).attr('id') == 'recommend_support_google' ) {
			var input = $("<input />", {
				type: 'text',
				name: 'google_api_key',
				id: 'google_api_key',
				placeholder: 'Google API Key'
			}).css({
				'display': 'none'
			});
			var input2 = $("<input />", {
				type: 'text',
				name: 'google_cx',
				id: 'google_cx',
				placeholder: 'Custom search engine ID'
			}).css({
				'display': 'none'
			});

			$(this).next()
						.after(input2)
						.after(input);

			//もしIDがあるならtextに挿入
			$('#google_api_key').ready(function() {
				var data = {
					'action': 'wix_contents_option',
					'contents_option' : 'google_api_key'
				};
				$.ajax({
					async: true,
					dataType: "json",
					type: "POST",
					url: ajaxurl,
					data: data,

					success: function(json) {
						console.log(json['contents_option']);
						if ( json['contents_option'].length != 0 ) {
							$('#google_api_key').val(json['contents_option']);
						}
						$('#google_api_key').show('slow/400/fast', function() {});
					},

					error: function(xhr, textStatus, errorThrown){
						alert('wixSetting.js Error');
					}
				});
			});

			$('#google_cx').ready(function() {
				var data = {
					'action': 'wix_contents_option',
					'contents_option' : 'google_cx'
				};
				$.ajax({
					async: true,
					dataType: "json",
					type: "POST",
					url: ajaxurl,
					data: data,

					success: function(json) {
						console.log(json['contents_option']);
						if ( json['contents_option'].length != 0 ) {
							$('#google_cx').val(json['contents_option']);
						}
						$('#google_cx').show('slow/400/fast', function() {});
					},

					error: function(xhr, textStatus, errorThrown){
						alert('wixSetting.js Error');
					}
				});
			});
		}
	});

	$('#morphological_analysis_reset, #recommend_support_reset').on('click', function(event) {
		event.preventDefault();
		$.each($(this).prevAll('input:radio'), function(index, el) {
			$(this).prop('checked', false);
		});
		$.each($(this).prevAll('input:text'), function(index, el) {
			$(this).remove();
		});
	});

/***********************************************************************************************************/

			//Tab 1 & 2
	
	/***********************************************************************************************************/
	//WIXファイル編集フォームでのEnter禁止
	$('.wixfile_keyword_edit, .wixfile_target_edit').keypress(function(e){
		if( (e.which == 13) || (e.keyCode == 13) )
			return false;
	});

	$('.wixfile_keyword_edit input:text, .wixfile_target_edit input:text').focus(function(event) {
		 $(this).select();
	});


	//WIXFileコンテンツの"編集"イベント
	$('.wixfile_entry_edit').click(function(event) {
		if ( $(this).parent('tr').attr('id') == 'wixfile_ex_entry0' ) return false;

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
						//表示
						$(this)
							.parent()
							.hide()
							.prev()
							.show();
					});
			});
	});

	//WIXFileコンテンツの"削除"イベント
	$('.wixfile_entry_delete').click(function(event) {
		if ( $(this).parent('tr').attr('id') == 'wixfile_ex_entry0' ) return false;
		
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
				
				//更新要素だったら、DB削除用要素を追加
				if ( $(event.target).parents('tr').next().next().attr('name').indexOf('update') != -1 ) {
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
				} else {
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
				}

				//隠しエントリ & 表示エントリ削除
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

										//削除するキーワードとターゲット名
										$.each(entryTr.children(), function(index, el) {
											if ( index == 3 ) 
												delete_keyword = $(el).text();
											if ( index == 4 )
												delete_target = $(el).find('a').attr('href');
										});

										//更新・挿入用の要素が存在するかチェック
										if ( entryTr.next().next('input').length != 0 ) {
											if ( entryTr.next().next().attr('name').indexOf('update') != -1 ) {

												//更新をして、やっぱ削除するって時は、更新用に作った要素を削除
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
											} else {
												console.log('挿入用要素を一括削除します');
												//更新をして、やっぱ削除するって時は、更新用に作った要素を削除
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
											}
										}

										//隠しエントリ&エントリ削除
										entryTr
											.next()
											.remove();
										entryTr
											.remove();

									}
								});
								
								$('.wixfile_entry_allcheck').prop('checked', false);	
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

										//削除するキーワードとターゲット名
										$.each(entryTr.children(), function(index, el) {
											if ( index == 3 ) 
												delete_keyword = $(el).text();
											if ( index == 4 )
												delete_target = $(el).find('a').attr('href');
										});
									
										//更新・挿入用の要素が存在するかチェック
										if ( entryTr.next().next('input').length != 0 ) {
											if ( entryTr.next().next().attr('name').indexOf('update') != -1 ) {
												//更新をして、やっぱ削除するって時は、更新用に作った要素を削除
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
											} else {
												//更新をして、やっぱ削除するって時は、更新用に作った要素を削除
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
											}
										}


										//隠しエントリ&エントリ削除
										entryTr
											.next()
											.remove();
										entryTr
											.remove();

									}
								});
								
								$('.wixfile_entry_allcheck').prop('checked', false);	
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
							}).css({
								'font-size': '6pt',
								// property2: 'value'
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
							}).css({
								'font-size': '6pt',
								// property2: 'value2'
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

				$('.pWindow tbody').ready(function() {
					if ( $('.pWindow').position().top < 35 ) {
						console.log('きた。');
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
						var former_keyword, former_target;
						var update_keyword, update_target;
						var org_keyword, org_target;
						var entryId;

						$.each($('.wixfile_tabbox'), function(i, el) {
							if ( $(this).css('display') == 'block' ) { //今見えてるWIXFileコンテンツ
								var wixContents = $(this);

								//モーダルテーブルの各エントリ
								$.each($('.edit_entryTr'), function(index, el) {
									update_keyword = $(this).find('.editKeyword').val();
									update_target = $(this).find('.editTarget').val();

									former_keyword = $(this).find('.former_editKeyword').val();
									former_target = $(this).find('.former_editTarget').val();
									entryId = $(this).find('.former_editTarget').attr('alt');

									//今見えてるWIXFileコンテンツにおける編集該当エントリ
									var subectElement = $(wixContents).find('#' + entryId);


									//キーワードかターゲットに変更があったら更新用要素追加
									if ( (update_keyword != former_keyword) || (update_target != former_target) ) {
										var count;
										$.each(subectElement.find('.update_element'), function(){ 
											if ( $(this).attr('name').indexOf('update_keywords') != -1 ) 
												count++;
										});

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
											$.each(subectElement.nextAll(), function(index, el) {
												if ( index == 3 ) org_target = $(this).val();
												else if ( index == 4 ) org_keyword = $(this).val();
												else if ( index == 5) return false;									
											});

											if ( subectElement.next().next().attr('name').indexOf('update') != -1 ) {
												count = subectElement
																	.next()
																	.next()
																	.attr('id')
																	.substr( 'update_element'.length );
												flag = false;
											} else if ( subectElement.next().next().attr('name').indexOf('insert') != -1 ) {
												count = subectElement
																	.next()
																	.next()
																	.attr('id')
																	.substr( 'insert_element'.length );
												flag = true;
											}
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
											if ( flag == false ) {
												$.each(subectElement.nextAll('input:text'), function(index, el) {
													if ( $(this).attr('class') == 'update_element' ) $(this).remove();
													else return false;
												});
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
											
											} else {
												$.each(subectElement.nextAll('input:text'), function(index, el) {
													if ( $(this).attr('class') == 'update_element') $(this).remove();
													else return false;
												});

												subectElement
													.next()
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

				$('.pWindow tbody').ready(function() {
					if ( $('.pWindow').position().top < 35 ) {
						console.log('きた。');
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
			}
		} 

	});


	//エントリ追加
	$('#add_wixfile_entry, #second_add_wixfile_entry').click(function(event) {
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
			var subjectTable, tmp_subjectTable, tab;
			$.each($('.tabbox'), function(i, el) {
				if ( $(this).css('display') == 'block' ) {
					tab = $(this).attr('id');
					if ( tab == 'tab1' ) 
						subjectTable = $(this).find('.wixfile_tabbox:last tbody');
					else 
						subjectTable = $(this).find('.second_wixfile_tabbox:last tbody');

					return false;
				}
			});
			tmp_subjectTable = subjectTable;

			if ( subjectTable.find('tr').size() == 0 ) {
				if ( tab == 'tab1' ) {
					// if ( subjectTable.parents('.wixfile_tabbox').prev().find('table tbody tr').size() < 40 ) {
						console.log('subjectTableを最後から2番目に切り替えます');
						subjectTable = tmp_subjectTable.parents('.wixfile_tabbox').prev().find('table tbody');
					// }
				} else {
					// if ( subjectTable.parents('.second_wixfile_tabbox').prev().find('table tbody tr').size() < 40 ) {
						console.log('subjectTableを最後から2番目に切り替えます');
						subjectTable = tmp_subjectTable.parents('.second_wixfile_tabbox').prev().find('table tbody');
					// }
				}
			}
			console.log('subjectTableは↓');
			console.log(subjectTable);


			//現在隠してあるエントリ要素取得
			var existingEntry_lastElement = subjectTable.find('tr:last');

			//現在の最後のエントリ要素取得
			var existingEntry_lastElement = subjectTable.find('tr:last');
			console.log( '最後尾のエントリは' );
			console.log( existingEntry_lastElement.attr('id') );

			if ( existingEntry_lastElement.attr('id') == 'wixfile_ex_entry_hidden0' ) {
				//挿入エントリに使用するNewIDを作成
				var newId = 0;
				var newClass = 'wixfile_even';		

			} else {
				//挿入エントリに使用するNewIDを作成
				var newId = parseInt(existingEntry_lastElement
										.attr('id')
										.substr('wixfile_entry_hidden'.length) )
										+ 1;
				var newClass = '';
				if ( newId % 2 == 0 ) newClass = 'wixfile_even';
				else newClass = 'wixfile_odd';
			}

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
					console.log('40です。');
					//wixfile_tabbox部を複製
					var existingWixfile_tabbox_last;
					var newTabboxId;
					var newWixfile_tabbox;

					if ( tab == 'tab1' ) {
						existingWixfile_tabbox_last = $('.wixfile_tabbox:last');
						newTabboxId = parseInt(existingWixfile_tabbox_last
													.attr('id')
													.substr('wixfile_tab'.length) )
													+ 1;
						newWixfile_tabbox = existingWixfile_tabbox_last
															.clone(true)
															.attr({
																'id': 'wixfile_tab' + newTabboxId,
															});
						newWixfile_tabbox
								.children('table')
									.attr('id', 'wixfile_table' + newTabboxId);

					} else {
						existingWixfile_tabbox_last = $('.second_wixfile_tabbox:last');
						newTabboxId = parseInt(existingWixfile_tabbox_last
													.attr('id')
													.substr('second_wixfile_tab'.length) )
													+ 1;
						newWixfile_tabbox = existingWixfile_tabbox_last
															.clone(true)
															.attr({
																'id': 'second_wixfile_tab' + newTabboxId,
															});
						newWixfile_tabbox
								.children('table')
									.attr('id', 'second_wixfile_table' + newTabboxId);
					}

					//wixfileタブ部を複製
					var existingWixfile_tab_last;
					var newWixfile_tab;
					$.each($('.tabbox'), function(i, el) {
						if ( $(this).css('display') == 'block' ) {
							existingWixfile_tab_last = $(this).find('.wixfile .wixfile_tab li:last');
							newWixfile_tab = existingWixfile_tab_last.clone(true);
							if ( tab == 'tab1' ) {
								$('#wixfile_tab li').removeClass('selected active');
								newWixfile_tab
									.find('a')
										.attr('href', '#wixfile_tab' + newTabboxId)
										.text('タブ' + newTabboxId);
							} else {
								$('#second_wixfile_tab li').removeClass('selected active');
								newWixfile_tab
									.find('a')
										.attr('href', '#second_wixfile_tab' + newTabboxId)
										.text('タブ' + newTabboxId);
							}
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
							if ( tab == 'tab1' ) {
								$('.wixfile_tabbox').hide();
								$('#wixfile_tab' + newTabboxId).fadeIn();
							} else {
								$('.second_wixfile_tabbox').hide();
								$('#second_wixfile_tab' + newTabboxId).fadeIn();
							}
							


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
				console.log(tmp_subjectTable);
				if ( tmp_subjectTable.find('input').length == 0 ) {
					console.log('inputが0でした');
					tmp_subjectTable
									.append( newElement )
									.append( newHiddenElement )
									.append( '<input type="text" id="insert_element' 
												+ count 
												+ '" class="update_element" name="insert_targets[' 
												+ count 
												+ ']" value="' 
												+ target 
												+ '" style="display:none">')
									.append( '<input type="text" id="insert_element' 
												+ count 
												+ '" class="update_element" name="insert_keywords[' 
												+ count 
												+ ']" value="' 
												+ keyword 
												+ '" style="display:none">')
									.append( '<input type="text" id=org_update_element' 
												+ count 
												+ ' class="org_update_element" name="org_insert_targets[' 
												+ count 
												+ ']" value="' 
												+ target 
												+ '" style="display:none">')
									.append( '<input type="text" id=org_update_element' 
												+ count 
												+ ' class="org_update_element" name="org_insert_keywords[' 
												+ count 
												+ ']" value="' 
												+ keyword 
												+ '" style="display:none">')

				} else {
					console.log('inputが0より大きいです');
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
			}

			if ( existingEntry_lastElement.attr('id') == 'wixfile_ex_entry_hidden0' ) {
				existingEntry_lastElement.prev().remove();
				existingEntry_lastElement.remove();
			}

			//エントリ挿入フォームを空にする
			$.each(entryElement, function(index, el) {
				if ( index == 0 ) $(this).val('');
				if ( index == 1 ) $(this).val('');
			});
			//"挿入成功"を出力する
			$.each($('.tabbox'), function(i, el) {
				if ( $(this).css('display') == 'block' ) {
					if ( $(this).attr('id') == 'tab1' ) 
						$('#entry_insert_result').text('データ追加成功！');
					else 
						$('#second_entry_insert_result').text('データ追加成功！');

					return false;
				}
			});

		} else {
			$.each($('.tabbox'), function(i, el) {
				if ( $(this).css('display') == 'block' ) {
					if ( $(this).attr('id') == 'tab1' ) 
						$('#entry_insert_result').text('両方入力してください');
					else 
						$('#second_entry_insert_result').text('両方入力してください');

					return false;
				}
			});
		}


		$('#newKeyword_form, #newTarget_form').click(function(e){
			$('#entry_insert_result').text('');
		});
	});

	//リンクがクリックされたら
	$('.doc_page, .wixfile_target').click(function(event) {
		if ( $('.second_wixfile_tabbox').is(':visible') == true ) {
			var url = '';
			if ( $(this).attr('class') == 'doc_page' )
				url = $(this).attr('href');
			else
				url = $(this).find('a').attr('href');

			//tab2のインラインフレーム直下のtextboxにURL挿入
			// $('#doc_iframe_text').val(url);
			$('#second_newTarget_form').val(url);
		}
	});
	$('.doc_page').click(function(event) {
		$(this).parents('#doc_list').find('td').css('background-color', '');	
		$(this).parent().css('background-color', 'yellow');
	});
	$('#second_newTarget_form').keypress(function(e){
		if( (e.which == 13) || (e.keyCode == 13) ) {
			var url = $('#second_newTarget_form').val();
			if ( url != '' ) {
				$('#doc_iframe')[0].contentDocument.location.replace(url);
			}
			return false;
		}
	});

	//tab2のインラインフレーム部分
	// $('#doc_iframe_text').focus(function(event) {
	// 	 $(this).select();
	// });
	// $('#doc_iframe_text').keypress(function(e){
	// 	if( (e.which == 13) || (e.keyCode == 13) ) {
	// 		var url = $('#doc_iframe_text').val();
	// 		$('#doc_iframe')[0].contentDocument.location.replace(url);

	// 		return false;
	// 	}
	// });

/***********************************************************************************************************/

			//Tab 3 
	
	/***********************************************************************************************************/

	//tab3のドキュメントリンクがクリックされたら
	var targetArray = new Object();
	$('.third_doc_page').click(function(event) {
		var subjectDoc = $(this);
		var doc_id = $(this).attr('id');

		subjectDoc.parents('#third_doc_list').find('td').css('background-color', '');
		subjectDoc.parent().css('background-color', 'yellow');

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
				if ( json['entrys'].length != 0 ) {
					//table群を一旦除去する
					if ( $('.recommend_entrys_tabbox').children().length != 0 ) {
						$('.recommend_entrys_tabbox').empty();
						$('#recommend_targets_div').empty();
					}

					var count = 0;
					var roop = 1;
					$.each(json['entrys'], function(word_type, obj) {
						var table = $("<TABLE />",{
							id: word_type + '_table'
						});
						var tr = $("<TR />", {
							id: word_type + '_tr'
						}).appendTo(table);
						var td = $("<TD />", {
							id: word_type + '_td'
						}).appendTo(tr);

						if ( word_type == 'site_freq_words' ) {
							$.each(obj, function(index, el) {
								var tr2 = $("<TR />", {
									class: word_type + '_inner_tr'
								}).appendTo(td);
								var td2 = $("<TD />", {
									id: word_type + '_inner_td' + count,
									class: word_type + '_inner_td',
									text: el['keyword']
								}).appendTo(tr2);

								count++;
							});
						} else if ( word_type == 'candidate_targets' ) {
							$.each(obj, function(index, el) {
								var tr2 = $("<TR />", {
									class: word_type + '_inner_tr'
								}).appendTo(td);
								var td2 = $("<TD />", {
									id: word_type + '_inner_td' + index + '-1',
									class: word_type + '_inner_td',
								}).appendTo(tr2);
								var a = $("<a />", {
									id: el['ID'],
									class: word_type + '_inner_a',
									href: el['guid'],
									text: el['post_title']
								}).appendTo(td2);

								var td3 = $("<TD />", {
									id: word_type + '_inner_td' + index + '-2',
									class: word_type + '_inner_td2',
								}).appendTo(tr2);
								var checkbox = $('<input type="checkbox" />', {
								}).attr({
									id: 'check_' + el['ID'],
									class: word_type + '_inner_checkbox',
								}).css({'vertical-align':'middle'}).appendTo(td3);
							});
							$('#recommend_targets_div').append(table);
						} else {
							$.each(obj, function(keyword, val) {
								var tr2 = $("<TR />", {
									class: word_type + '_inner_tr'
								}).appendTo(td);
								var td2 = $("<TD />", {
									id: word_type + '_inner_td' + count,
									class: word_type + '_inner_td',
									text: keyword
								}).appendTo(tr2);

								count++;
							});
						}
						
						$('#recommend_entrys_tab' + roop).append(table);
						roop++;
					});

					//対象ドキュメントが切り替わってもtargetArrayにあれば単語をBlueに
					$('#recommend_keywords').ready(function(){
						if ( doc_id in targetArray ) {
							$.each(targetArray[doc_id], function(keyword, obj) {
								$.each($('.recommend_entrys_tabbox').find('td'), function(index, el) {
									if ( $(el).text() == keyword ) {
										$(el).css('color', 'Blue');
									}
								});

							});
						}
					});

					//ターゲット候補のaタグがクリックされたらインラインフレームで表示
					$('.candidate_targets_inner_a').on('click', function(event) {
						event.preventDefault();
						var url = $(this).attr('href');
						$('#third_doc_iframe')[0].contentDocument.location.replace(url);
					});

					//"単語"に関するマウスイベント
					//単語が選択されてなかったらalert
					$('.candidate_targets_inner_checkbox').click(function(event) {
						var checkbox = $(this);
						$.each($('.recommend_entrys_tabbox'), function(index, el) {
							if ( $(this).css('display') == 'block' ) {
								var subjectTrs = $(this).find('tr');
								var flag = false;

								$.each(subjectTrs, function(index, el) {
									if ( index != 0 ) {
										if ( $(this).children('td').css('background-color') == 'rgb(0, 255, 255)' )
											flag = true;
									}

									if ( flag == false ) {
										alert('単語を選択してください');
										return false;
									}
								});

								checkbox.prop('checked', false);
							}
						});
					});



					$('.page_freq_words_inner_td, .page_freq_words_in_site_inner_td, .feature_words_inner_td, .site_freq_words_inner_td').on({
						'mouseover': function() {
							event.preventDefault();
							$(this).css('opacity', '0.2').animate({'opacity': '1'}, 'slow');
						},
						'click': function() {
							event.preventDefault();

							$(this)
								.parent()
								.siblings('tr')
								.find('td')
								.css('background-color', '');
							$(this)
								.css('background-color', 'Aqua');

							var keyword_td = $(this);
							var keyword = $(this).text();
							// var word_type = $(this).attr('class');

							//一度チェックボックス全部外し、targetArrayにあるならチェックつける
							$('.candidate_targets_inner_checkbox').prop('checked', false);
							if ( doc_id in targetArray ) {
								$.each(targetArray[doc_id], function(key, elm) {
									if ( keyword == key ) {
										$.each(elm, function(i, id) {
											$('#check_'+id).prop('checked', true);
										});
									}
								});
							}

							//チェックボックスのクリックイベント
							$('.candidate_targets_inner_checkbox').off();
							$('.candidate_targets_inner_checkbox').click(function(event) {
								if ( $(this).prop('checked') ) {
									var id = $(this).parent().prev().children().attr('id');

									if ( doc_id in targetArray ) {
										var tmpArray = targetArray[doc_id];
										if ( keyword in tmpArray ) {
											var tmp = tmpArray[keyword];
											tmp.push(id);
											tmpArray[keyword] = tmp;
										} else {
											tmpArray[keyword] = [id];
										}
										targetArray[doc_id] = tmpArray;

									} else {
										var tmpArray = new Object();
										tmpArray[keyword] = [id];
										targetArray[doc_id] = tmpArray;
									}

									//targetArrayにあるキーワードにはずっと色づけしとく
									keyword_td.css('color', 'Blue');
									$.each(keyword_td.parents('.recommend_entrys_tabbox').siblings('.recommend_entrys_tabbox').find('td'), function(index, el) {
										if ( $(this).text() == keyword ) {
											$(this).css('color', 'Blue');
										}
									});

									subjectDoc.css('color', 'Red');

								} else {
									var checkbox = $(this);
									$.each(targetArray[doc_id][keyword], function(index, id) {
										if ( id == checkbox.parent().prev().children().attr('id') ) {
											targetArray[doc_id][keyword].splice(index, 1);
										}
									});
									if ( targetArray[doc_id][keyword].length == 0 ) {
										delete targetArray[doc_id][keyword];
										keyword_td.css('color', 'Black');
										$.each(keyword_td.parents('.recommend_entrys_tabbox').siblings('.recommend_entrys_tabbox').find('td'), function(index, el) {
											if ( $(this).text() == keyword ) {
												$(this).css('color', 'Black');
											}
										});
										subjectDoc.css('color', '');

										if ( targetArray[doc_id].length == 0 ) {
											delete targetArray[doc_id];
										}
									}
								}

								console.log(targetArray);
							});

							//クリックした単語がWIXファイルのキーワードにあったらそれを提示する
							var data = {
								'action': 'wix_exisitng_entry_presentation',
								'keyword' : keyword
							};
							$.ajax({
								async: true,
								dataType: "json",
								type: "POST",
								url: ajaxurl,
								data: data,

								success: function(json) {
									$('#existing_wixfile_entrys').empty();

									var table = $("<TABLE />",{
										id: 'exisitng_entry_table'
									}).css({
										'width': '100%',
									});

									if ( json['entrys'].length != 0 ) {
										var th = $("<TH />", {
											class: 'exisitng_entry_th',
											text: keyword + ' の既存リンク先URL情報'
										}).css({
											'width': '100%',
										}).appendTo(table);
										$.each(json['entrys'], function(index, el) {
											var tr = $("<TR />", {
												id: 'exisitng_entry_tr' + index,
												class: 'exisitng_entry_tr'
											}).css({
												'width': '100%',
												'text-align': 'center',
											}).appendTo(table);

											var td = $("<TD />", {
												id: 'exisitng_entry_td' + index,
												class: 'exisitng_entry_td',
											}).css({
												'width': '100%',
											}).appendTo(tr);

											var a = $("<A />", {
												id: 'exisitng_entry_a' + index,
												class: 'exisitng_entry_a',
												href: el['target'],
												text: el['target']
											}).css({
												'width': '100%',
											}).appendTo(td);
										});

									} else {
										var th = $("<TH />", {
											class: 'exisitng_entry_th',
											text: keyword + ' の既存リンク先URL情報は存在しません。'
										}).css({
											'width': '100%',
										}).appendTo(table);
									}
									$('#existing_wixfile_entrys').append(table);

									$('.exisitng_entry_a').on('click', function(event) {
										event.preventDefault();
										var url = $(this).attr('href');
										$('#third_doc_iframe')[0].contentDocument.location.replace(url);
									});
								},

								error: function(xhr, textStatus, errorThrown){
									alert('wixSetting.js Error');
								}
							});
						},
					});
					

					//タブが切り替わったら"Aqua"を消す
					$('#recommend_entrys_tab').children().click(function(event) {
						$('.page_freq_words_inner_td, .page_freq_words_in_site_inner_td, .feature_words_inner_td, .site_freq_words_inner_td')
								.css('background-color', '');
					});

				} else {
					alert('推薦できる候補がありませんでした。');
				}



			},

			error: function(xhr, textStatus, errorThrown){
				alert('wixSetting.js Error');
			}
		});
	});
	
	$('#add_wixfile').on('click', function(event) {
		event.preventDefault();

		if ( Object.keys(targetArray).length > 0 ) {
			var insertArray = new Object();
			/*
				insertArray: [word: [0: [index:target_doc_id]]]
			*/
			$.each(targetArray, function(doc_id, obj) {
				$.each(obj, function(keyword, element) {

					$.each(element, function(index, target_doc_id) {

						if ( keyword in insertArray ) {

							$.each(insertArray[keyword], function(zero, array) {
								var flag = false;

								$.each(array, function(i, id) {
									if ( id == target_doc_id ) {
										flag = true;
										return false;
									}	
								});
								if ( flag == false ) array.push(target_doc_id);

								insertArray[keyword] = [array];

							});

						} else {

							insertArray[keyword] = [[target_doc_id]];

						}
					});

				});
			});

			//モーダル要素作成
			var content = $("<div />", {
				id: 'insert_popTableDiv'
			});
			var table = $("<TABLE />",{
				class: 'insert_popTable'
			}).appendTo(content);
			var tr = $("<TR />", {
				class: 'insert_popTr'
			}).appendTo(table);
			$("<TH />", {
				text: '単語'
			}).css({'white-space': 'nowrap'}).appendTo(tr);
			$("<TH />", {
				text: 'リンク先URL'
			}).css({'white-space': 'nowrap'}).appendTo(tr);
			$("<TH />", {
				text: ''
			}).css({'white-space': 'nowrap'}).appendTo(tr);

			$.each(insertArray, function(word, array) {
				var tr = $("<TR />",{
					class: 'insert_entryTr'
				}).appendTo(table);
				var td = $("<TD />",{
					class: 'insert_keywordTd',
					text: word,
				}).appendTo(tr);
				var td2 = $("<TD />",{
					class: 'insert_targetsTd'
				}).appendTo(tr);
				var td3= $("<TD />",{
					class: 'insert_checkTd'
				}).appendTo(tr);

				$.each(array, function(zero, ar) {
					$.each(ar, function(index, doc_id) {
						var div = $("<div />", {
							class: 'insert_targetDiv',
						}).css({'white-space': 'nowrap'}).appendTo(td2);
						var a = $("<a />", {
							class: 'insert_targetA',
							href: $('#'+doc_id).attr('href'),
							target: '_blank',
							text: $('#'+doc_id).text()
						}).appendTo(div);

						var div = $("<div />", {
							class: 'insert_checkDiv',
						}).css({'white-space': 'nowrap'}).appendTo(td3);
						var checkbox = $('<input type="checkbox" />', {
						}).attr({
							class: 'insert_targetCheck',
							checked: 'checked'
						}).css({'vertical-align':'middle'}).appendTo(div);
					});
				});
			});

			//モーダル作成
			var pop = new $pop(content, {
				type: 'inline',
				title: 'WIXファイルデータ編集',
				width: 500,
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
					var hiddenDiv = $("<div />", {
						id: 'hiddenDiv'
					}).appendTo(content);
					var form = $("<form />", {
						id: 'insert_entryForm',
						class: 'wixfile_settings_form',
						method: 'post',
					}).appendTo(hiddenDiv);
					$("<input />", {
						type: 'hidden',
						id: 'nonce_wixfile_settings',
						name: 'nonce_wixfile_settings',
						value: '704831fc2b',
					}).appendTo(form);
					$("<input />", {
						type: 'hidden',
						name: '_wp_http_referer',
						value: '/wordpress/wp-admin/admin.php?page=wix-admin-settings',
					}).appendTo(form);


					var count = 0;
					$.each($('.insert_entryTr'), function(index, el) {
						if ( $(this).find('.insert_targetCheck').is(':checked') == true ) {
							var keyword = $(this).children('.insert_keywordTd').text();

							$.each($(this).find('.insert_targetA'), function(index, el) {

								var input = $("<input />", {
									type: 'text',
									class: 'insertKeyword',
									name: 'insert_keywords['+count+']',
									value: keyword,
								}).css({
									'display': 'none'
								}).appendTo(form);

								var input2 = $("<input />", {
									type: 'text',
									class: 'insertTarget',
									name: 'insert_targets['+count+']',
									value: $(this).attr('href'),
								}).css({
									'display': 'none'
								}).appendTo(form);

								count++;
							});
						}
					});
					$('<input />', {
						type: 'submit',
					}).attr({
						name: 'wixfile_settings',
						class: 'insert_entryButton',
						value: "データ更新",
					}).css({
						'display': 'none'
					}).appendTo(form);

					$('.insert_entryButton').ready(function(){
						$('.insert_entryButton').click();
					});
		
				}
			}).appendTo(buttonDiv);


		} else {
			alert('追加したい情報を選択してください');
		}

	});

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