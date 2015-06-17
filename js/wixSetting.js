jQuery(function($) {

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
    $('#manual_decide input[type=checkbox]').click(function(){
    	var manual_decideFlag = $('.decide_management input[type=checkbox]').prop("checked");

    	var data = {
			'action': 'wix_manual_decide',
			'manual_decideFlag' : manual_decideFlag
		};

		$.ajax({
			async: true,
			dataType: "json",
			type: "POST",
			url: ajaxurl,
			data: data,

			success: function(json) {
				console.log(json['data']);
			},

			error: function(xhr, textStatus, errorThrown){
				alert('wixSetting.js Error');
			}
		});

    });




});