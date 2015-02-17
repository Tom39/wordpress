
//マウスの座標位置
var mouseX = 0;
var mouseY = 0;
//画面サイズ
var windowWidth = 0;
var windowHeight = 0;

jQuery(function($) {

	windowWidth = $(window).width();
	windowHeight = $(window).height();

	$("body").mousemove(function(e) {
        mouseX = e.pageX;
        mouseY = e.pageY;
    });


	if ( $('#submitdiv').length ) {
	    stamp = $('#timestamp').html();
	    $('#timestampdiv')
	      .before('<p><a class="update-timestamp hide-if-no-js button" href="#update_timestamp">最新の日時に置き換えます</a></p>')
	      .prev().click(function(){
		        date = new Date();
		        var aa = date.getFullYear(), mm = date.getMonth() + 1, jj = date.getDate(), hh = date.getHours(), mn = date.getMinutes();
		        mm = '' + mm;
		        if(mm.length == 1) mm = '0' + mm;
		        $('#aa').val(aa);
		        $('#mm').val(mm);
		        $('#jj').val(jj);
		        $('#hh').val(hh);
		        $('#mn').val(mn);
		        $('#timestamp').html(
		        	postL10n.publishOnPast + ' <b>' +
		        	aa + '年' +
		        	mm + '月' +
		        	jj + '日 @ ' +
		        	hh + ':' +
		        	mn + '</b> '
		        );

				return false;
	      });
  	}

  	if ( manual_decideFlag == 'true' ) {

		if ( $('#publish').length ) {
			// $('#publish').hide();		

			$('#publish')
				.before('<input name="wix" type="button" class="button button-primary button-large" id="wixDecide" value="WIXDecide" >')
				.prev().click(function(evt) {

					/*
					* href: プレビュー先URL 
					* target: 編集中コンテンツID
					* post_format: フォーマットの種類
					* after_body_part: 差し替え用のBody
					*/
					var href =  decodeURI( $('#post-preview').attr('href') );
					var target = $('#post-preview').attr('target');
					var post_format = $('#post-formats-select :input:checked').val();
					var before_body_part = $('#content').html();
					var after_body_part = $('iframe:first').contents().find('#tinymce').eq(0).html();



					var data = {
						'action': 'wix_decide_preview',
						'target' : target,
						'post_format' : post_format,
						'before_body_part' : before_body_part,
						'after_body_part' : after_body_part
					};

					$.ajax({
						async: true,
						dataType: "json",
						type: "POST",
						url: ajaxurl,
						data: data,

						success: function(json) {

							var contents = $("<iframe />", {
								id: 'wixDecideIframe'
							});

							var pop = new $pop(contents , {
								type: 'inline',
								title: 'WIX Manual Decide',
								width: windowWidth,
								height: windowHeight - 50,
								modal: true,
								windowmode: false,
								close: true,
								resize: true
							});  


							// //iframeへのbody挿入
							var iframe = window.document.getElementById('wixDecideIframe');
							iframe.contentWindow.document.open();
							iframe.contentWindow.document.write(json['html']);
							iframe.contentWindow.document.close();


							$('iframe').ready(function(){
								//Decide決定ボタンの作成
								var wixDecideButton = $("<button />", {
									text: 'Decide',
									class: 'wixDecideBtn',
									href: 'javascript:;',
									title: 'wixDecide',
									id: 'pwWixDecide',
									click: function(event) {
										/* Act on the event */
										$('#lost-connection-notice')
											.before('<div id="wixDecide_message" class="updated below-h2"><p>WIX Decide処理を行いました</p></div>');

										$('#publish').show();
										$('#wixDecide').hide();
										pop.close();
									}
								});
								$('.pWindow').children().eq(0).before(wixDecideButton);
							});

							//背景のグレー画面をクリックしたらモーダルclose
							$('#pwCover').off().click(function(event) {
								// html、bodyの固定解除
								// $('html, body').removeClass('lock');

								pop.close();
							});
						},

						error: function(xhr, textStatus, errorThrown){
      						alert('Error');
    					}

    				});










					// var pop = new $pop(href , {
					// 	type: 'iframe',
					// 	title: 'WIX Manual Decide',
					// 	width: windowWidth,
					// 	height: windowHeight - 50,
					// 	modal: true,
					// 	windowmode: false,
					// 	close: true,
					// 	resize: true
					// });

					// //iframeの読み込みが完了後
					// $('iframe').load(function(){
					// 	//bodyの書き換え
					// 	$('iframe').contents().find('.entry-content').eq(0).children().remove();
					// 	$('iframe').contents().find('.entry-content').eq(0).append(after_body_part);

					// 	//Decide決定ボタンの作成
					// 	var wixDecideButton = $("<button />", {
					// 		text: 'Decide',
					// 		class: 'wixDecideBtn',
					// 		href: 'javascript:;',
					// 		title: 'wixDecide',
					// 		id: 'pwWixDecide',
					// 		click: function(event) {
					// 			/* Act on the event */
					// 			$('#lost-connection-notice')
					// 				.before('<div id="wixDecide_message" class="updated below-h2"><p>WIX Decide処理を行いました</p></div>');

					// 			$('#publish').show();
					// 			$('#wixDecide').hide();
					// 			pop.close();
					// 		}
					// 	});
					// 	$('.pWindow').children().eq(0).before(wixDecideButton);
					// });



					//背景のグレー画面をクリックしたらモーダルclose
					// $('#pwCover').off().click(function(event) {
					// 	// html、bodyの固定解除
					// 	// $('html, body').removeClass('lock');

					// 	pop.close();
					// });
							


				
				return false;

				});


		} else {
			// alert('elseだよ');
		}
	}



});

// 
function wix_ajax_message( message ) {
	var data = {
		'action': 'wix_message',
		'wix_ajax_message' : message
	}

	$.ajax({
		async: true,
		dataType: "json",
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(json) {
			alert('Success');
		},
		error: function(xhr, textStatus, errorThrown){
			alert('Error');
		}
	});

	return false;
}