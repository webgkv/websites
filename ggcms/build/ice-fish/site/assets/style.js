$(function(){

  $('.lng_btn1').on('click',function(){
    if($(this).hasClass('active')) {
      $(this).removeClass('active');
      $('.lngs').hide();
      $('.dohidem').removeClass('down');
    } else {
      $(this).addClass('active');
      $('.lngs').show();
      $('.dohidem').addClass('down');
    }
    return false;
  });

//on change resolution

  $('html,body').animate({scrollTop:0},0);

  if($('#menu-container').is(':visible')) {
    var menu_top=$('#menu-container').offset().top;
    var menuontop=0;
    var menuid=0;
    $(window).scroll(function(){
      if(menuontop==0 && $(this).scrollTop()>=menu_top) {
        $('#menu-container').addClass('top');
        menuontop=1;
      } else if(menuontop==1 && $(this).scrollTop()<menu_top){
        $('#menu-container').removeClass('top');
        menuontop=0;
      }
    });
  }


//  $('.expand').on('mouseover',function () {
//    if($('#menu .text-center').is(':visible')) {
//      menuid=$(this).data('menuid');
//      $(this).parents('.tmenu_link').find('.submenu').css({'top':$('#menu').css('height')});
//      $(this).parents('.tmenu_link').find('.submenu').removeClass('hidden');
//    }
//  });

//  $('.tmenu_a').on('mouseover',function () {
//    if($('#menu .text-center').is(':visible')) {
//      if(menuid!=$(this).data('menuid')) {
//        $('.submenu').addClass('hidden');
//      }
//    }
//  });

//  $('.submenu').on('mouseleave',function () {
//    if($('#menu .text-center').is(':visible')) {
//      $(this).addClass('hidden');
//    }
//  });

  $('.tmenu_link').on('mouseover',function () {
    $('.submenu').addClass('hidden');
    if($(this).find('.submenu.hidden').length>0) {
//      $(this).find('.submenu').css({'top':$('#menu').css('height')});
      $(this).find('.submenu').css({'top':'80px'});
//      $(this).find('.submenu').css({'top':'70px'});
      $(this).find('.submenu').removeClass('hidden');
    }
  });

  $('#menu').on('mouseout',function () {
    $('.submenu').addClass('hidden');
  });

  $('.dohide').on('click',function(){
    var blockhide=$(this).closest('.phide').children('.hide');
    if(blockhide.hasClass('hidden')) {
      blockhide.removeClass('hidden');
      $(this).removeClass('down');
    } else {
      blockhide.addClass('hidden');
      $(this).addClass('down');
    }
    return false;
  });

  $('.dohide2').on('click',function(){
    var blockhide=$(this).closest('.phide').children('.hide');
    if(blockhide.hasClass('hidden')) {
      blockhide.removeClass('hidden');
      $(this).removeClass('down');
    } else {
      blockhide.addClass('hidden');
      $(this).addClass('down');
    }
    return false;
  });

  $('.dohide_m').on('click',function(){
    var blockhide=$(this).closest('.phide_m');
    if(blockhide.hasClass('h')) {
      blockhide.removeClass('h');
    } else {
      blockhide.addClass('h');
    }
    return false;
  });

//  $('.showmenu').on('click',function(){
//    if($('#menu_m').hasClass('hidden')) {
//      $('#submenu_search').addClass('hidden');
//      $('#menu_m').removeClass('hidden');
//    } else{
//      $('#menu_m').addClass('hidden');
//    }
//    return false;
//  });

  $('.showmenu').on('click',function(){
    if(!$('#showsearch').find('img').hasClass('red')) {
      if($('#menu_m').hasClass('hidden')) {
        $('body').css({'overflow':'hidden'});
        $('#submenu_search').addClass('hidden');
        $('#menu_m').removeClass('hidden');
        $(this).addClass('red');
      } else{
        $('body').css({'overflow':'auto'});
        $('#menu_m').addClass('hidden');
        $(this).removeClass('red');
      }
    }
    return false;
  });

//  $('#showsearch').on('click',function(){
//    if($('#submenu_search').hasClass('hidden')) {
//      $('#submenu_search').css({'top':$('#menu').css('height')});
//      $('#menu_m').addClass('hidden');
//      $('#submenu_search').removeClass('hidden');
//    } else{
//      $('#submenu_search').addClass('hidden');
//    }
//    return false;
//  });

  $('#showsearch').on('click',function(){
    if($('#menu_m').hasClass('hidden')) {
      var blockhide=$(this).closest('.tmenu_link');
      if(blockhide.hasClass('h')) {
        $('body').css({'overflow':'hidden'});
        blockhide.removeClass('h');
        $(this).find('img').addClass('red');
      } else {
        $('body').css({'overflow':'auto'});
        blockhide.addClass('h');
        $(this).find('img').removeClass('red');
      }
    }
    return false;
  });

  $('body').on('click', '.copylink', function () {
    var dummy = document.createElement('input'),
         text = window.location.href;
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    document.execCommand('copy');
    document.body.removeChild(dummy);
    return false;
  });

  $('.gobonus').on('click',function(){
    $('html,body').animate({scrollTop: $('.bonusuri').offset().top-90},500);
    return false;
  });

  $('.gorecenziile').on('click',function(){
    $('html,body').animate({scrollTop: $('.recenziile').offset().top-90},500);
    return false;
  });
 
  $('.gotext').on('click',function(){
    $('html,body').animate({scrollTop: $('.recenzie').offset().top-90},500);
    return false;
  });

  $('.gofaq').on('click',function(){
    $('html,body').animate({scrollTop: $('.faq').offset().top-90},500);
    return false;
  });

  $('.goblock_1').on('click',function(){
    $('html,body').animate({scrollTop: $('.block_1').offset().top-90},500);
    return false;
  });

  $('.goblock_2').on('click',function(){
    $('html,body').animate({scrollTop: $('.block_2').offset().top-90},500);
    return false;
  });

  $('.goblock_3').on('click',function(){
    $('html,body').animate({scrollTop: $('.block_3').offset().top-90},500);
    return false;
  });

  function setCookie() {
    var expires = new Date();
    expires.setTime(expires.getTime() + (365 * 24 * 60 * 60 * 1000)); //year
    document.cookie = 'cookie=1;expires=' + expires.toUTCString();
  }

  $('body').on('click','.cookie1 .closecookie',function(){
    $('.cookie1').addClass('hidden');
    return false;
  });

  $('body').on('click','.cookie2_0 .closecookie',function(){
    $('.cookie2_0').addClass('hidden');
    return false;
  });

  $('body').on('click','.cookie1_7',function(){
    $('.cookie1').addClass('hidden');
    $('.cookie2_0').removeClass('hidden');
    return false;
  });

  $('body').on('click','.cookie2_6',function(){
    $('.cookie2_0 input[type=checkbox]').prop('checked','true');
    $('.cookie2_0').addClass('hidden');
    setCookie();
    return false;
  });

  $('body').on('click','.cookie1_6',function(){
    $('.cookie1').addClass('hidden');
    setCookie();
    return false;
  });

  $('body').on('click','.cookie2_7',function(){
    $('.cookie2_0').addClass('hidden');
    setCookie();
    return false;
  });

  var swiper1 = new Swiper('.swiper1', {
    slidesPerView: 1,
    spaceBetween: 30,
    loop: true,
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    pagination: {
      el: '.swiper-pagination',
    },
    autoplay: {
      delay: 15000,
    },
    mousewheel: true
  });

  var swiper2 = new Swiper('.swiper2', {
    slidesPerView: 1,
    spaceBetween: 30,
    loop: true,
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    pagination: {
      el: '.swiper-pagination',
    },
    mousewheel: true
  });

});