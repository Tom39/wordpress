//画面サイズ
var windowWidth = 0;
var windowHeight = 0;

var leftPosition;
var topPosition;

var decideLink = new Array();

var manual_decide_check;

jQuery(function($) {

	/* ↓ 使ってない??? (2015/11/18) */
	window.onmessage = function(e){
	 	var tmp_leftPosition = Number(e.data.split(",")[0]);
	 	var tmp_topPosition = Number(e.data.split(",")[1]);
	 	leftPosition = tmp_leftPosition;
	 	topPosition = tmp_topPosition;
	 	// console.log(leftPosition + ' : ' + topPosition);
	 }

	windowWidth = $(window).width();
	windowHeight = $(window).height();

  	$('#detail_show').click(function(){
  		$('#detail_show').hide();
  		$('#detailSettings').show();
  	});
  	$('#detail_hide').click(function(){
  		$('#detailSettings').hide();
  		$('#detail_show').show();
  	});

  	//ページ作成画面における、WIXファイルへのエントリ追加
  	$('#new_entry_insert').click(function(){
  		var keyword = $('#newEntry input:text').eq(0).val();
  		var target = $('#newEntry input:text').eq(1).val();
  		if ( keyword.length != 0 && target.length != 0 ) {
  			var entry_data = {
				'action': 'wix_new_entry_insert',
				'keyword' : keyword,
				'target' : target,
			};
			$.ajax({
				async: true,
				dataType: "json",
				type: "POST",
				url: ajaxurl,
				data: entry_data,

				success: function(json) {
					if ( json['result'] == 'SUCCESS') {
						$('#newEntry input:text').eq(0).val('');
  						$('#newEntry input:text').eq(1).val('');
  						$('#insert_success').show();

  						//成功したら、このキーワードが出現するドキュメント群をwixfilemeta_postsに格納
  						var data = {
  							'action': 'wix_wixfilemeta_posts_insert',
  							'keyword_id': json['keyword_id'],
  							'keyword': json['keyword'],
  						};
  						$.ajax({
							async: true,
							dataType: "json",
							type: "POST",
							url: ajaxurl,
							data: data,

							success: function(json) {
								console.log(json);
							},

							error: function(xhr, textStatus, errorThrown) {
								console.log(textStatus);
							}
						});

					} else {
						alert('既に存在する情報です');
					}
				},

				error: function(xhr, textStatus, errorThrown){
					console.log(textStatus);
				}

			});

			$('#newEntry input:text').focus(function(){
				$('#insert_success').hide();
			});

 		} else {
 			alert('入力してください');
 		}
 
  	}); 


  	$('#wix_entry_recommendation').click(function(){
  		var sentence = $('iframe:first').contents().find('#tinymce').eq(0).html();
  		var entry_data = {
			'action': 'wix_entry_recommendation_creating_document',
			'sentence': sentence,
			'doc-title': $('#titlewrap input:text').val(),
			'target': $('#post-preview').attr('target'),
		};
		$.ajax({
			async: true,
			dataType: "json",
			type: "POST",
			url: ajaxurl,
			data: entry_data,

			success: function(json) {
console.log(json['similarity']);
// console.log(json['returnValue']);
				if ( json['returnValue'].length != 0 ) {
					var contents = $("<div />", {
						id: 'wixRecommendDiv'
					});
					var pop = new $pop(contents , {
						type: 'inline',
						title: 'WIX Recommendation',
						width: windowWidth,
						height: windowHeight - 50,
						modal: true,
						windowmode: false,
						close: true,
						resize: true
					});  

					var tableDiv = $("<div />", {
						id: 'popTableDiv'
					}).css({'margin' : '5px auto'}).appendTo(contents);
					
					var table = $("<TABLE />",{
						id: 'popTable',
						class: 'popTable'
					}).appendTo(tableDiv);

					var tr = $("<TR />", {
						id: 'popTableTr',
						class: 'popTable'
					}).appendTo(table);
					
					$("<TH />", {
						text: 'キーワード'
					}).css({'white-space': 'nowrap'}).appendTo(tr);
					$("<TH />", {
						text: 'リンク先Webページ候補'
					}).css({'white-space': 'nowrap'}).appendTo(tr);
					$("<TH />", {
						text: '選択'
					}).css({'white-space': 'nowrap', 'width' : '8px'}).appendTo(tr);

					var td_num = 0;
					$.each(json['returnValue'], function(keyword, titles) {
						var tr = $("<TR />").appendTo(table);

						$("<TD />", {
							text: keyword
						}).css({'white-space': 'nowrap'}).appendTo(tr);

						var td = $("<TD />",{
							class: 'wixRecomTd',
						}).appendTo(tr);
						var td2 = $("<TD />").appendTo(tr);

						var count = 0;
						$.each(titles, function(index, title){
							// if ( title != $('#titlewrap input:text').val() ) {
								$("<div />", {
									text: title
								}).css({'white-space': 'nowrap'}).appendTo(td);
								$("<input />", {
									type: "checkbox",
									id: 'wixRecomCheck' + td_num + '-' + count,
								}).appendTo(td2);
								count++;
							// }
						});

						td_num++;
					});

					var buttonDiv = $("<div />", {
						id: 'popButtonDiv'
					}).appendTo(contents);

					$('<button />', {
						text: "ADD ENTRY",
						click: function(event) {
							/* Act on the event */
							var newEntry = new Array();
							var count = 0;
							$.each($('#popTable input:checkbox'), function(index, elm) {
								if ( $('#popTable input:checkbox').eq(index).prop('checked') ) {
									var keyword = $('#popTable input:checkbox').eq(index).parent().parent().children(0).eq(0).text();
									var id = $('#popTable input:checkbox').eq(index).attr('id');
									var former_id = Number(id.substring(13, id.indexOf('-')));
									var later_id = Number(id.substring(id.indexOf('-') + 1));
									var target = $('.wixRecomTd').eq(former_id).children().eq(later_id).text().split('【')[1];
									target = target.substring(0, target.length - 1);
									newEntry[count] = {
										'keyword' : keyword,
										'target' : target
									};
									count++;
								}
							});
// console.log(newEntry);
							if ( newEntry.length != 0 ) {
								data = {
									'action': 'wix_new_entry_inserts',
									'entry': newEntry,
								};
								$.ajax({
									async: true,
									dataType: "json",
									type: "POST",
									url: ajaxurl,
									data: data,

									success: function(json){
// console.log(json['test']);
// console.log(json['entry']);
										alert('完了しました');
									},
									error: function(xhr, textStatus, errorThrown){
										alert('wixDecide.js DB Insert Error');
									}
								});
							} else {
								alert('選択してください');
							}
						}
					}).appendTo(buttonDiv);
					$('<button />', {
						text: "CANSEL",
						click : function(event) {
							/* Act on the event */
							pop.close();
						}
					}).appendTo(buttonDiv);
				} else {
					alert('推薦可能なエントリはありません');
				}
			},

			error: function(xhr, textStatus, errorThrown){
				console.log(textStatus);
			}

		});
  	});


	//Manual Decideボタンを出現させるか、動作させるかのチェックをAjaxで持ってきてる
	data = {
		'action': 'wix_manual_decide_check'
	};
	$.ajax({
		async: true,
		dataType: "json",
		type: "POST",
		url: ajaxurl,
		data: data,

		success: function(json){
			manual_decide_check = json['manual_decide_check'];
			if ( manual_decide_check == 'true' ) {
		  		//新規作成ページだったらmetaboxを隠しておく
		  		if ( adminpage == 'post-new-php' ) {
		  			$('#wixDecide').hide();
		  		}

		  		//更新ページまたは、messageが出現していれば
				if ( (adminpage == 'post-php' || location.href.indexOf('message') != -1) && $('#publish').length ) {
					$('#wixDecide')
						.click(function(evt) {
							// $('#publish').trigger('click');

							/*
							* href: プレビュー先URL 
							* target: 編集中コンテンツID
							* post_format: フォーマットの種類
							* after_body_part: 差し替え用のBody
							*/
							var href = decodeURI( $('#post-preview').attr('href') );
							var target = $('#post-preview').attr('target');
							var post_format = $('#post-formats-select :input:checked').val();

							var after_body_part = $('iframe:first').contents().find('#tinymce').eq(0).html();
							if ( after_body_part == null )
								after_body_part = $('#content-textarea-clone').text(); 

							var data = {
								'action': 'wix_decide_preview',
								'target' : target,
								'post_format' : post_format,
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
	// console.log(json['test']);
	// console.log(json['test2']);
	// console.log(json['js']);
	// console.log(json['js2']);

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

									//iframeへのbody挿入
									var iframe = window.document.getElementById('wixDecideIframe');
									iframe.contentWindow.document.open();
									iframe.contentWindow.document.write(json['html']);
									iframe.contentWindow.document.close();

									$('iframe').ready(function(){
										//all non-attachボタンの作成
										var wixAllNoAttachButton = $("<button />", {
											text: 'All Non Create Link',
											class: 'wixAllNoAttachBtn',
											href: 'javascript:;',
											title: 'wixAllNoAttach',
											id: 'pwWixAllNoAttach',
											click: function(event) {
												$.each($('#wixDecideIframe').contents().find('.wix-authorLink'), function(index, el) {
													var start = $(this).attr('start');
													var keyword, end;

													if ( (start in decideLink) == true ) {
														delete decideLink[start];
													}
													keyword = $(this).html();
													end = parseInt(start) + keyword.length;

													decideLink[start] = {'keyword':keyword,'target':'no_attach','end':end};
													$(this).css('background', '#ccccff');
												});
											}
										});
										$('.pWindow').children().eq(0).before(wixAllNoAttachButton);

										//Decide決定ボタンの作成
										var wixDecideButton = $("<button />", {
											text: 'Decide',
											class: 'wixDecideBtn',
											href: 'javascript:;',
											title: 'wixDecide',
											id: 'pwWixDecide',
											click: function(event) {
												/* Act on the event */
												if (decideLink.length > 0) {
													//Decideファイル作成部
													var count = 0;
													var post_decideLink = new Array();
													var nextStartArray = new Array();

													//Decide処理で回収していない残りもDecideファイルに入れるようにしてる
													//今使ってない（2015/9/29）
													// rest_popup();

													$.each(decideLink, function(index, ar) {
														if (ar !== undefined) {
															if ( count != 0 ) 
																nextStartArray.push(index);

															count++;
														}
													});
													nextStartArray.push(0);
													
													count = 0;
													$.each(decideLink, function(index, ar) {
														if (ar !== undefined) {
															ar['start'] = index;
															ar['nextStart'] = nextStartArray[count];
															post_decideLink[count] = ar;
															count++;
														}
													});

													data = {
														'action': 'wix_create_decidefile',
														'post_ID': target,
														'decideLink' : post_decideLink
													};
													$.ajax({
														async: true,
														dataType: "json",
														type: "POST",
														url: ajaxurl,
														data: data,

														success: function(response){
															console.log(response);
														},
														error: function(xhr, textStatus, errorThrown){
															alert('wixDecide.js DecideFile Create Error');
														}
													});

													if ( $('#wixDecide_message').length != 0 )
														 $('#wixDecide_message').remove();
														
													$('#lost-connection-notice')
														.before('<div id="wixDecide_message" class="updated below-h2"><p>WIX Decide処理を行いました</p></div>');
													$('#publish').show();
													// $('#wixDecide').hide();
													pop.close(); pop2.close();
												} else {
													if ( confirm('Decide処理してませんが、閉じていいですか？') ) {
														$('#lost-connection-notice')
															.before('<div id="wixDecide_message" class="updated below-h2"><p>WIX Decide処理は行ってません</p></div>');
														$('#publish').show();
														// $('#wixDecide').hide();
														pop.close(); pop2.close();
													}
												}
												
											}
										});
										$('.pWindow').children().eq(0).before(wixDecideButton);


										//既存Decide情報の見える化
										// data = {
										// 	'action': 'wix_decidefile_check',
										// 	'post_ID': target,
										// };
										// $.ajax({
										// 	async: true,
										// 	dataType: "json",
										// 	type: "POST",
										// 	url: ajaxurl,
										// 	data: data,

										// 	success: function(response){
										// 		if ( response['existingDecideInfo'] != '' ) {
										// 			var contents2 = $("<iframe />", {
										// 				id: 'wixExistingDecideInfo'
										// 			});
										// 			var pop2 = new $pop(contents2 , {
										// 				type: 'inline',
										// 				title: 'WIX Decide情報',
										// 				width: 400,
										// 				height: 400,
										// 				modal: false,
										// 				windowmode: false,
										// 				close: true,
										// 				resize: true
										// 			});  
										// 			iframe = window.document.getElementById('wixExistingDecideInfo');
										// 			iframe.contentWindow.document.open();
										// 			iframe.contentWindow.document.write(response['existingDecideInfo']);
										// 			iframe.contentWindow.document.close();
										// 		}

										// 	},
										// 	error: function(xhr, textStatus, errorThrown){
										// 		alert('wixDecide.js Exsiting DecideInfo Error');
										// 	}
										// });
										
										//ポップアップの処理
										$('#wixDecideIframe').contents().find('.wix-authorLink').mouseover(function() {
											popupMenu($(this));
											//既存orクリックされたTargetなら色付け
											// clickPopupTarget($(this));

											//ポップアップのクリックイベント
											$('#wixDecideIframe').contents().find('.wix-pre-authorLink').off();
											$('#wixDecideIframe').contents().find('.wix-pre-authorLink').click(function(){
												createPreDecideFile($(this));
												//クリックされたキーワードの色変更
												$(this).parents('span').prev().css('background', '#ccccff');
											});
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

										pop.close(); pop2.close();
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
		},
		error: function(xhr, textStatus, errorThrown){
			alert('wixDecide.js Manual Decide Check Error');
		}
	});
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
	for ( var i = 0; i < target.length; i++ ) {
		// var att = "class='wix-pre-authorLink' href='javascript:void(0)' target='" + target[i] + "' start='" + targetStart + "'";
		var att = "class='wix-pre-authorLink' href='javascript:void(0)'";
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

function clickPopupTarget(object) {

}


//.wix-pre-authorLinkのクリックイベント
function createPreDecideFile(object){
	var start = object.parents('span').prev().attr('start');
	var keyword, target, end;

	if ( (start in decideLink) == true ){
		delete decideLink[start];
	}
	keyword = object.parents('span').prev().html();
	target = object.html();
	end = parseInt(start) + keyword.length;

	decideLink[start] = {'keyword':keyword,'target':target,'end':end};

	console.log(decideLink);
}

//Decide時にポップアップをクリックしていないものも、デフォルトで回収
//今使ってない（2015/9/29）
function rest_popup() {
	var test = new Array();
	var start, keyword, target, end;

	var object = jQuery('iframe').contents().find('.wix-authorLink');
	var obj_size = object.size();
	for ( var i = 0; i < obj_size; i++) {
		start = object.eq(i).attr('start');
		if(decideLink.hasOwnProperty(start) == false) {
			keyword = object.eq(i).html();
			target = object.eq(i).attr('targets').split(',')[0];
			end = parseInt(start) + keyword.length;
			decideLink[start] = {'keyword':keyword,'target':target,'end':end};
		}
	}
}



//クリックされたポップアップを視覚的に分からせる
// function changeColor(object) {
	//クリックされたキーワードの色変更
	// object.parents('span').prev().css('background', '#ccccff');
	// object.css('cssText','color: blue !important;');
// }
