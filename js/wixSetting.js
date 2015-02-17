//Decide処理の有無フラグ
// var manual_decideFlag;
// var auto_decideFlag;

jQuery(function($) {

	//パターンファイルのフォーム追加
	$('#add_patternFile').click(function() {
		var parentElementName = '#patternFile_form #pattern_wid ';

		//フォームを追加
		var pattern_wid_len = $(parentElementName + 'li').length;
		var insertElement = '<li><input type="text" name="pattern[' + pattern_wid_len + ']"> <input type="text" name="wid[' + pattern_wid_len + ']"></li>';
		$(parentElementName).append(insertElement);

		// 削除ボタンの一旦全消去し、配置し直す
		$(parentElementName + 'input[type="button"]').remove();

		var delete_btn = ' <input type="button" value="Delete" class="button button-primary button-large">';
		$(parentElementName + 'li').each(function(index) {
			$(parentElementName + 'li').eq(index).append(delete_btn);
		});


	});

	// 削除ボタンを押した場合の処理
	$(document).on('click', '#patternFile_form #pattern_wid input[type="button"]', function(e) {
		var parentElementName = '#patternFile_form #pattern_wid ';

		//フォームを削除
		var idx = $(e.target).parent().index();
		$('#patternFile_form #pattern_wid li').eq(idx).remove();

		// フォームがひとつになるなら、削除ボタンは不要なので消去
		if ($(parentElementName + 'li').length == 1) $(parentElementName + 'input[type="button"]').remove();

		// フォームの番号を振り直す
		$(parentElementName + 'li').each(function(index) {
			 $(this).children('input:text:eq(0)').attr('name', 'pattern[' + index + ']');
			 $(this).children('input:text:eq(1)').attr('name', 'wid[' + index + ']');
		});

	});	


	
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