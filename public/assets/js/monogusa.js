// 幅。画面サイズによって計算方法が変わる
var nowLeftWidth   = '';
var nowCenterWidth = '';
var nowRightWidth  = '';
var leftLeft       = 0;
var centerLeft     = '';
var rightRight     = '';


function changedWindowSize(width, height) {
  screenWidth  = width;
  screenHeight = height;
}
function showNowLoading() {
  showOverlay(nowLoading);
}
function showSub() {
  showOverlay(document.getElementById('confirm').innerHTML, 'sub_view_');
}
function showDialog(dialogId) {
  var width = parseInt(screenWidth * 0.9);
  document.getElementById('sub_view_overlay_message_text').style.width = width + 'px';
  var height = parseInt(screenHeight * 0.9);
  //document.getElementById('sub_view_overlay_message_text').style.height = height + 'px';
  showOverlay(document.getElementById(dialogId).innerHTML, 'sub_view_');
}
function showOverlay(message, sub = '') {
  document.getElementById(sub + 'overlay_message_text').innerHTML = message;
  document.getElementById(sub + 'overlay').style.display = 'block';
  if (sub != '') {
    document.getElementById(sub + 'overlay_message_text').style.background = 'rgba(255, 255, 255, 0.8)';
    document.getElementById(sub + 'overlay_message_text').style.padding    = '5px';
  } else {
    document.getElementById(sub + 'overlay_message_text').style.background = 'rgba(0, 0, 0, 0.8)';
    document.getElementById(sub + 'overlay_message_text').style.padding    = '15px 30px';
  }
  var h = $('#' + sub + 'overlay_message_text').height();
  var bh = window.innerHeight;
  var top = '10%';
  if (screenHeight <= h) {
    top = '0px';
  } else {
      // 全体の何%か計算してその分下にずらして中央に配置する。2は少し上に置く
      top = (parseInt(((bh-h)/2)/bh*100)-2).toString() + '%';
  }
  document.getElementById(sub + 'overlay_box').style.top = top;
}
function hideOverlay() {
  document.getElementById('overlay').style.display = 'none';
}
function hideSubView() {
  document.getElementById('sub_view_overlay').style.display = 'none';
  menuClick();
}
function postData(url,sendData,movePage,successMessage,errorElseMessage,overlayMessage,success=null) {
  showOverlay(overlayMessage);
  $.ajax({
    url: url,
    type:'POST',
    data: sendData
  }).done(function(data) {
    if (successMessage != '') {
       alert(successMessage);
    }
    if (success != null && typeof(success) == "function") {
      success(data);
    }
    if (movePage) {
      location.href = data.url;
    } else {
    }
  }).fail(function(jqXHR, textStatus, errorThrown) {
    console.log(jqXHR.responseJSON);
    console.log("textStatus : " + textStatus);
    console.log("errorThrown : " + errorThrown);
    if (errorThrown == "Bad Request") {
      if (("responseJSON" in jqXHR) && 
          ("message" in jqXHR.responseJSON)
      ) {
        alert(jqXHR.responseJSON.message);
      } else {
        alert(errorElseMessage);
      }
    } else {
        if (("responseJSON" in jqXHR) && 
          ("message" in jqXHR.responseJSON)
        ) {
          alert(jqXHR.responseJSON.message);
        } else {
          alert(errorElseMessage);
        }
    }
  }).always(function() {
    hideOverlay();
  });
}
function getHash() {
  var hash = location.hash;
  hash = (hash)? hash.replace('#', '') : '';
  return hash;
}
function showView() {
    var hash = getHash();
    currenthash = hash;
    var view = hash;
    if (view == '') {
        view = 'top';
    }
    var controller = view + 'Controller';
    if (document.getElementById(view + 'View') == undefined) {
        alert('not found');
        location.hash = lastPage;
        return;
    }
    if (typeof this[controller] == "function")
    {
        this[controller]();
    }

    if (document.getElementById(lastPage + 'View') !== undefined &&
        document.getElementById(lastPage + 'View') !== null
    ) 
    {
        document.getElementById(lastPage + 'View').style.display = 'none';
    }
    document.getElementById(view + 'View').style.display = 'block';
    
    lastPage = location.hash;
    setLayout();
}
function tagClick(tagName) {
    location.href = API + '/?tag=' + tagName;
}
function sendMessage() {
    var sendData = new Object();
    sendData = {
      text: document.getElementById("message_text").value,
      human_check: document.getElementById("humanCheck").value,
      token: _TOKEN
    };
    var url = API + '/api/message';
    var success = function () {
      document.getElementById("message_text").value = '';
      alert('送信しました');
      hideSubView();
      showOverlay("読み込み中です...");
      location.reload(true);
    };
    postData(url, sendData, false, '', '登録に失敗しました', '送信中です...', success);
}
/**
 * URL解析して、クエリ文字列を返す
 * @returns {Array} クエリ文字列
 */
function getUrlVars()
{
    var vars = [], max = 0, hash = "", array = "";
    var url = window.location.search;

        //?を取り除くため、1から始める。複数のクエリ文字列に対応するため、&で区切る
    hash  = url.slice(1).split('&');    
    max = hash.length;
    for (var i = 0; i < max; i++) {
        array = hash[i].split('=');    //keyと値に分割。
        vars.push(array[0]);    //末尾にクエリ文字列のkeyを挿入。
        vars[array[0]] = array[1];    //先ほど確保したkeyに、値を代入。
    }

    return vars;
}
/**
 * バリデーションエラーになったinputを赤枠にする。
 * 画面の読み込み完了後に呼び出す
 * viewにvar validationErrorNames = '@(validationErrorNames)';の１行をscriptエリアに追記する。
 * routeの処理でvalidationErrorNamesにnameの値とメッセージをコロン区切りとカンマ区切りで返すようにする
 *   例）var validationErrorNames = 'login_id:login_id is required,login_pass:login_pass is required'
 *
 * メッセージ表示は対象の入力欄のnameに'_erro_message'をつけたidのLabelに渡します
 *   例) 
 *        <input name="login_id" type="text" size="40" value="@(login_id)">               <- これが入力チェック対象
 *        <br/><label class="validationErrorMessage" id="login_id_error_message"></label>   <- エラーメッセージ
 */
function validationErrorCheck() {
  if (validationErrorNames !== '') {
    var names = validationErrorNames.split(",");
    var validation = null;
    var errorMessageId = '';
    for( var i=0 ; i<names.length ; i++ ) {
      var validation = names[i].split(":");
      document.getElementsByName(validation[0])[0].style.border = 'solid 2px red';
      errorMessageId = validation[0] + '_error_message';
      if (document.getElementById(errorMessageId) != null) {
        document.getElementById(errorMessageId).innerHTML = validation[1];
      }
    }
  }
}


