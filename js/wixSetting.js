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
	$('.wixfile_tabbox:first').show();
	$('#wixfile_tab li:first').addClass('active');
	$('#wixfile_tab li').click(function() {
		$('#wixfile_tab li').removeClass('active');
		$(this).addClass('active');
		$('.wixfile_tabbox').hide();
		$($(this).find('a').attr('href')).fadeIn();
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

	//WIXファイルのフォーム追加
	$('#add_wixfile').click(function() {
		var newEntry_num = $('#newWIXFiles').children().length;

		var table = document.createElement('table');
		table.setAttribute('class', 'newEntry');

		//
		var keyword_tr = document.createElement('tr');
		var keyword_label_td = document.createElement('td');
		var keyword_input_td = document.createElement('td');
		var keyword_label = document.createElement('label');
		var keyword_input = document.createElement('input');

		keyword_label.setAttribute('for', 'keyword');
		keyword_label.innerHTML = 'Keyword';
	    keyword_input.setAttribute('type', 'text');
	    keyword_input.setAttribute('name', 'keywords[' + newEntry_num + ']');

		keyword_label_td.appendChild(keyword_label);
		keyword_input_td.appendChild(keyword_input);
		keyword_tr.appendChild(keyword_label_td);
		keyword_tr.appendChild(keyword_input_td);


		//
		var target_tr = document.createElement('tr');
		var target_label_td = document.createElement('td');
		var target_input_td = document.createElement('td');
		var target_label = document.createElement('label');
		var target_input = document.createElement('input');

		target_label.setAttribute('for', 'target');
		target_label.innerHTML = 'Target';
	    target_input.setAttribute('type', 'text');
	    target_input.setAttribute('size', 30);
	    target_input.setAttribute('name', 'targets[' + newEntry_num + ']');

		target_label_td.appendChild(target_label);
		target_input_td.appendChild(target_input);
		target_tr.appendChild(target_label_td);
		target_tr.appendChild(target_input_td);

		//
		var attribute_tr = document.createElement('tr');
		var dummy_td = document.createElement('td');
		var attribute_td = document.createElement('td');
		var attribute_label_firstonly = document.createElement('label');
		var attribute_input_firstonly = document.createElement('input');
		var attribute_label_case = document.createElement('label');
		var attribute_input_case = document.createElement('input');
		var attribute_label_filter = document.createElement('label');
		var attribute_input_filter = document.createElement('input');

		attribute_label_firstonly.setAttribute('for','firstonly' + newEntry_num);
		attribute_label_case.setAttribute('for','case' + newEntry_num);
		attribute_label_filter.setAttribute('for','filter' + newEntry_num);
		attribute_label_firstonly.innerHTML = 'First Match Only ';
		attribute_label_case.innerHTML = 'Case Sensitivity ';
		attribute_label_filter.innerHTML = 'Filter in comments?';

	    attribute_input_firstonly.setAttribute('type', 'checkbox');
	    attribute_input_firstonly.setAttribute('id', 'firstonly' + newEntry_num);
	    attribute_input_firstonly.setAttribute('name', 'firstonly');
	    attribute_input_firstonly.setAttribute('value', '1');
	    attribute_input_case.setAttribute('type', 'checkbox');
	    attribute_input_case.setAttribute('id', 'case' + newEntry_num);
	    attribute_input_case.setAttribute('name', 'case');
	    attribute_input_case.setAttribute('value', '1');
	    attribute_input_filter.setAttribute('type', 'checkbox');
	    attribute_input_filter.setAttribute('id', 'filter' + newEntry_num);
	    attribute_input_filter.setAttribute('name', 'filter');
	    attribute_input_filter.setAttribute('value', '1');


	    attribute_td.appendChild(attribute_input_firstonly);
	    attribute_td.appendChild(attribute_label_firstonly);
	    attribute_td.appendChild(attribute_input_case);
	    attribute_td.appendChild(attribute_label_case);
	    attribute_td.appendChild(attribute_input_filter);
	    attribute_td.appendChild(attribute_label_filter);
	    attribute_tr.appendChild(dummy_td);
	    attribute_tr.appendChild(attribute_td);



		table.appendChild(keyword_tr);
		table.appendChild(target_tr);
		table.appendChild(attribute_tr);


		$('#newWIXFiles').append(table);

	});

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

						//キーワードかターゲットに変更があったら更新用要素追加
						if ( (update_keyword != former_keyword) || (update_target != former_target) ) { 

							var count = ($('.update_element').length) / 2;
							if ( $(this).parent().next('input:text').size() == 0 ) {
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
								org_keyword = $(this)
													.parent()
													.siblings('.org_update_element')
													.eq(1)
													.val();

								org_target = $(this)
													.parent()
													.siblings('.org_update_element')
													.eq(0)
													.val();

								//既に更新用要素が生成されてたら一旦削除して新規で要素作成
								count = $(this)
									.parent()
									.next('input:text')
									.attr('id')
									.substr( 'update_element'.length );
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
								//更新用要素を削除・追加
								$(this)
									.parent()
									.nextAll('input:text')
									.remove('.update_element');
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


			// $('.wixfile_keyword_edit, .wixfile_target_edit')
			// 	.on({
			// 		/* focus+click か mouseupか。とりあえずmouseupだけでいく*/

			// 		// 'focus': function() {
			// 		// 	$(this).select();
			// 		// },
			// 		// 'click': function() {
			// 		// 	$(this).select();
			// 		// 	return false;
			// 		// },
			// 		'mouseup': function() {
			// 			$(this).select();
			// 		},
			// 		'click': function() {
			// 			alert('a');
			// 		},
			// 		'focus': function() {
			// 			alert('a');
			// 		},

			// 	});

	});

	//WIXファイル編集フォームでのEnter禁止
	$('.wixfile_keyword_edit, .wixfile_target_edit').keypress(function(e){
		if( (e.which == 13) || (e.keyCode == 13) )
			return false;
	});

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

				//対象要素を削除
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