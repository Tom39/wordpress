
// var leftPosition;
// var topPosition;

// window.onmessage = function(e){
//  	var tmp_leftPosition = Number(e.data.split(",")[0]);
//  	var tmp_topPosition = Number(e.data.split(",")[1]);
//  	leftPosition = tmp_leftPosition;
//  	topPosition = tmp_topPosition;

//  }

// function displayMode ( mode, id, popupIndex ) {
// 	$('#after' + id).contents().find('#predecidemenu' + popupIndex).css("display", mode); 
// }
// function displayMode_LeftTopMoving ( mode, id, popupIndex, left, top, object ) {
// 	// $('#after' + id).contents().find('#predecidemenu' + popupIndex).css({"display": mode, "position": "absolute",  "top": top + "px", "left": left + "px"});
// 	var removeElement = $('#after' + id).contents().find('#predecidemenu' + popupIndex);
// 	removeElement.remove();
// 	object.after(removeElement);
// 	$('#after' + id).contents().find('#predecidemenu' + popupIndex).css({"position": "absolute",  "top": top + "px", "left": left + "px"});
// }

// function popUp(afterId, object) {
// 	// メニュー項目
// 	popupMenuItem = new Array();
// 	var popupIndex = 0;
// 	var count = 0;

// 	//リンク群
// 	for(var i = 0; i < ar.length; i++) {
// 		if (ar[i][0] == object.html()) {
			
// 			// var att = "class='preDecide-wix-link' id='pdLink" + i + "' href='javascript:void(0)' onClick=\"window.parent.createPreDecideFile('" + ar[i][1] + "');return false;\" ";
// 			var att = "class='preDecide-wix-link' id='pdLink" + i + "' href='javascript:void(0)' onmouseover=\"window.parent.createPreDecideFile();return false;\" ";
			
// 			// popupMenuItem.push(ar[i][0], att);
// 			popupMenuItem.push(ar[i][1], att);
// 			if ( count == 0 ) {
// 				popupIndex = ar[i][2];
// 				count++;
// 			}
// 		}
// 	}
// 	popupMenuItem.push('non_attach', "class='preDecide-wix-link' id='pdLink" + ar.length + "' href='javascript:void(0)' ");

// 	// メニュー作成
// 	var layer;
// 	var roop;
// 	var url;
// 	var subject;
// 	layer = "<table class='predecidetable' onmouseover=\"window.parent.displayMode('block', " + afterId + ", " + popupIndex + ");\" onmouseout=\"window.parent.displayMode('none', " + afterId + ", " + popupIndex + ");\" style=\"\">";
// 	layer += "<thead><tr><th scope='col'>WIX PreDecide</th></tr></thead>";
// 	roop = popupMenuItem.length / 2;
// 	for (i = 0; i < roop; i++) {
// 		url = i * 2 + 1;
// 		subject = i * 2;
// 		layer += "<tr>";
// 		layer += "<td>";
// 		layer += "<a " + popupMenuItem[url] + ">" + popupMenuItem[subject] + "</a><br>";
// 		layer += "</td>";
// 		layer += "</tr>";
// 	}
// 	layer += "<tfoot><tr><th scope='col'></th></tr></tfoot>";
// 	layer += "</table>";
// 	// alert(layer);


// 	// ポップアップメニュー表示
// 	var insert_popup = $('<span>').attr('id', 'predecidemenu' + popupIndex)
// 									.attr('class', 'menu')
// 									.attr('style', 'position:absolute; left:' + leftPosition + 'px; top:' + topPosition + 'px;')
// 									.attr('onmouseover', 'window.parent.displayMode(\'block\', ' + afterId + ", " + popupIndex + ');')
// 									.attr('onmouseout', 'window.parent.displayMode(\'none\', ' + afterId + ", " + popupIndex + ');')
// 									.html(layer);
// 	// insert_popup.style.left = insert_popup.previousSibling.offsetLeft + 'px';

// 	//対象キーワードの兄弟要素として後に並べる	
// 	$('#after' + afterId).contents().find("#" + object.attr('id')).after(insert_popup);
// 	// console.log(leftPosition + ", " + topPosition);

// 	// var decidemenu = document.getElementById('decidemenu' + popupIndex);
// 	// alert(decidemenu);
// 	// decidemenu.style.display = 'block';	
// 	// decidemenu.style.left = decidemenu.previousSibling.offsetLeft + 'px';
// 	// decidemenu.innerHTML = layer;

// 	// var xxx = parent.document.getElementById('after' + id).contentWindow.MousePosition();

// }
