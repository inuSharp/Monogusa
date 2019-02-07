function getElmById(id) {
    return document.getElementById(id);
  }
  function showLoading() {
    getElmById('loader-bg').style.display = 'block';
  }
  function hideLoading() {
    getElmById('loader-bg').style.display = 'none';
  }
  function logout() {
    location.href = SERVER + '/logout';
  }

  function infoShow(message) {
    let bgColor = '#512DA8';
    flash(0, 'alertMessageOuter', bgColor);
    document.getElementById('alertMessageOuter').style.backgroudColor = bgColor;
    document.getElementById('alertMessage').innerHTML = message;
    document.getElementById('alertMessageOuter').style.display = 'block';
  }
  function messageClose() {
    getElmById('alertMessageOuter').style.display = 'none';
  }
  function alertShow(message) {
    let bgColor = '#E91E63';
    flash(0, 'alertMessageOuter', bgColor);
    document.getElementById('alertMessageOuter').style.backgroudColor = bgColor;
    document.getElementById('alertMessage').innerHTML = message;
    document.getElementById('alertMessageOuter').style.display = 'block';
  }
  function flash(count, id, baseColor) {
    count++;

    if (count % 2 == 1) {
        getElmById(id).style.backgroundColor = baseColor;
    } else {
        getElmById(id).style.backgroundColor = '#FF9800';
    }

    if (count == 5) { return; }
    setTimeout(() => {
      flash(count, id, baseColor);
    }, 350);
  }
  function menuShow() {
      var elm = document.getElementById('sp-menu');
      if (document.getElementById('spMenuList').innerHTML == '') {
          document.getElementById('spMenuList').innerHTML = document.getElementById('menu').innerHTML;
          setOpenCloseEvent();
      }
      elm.style.display = 'inline-block';
      document.getElementById('overlayMenu').style.display = 'block';
      document.getElementsByTagName('body')[0].style.position = 'fixed';
  }
  function menuHide() {
    var elm = document.getElementById('sp-menu');
    elm.style.display = 'none';
    document.getElementById('overlayMenu').style.display = 'none';
    document.getElementsByTagName('body')[0].style.position = 'static';
  }

  function openAndClose(event){
    var clickElm = event.target.eventElement;
    var menu = clickElm.nextElementSibling;
    var display = '';

    // classで指定したdisplayは単純なstyle.displayでは取得できない
    var currentDisplay = (menu.currentStyle || document.defaultView.getComputedStyle(menu, '')).display;
    if (currentDisplay == 'none') {
        display = 'block';
    } else {
        display = 'none';
    }
    menu.style.display = display;
  }
  
  function setOpenCloseEvent()
  {
    var menus = document.getElementsByClassName('open_close');
    for(var i = 0; i < menus.length; i++)
    {
       menus.item(i).addEventListener("click", openAndClose, false);
       menus.item(i).eventElement = menus.item(i);
    }
  }
