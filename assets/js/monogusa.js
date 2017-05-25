/*
 responsive
*/
var resizeTimer;
//var interval = Math.floor(1000 / 60 * 10); // 166.666
var interval = 170; // 166.666
resizeTimer = setTimeout(callChangedWindowSize, interval);

/**
 * 表示中ページに changedWindowSize が定義されていたら呼び出す。
 * リサイズ後のウィンドウサイズを渡す。
 */
function callChangedWindowSize() {
  if (typeof changedWindowSize == "function") {
    changedWindowSize(
        document.documentElement.clientWidth || 0,
        document.documentElement.clientHeight || 0
    );
  }
}

/**
 * Window リサイズ時のイベントリスナを登録
 */
window.addEventListener('resize', function () {
  if (resizeTimer !== false) {
    clearTimeout(resizeTimer);
  }
  resizeTimer = setTimeout(callChangedWindowSize, interval);
});

var changeWindowSizeFirst = true;
function changedWindowSize(width, height) {
  if (changeWindowSizeFirst) {
    document.getElementById("dummy").style.display = 'none'; 
    changeWindowSizeFirst = false;
  }
  // simplegrid.cssの @media max-widthの値
  if (width > 767 && document.getElementById("menu") != undefined) {
    document.getElementById("menu").style.display = 'none'; 
  }
}
  function showNowLoading() {
      showOverlay('読み込み中です...');
  }
  function showOverlay(message) {
      var id = 'overlay_message_box';
      document.getElementById('overlay_message_text').innerHTML = message;
      document.getElementById('overlay').style.display = 'block';
      document.getElementById('overlay_box').innerHTML = document.getElementById(id).innerHTML;
      var h = $('#'+id).height();
      var bh = window.innerHeight;
      var top = '10%';
      if (bh <= h) {
        top = '0%';
      } else {
          // 全体の何%か計算してその分下にずらして中央に配置する。7は少し上に置く
          top = (parseInt(((bh-h)/2)/bh*100)-7).toString() + '%';
      }
      document.getElementById('overlay_box').style.top = top;
  }
  function hideOverlay() {
      document.getElementById('overlay').style.display = 'none';
  }
function sendMessage() {
    var sendData = new Object();
    sendData = {
      text: document.getElementById("message_text").value,
      token: _TOKEN
    };
    var url = SERVER + '/api/message';
    var success = function () {
      document.getElementById("message_text").value = '';
      alert('送信しました');
    };
    postData(url, sendData, false, '', '登録に失敗しました', '送信中です...', success);
}
function showMenu() {
    document.getElementById("menu").style.display = 'block';
    document.getElementById("main_contents").style.display = 'none';
    document.getElementById("munu_list").innerHTML = document.getElementById("table_of_contents").innerHTML;
}

function closeMenu() {
    document.getElementById("menu").style.display = 'none';
    document.getElementById("main_contents").style.display = 'block';
    document.getElementById("munu_list").innerHTML = document.getElementById("table_of_contents").innerHTML;
}
function login() {
    var sendData = new Object();
    sendData = {
      login_id: document.getElementById("login_id").value,
      login_pass: document.getElementById("login_pass").value,
      token: _TOKEN
    };
    var url = SERVER + '/api/yw84scpcDAekiiS/login';
    
    postData(url,sendData,true,'','ログインに失敗しました', 'ログインしています...');
}
function addUser() {
    var sendData = new Object();
    sendData = {
      login_id: document.getElementById("login_id").value,
      login_pass: document.getElementById("login_pass").value,
      token: _TOKEN
    };
    var url = SERVER + '/api/yw84scpcDAekiiS/add-user';
    postData(url,sendData,true,'追加しました','追加に失敗しました', '送信中です...');
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
      success();
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
        alert(errorElseMessage);
    }
  }).always(function() {
    hideOverlay();
  });
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


