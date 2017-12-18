
//function topController() {
//  if (!loginCheck()) {
//    location.hash = 'login';
//  }
//}
//function loginController() {
//}
//function loginCheckController() {
//  if (localStorage["lk"] == "") {
//    location.hash = 'login';
//    return;
//  }
//  var success = function (data) {
//    if(data.result == 'true') {
//      location.hash = 'top';
//      localStorage["lk"] = data.loginkey;
//    } else {
//      location.hash = 'login';
//      localStorage["lk"] = "";
//    }
//  };
//  var url = API + '/api/yw84scpcDAekiiS/login';
//  postData(url, sendData, false, '', null, 'ログインしています...', success);
//}
//function login() {
    //var sendData = new Object();
    //sendData = {
    //  login_id: document.getElementById("login_id").value,
    //  login_pass: document.getElementById("login_pass").value,
    //  token: _TOKEN
    //};
    //var url = API + '/api/yw84scpcDAekiiS/login';
    //
    //postData(url,sendData,true,'','ログインに失敗しました', 'ログインしています...');

//    location.hash = 'top';
//}
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

