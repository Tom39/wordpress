//画面サイズ
var windowWidth = 0;
var windowHeight = 0;

var leftPosition;
var topPosition;

jQuery(function($) {


	window.onmessage = function(e){
	 	var tmp_leftPosition = Number(e.data.split(",")[0]);
	 	var tmp_topPosition = Number(e.data.split(",")[1]);
	 	leftPosition = tmp_leftPosition;
	 	topPosition = tmp_topPosition;
	 	// console.log(leftPosition + ' : ' + topPosition);
	 }

	windowWidth = $(window).width();
	windowHeight = $(window).height();


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

  	if ( typeof(manual_decideFlag) != "undefined" ) {
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
// console.log(json['html']);
// console.log( json['html'].substring(json['html'].indexOf('<head>'), json['html'].indexOf('</head>')) );

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

									//ポップアップの処理
									$('#wixDecideIframe').contents().find('.wix-authorLink').mouseover(function() {
										popupMenu($(this));
										// alert($(this).attr('keywords'));
									});

									$('#wixDecideIframe').contents().find('.decidemenu').mouseover(function(event) {
										displayMode('block', $(this));
									});
									$('#wixDecideIframe').contents().find('.decidemenu').mouseout(function(event) {
										displayMode('none', $(this));
									});

								});

								//背景のグレー画面をクリックしたらモーダルclose
								$('#pwCover').off().click(function(event) {
									// html、bodyの固定解除
									// $('html, body').removeClass('lock');

									pop.close();
								});
							},

							error: function(xhr, textStatus, errorThrown){
	      						// alert('Error');
	      						console.log(textStatus);
	    					}

	    				});

					return false;

					});


			} else {
				// alert('elseだよ');
			}
		}
	}

});



//ポップアップの処理
function displayMode(block_none, object) {
	object.css('display', block_none);
}
function popupMenu(object) {
	var keyword = object.attr('keywords').split(',');
	var target = object.attr('targets').split(',');
	var targetStart = object.attr('start');
	var popupIndex = object.attr('popupIndex');

	popupMenuItem = new Array();
	for ( var i = 0; i < keyword.length; i++ ) {
		var att = "class='wix-pre-authorLink' href='javascript:void(0)' target='" + target[i] + "' start='" + targetStart + "'";
		popupMenuItem.push(target[i], att);
	}
	// メニュー作成
	var layer;
	var roop;
	var url;
	var subject;
	layer = "<table class='predecidetable' onmouseover=\"window.parent.displayMode('block', " + object.next() + ");\" onmouseout=\"window.parent.displayMode('none', " + object.next() + ");\" style=\"\">";
	layer += "<thead><tr><th scope='col'>WIX Decide</th></tr></thead>";
	roop = popupMenuItem.length / 2;
	for (i = 0; i < roop; i++) {
		url = i * 2 + 1;
		subject = i * 2;
		layer += "<tr>";
		layer += "<td>";
		layer += "<a " + popupMenuItem[url] + ">" + popupMenuItem[subject] + "</a><br>";
		layer += "</td>";
		layer += "</tr>";
	}
	layer += "<tfoot><tr><th scope='col'></th></tr></tfoot>";
	layer += "</table>";
	// ポップアップメニュー表示

	var offsetLeft = object.offset().left;
	object.next()
				.css('display', 'block')
				.css('left', offsetLeft + 'px')
				.html(layer);
}





// function displayMode(block_none, popupIndex) {
// 	// $('#decidemenu' + popupIndex).css('display', block_none);
// 	$('#wixDecideIframe').contents().find('#decidemenu' + popupIndex).css('display', block_none);
// }
// function popupMenu(keyword, target, targetStart, popupIndex) {
	// popupMenuItem = new Array();
	// for ( var i = 0; i < keyword.length; i++ ) {
	// 	// var att = "class = 'wix-authorLink' target = \"_blank\" href = '\"' ";

	// 	var att = "class='wix-pre-authorLink' href='javascript:void(0)' target='" + target[i] + "' start='" + targetStart + "'";
	// 		popupMenuItem.push(target[i], att);
	// }
	// // メニュー作成
	// var layer;
	// var roop;
	// var url;
	// var subject;
	// layer = "<div class='predecidetable' onmouseover=\"window.parent.displayMode('block', " + popupIndex + ");\" onmouseout=\"window.parent.displayMode('none', " + popupIndex + ");\" style=\"\">";
	// layer += "<thead><tr><th scope='col'>WIX Decide</th></tr></thead>";
	// roop = popupMenuItem.length / 2;
	// for (i = 0; i < roop; i++) {
	// 	url = i * 2 + 1;
	// 	subject = i * 2;
	// 	layer += "<tr>";
	// 	layer += "<td>";
	// 	layer += "<a " + popupMenuItem[url] + ">" + popupMenuItem[subject] + "</a><br>";
	// 	layer += "</td>";
	// 	layer += "</tr>";
	// }
	// layer += "<tfoot><tr><th scope='col'></th></tr></tfoot>";
	// layer += "</table>";
	// // ポップアップメニュー表示
	// var offsetLeft = $('#wixDecideIframe').contents().find('#decidemenu' + popupIndex).prev().offset().left;
	// $('#wixDecideIframe').contents().find('#decidemenu' + popupIndex)
	// 									.css('display', 'block')
	// 									.css('left', offsetLeft + 'px')
	// 									.html(layer);
	// decidemenu.style.display = 'block';	
	// decidemenu.style.left = decidemenu.previousSibling.offsetLeft + 'px';
	// decidemenu.innerHTML = layer;
// }





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