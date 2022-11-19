$(function(){

    $('#login-form').submit(function () {

        var $form = $(this);

        $('div.login-error').hide();

        $.post('/ru/cabinet', $form.serialize()).done(function (data) {
            if (data === '1') {
            	location.reload();
			}else{
            	$('#login-error-'+data).show();
			}
        });

        return false;
    });

	$('#simple-order-form').submit(function () {

		var $form = $(this);

        $.post('/ru/order/simple-order', $form.serialize()).done(function (data) {
        	$form.find("input[type=text], input[type=email], textarea").val("");
			alert('Заявка отправлена!');
        });

		return false;
    });

	$('.slider').slick({
		autoplay: true,
		autoplaySpeed: 3000,
		speed: 1500,
		arrows: false,
		dots: true,
		responsive: [
			{
				 breakpoint: 767,
			      settings: {
			       arrows: false
			      }
			}
		]
	});
	$('.input-code').mask('9999');
	$('.input-number').mask('999');
	$('.input-phone').mask('+7 (999) 999-9999');
	$('.input-other').mask('+(999) 999-99-99');

	adjustMenu();

	$('.btn-calc').on('click', function(){
		$(this).toggleClass('active');
		if($(this).hasClass('active')) {
			$('.header__down__form-block').addClass('header__down__form-block-open');
		} else {
			$('.header__down__form-block').removeClass('header__down__form-block-open');
		}
		return false;
	});

	$('.btn-help').on('click', function(){
		$('#help-calc').fadeIn();
	});

	$('.form__block__close').on('click', function(){
		$('.header__down__form-block').removeClass('header__down__form-block-open');
		$('.btn-calc').removeClass('active');
		return false;
	});

	$('.pages__question-answer__block').hide();
	$('.pages__question-answer__btn').on('click', function(){
		$(this).toggleClass('btn-active');
		if($(this).siblings('.pages__question-answer__block').is(':visible')) {
			$(this).siblings('.pages__question-answer__block').slideUp();	
		} else {
			$(this).siblings('.pages__question-answer__block').slideDown();
		}
		return false;
	});

	$('.modal').hide();

	$('.header__log').on('click', function(){
		$('#login').fadeIn();
		return false;
	});

	$('.plan').on('click', function(){
		$('#map').fadeIn();
		return false;
	});

	$('.modal__block .forgot').on('click', function(){
		$('.forgot-form').show().prev('form').hide();
	});

	$('.modal__block .back').on('click', function(){
		$('.login-form').show().next('form').hide();
	});

	$('.forgot-form .btn').on('click', function(){
		$('.down__row__sms').show();
		$('.down__row__num, .del').hide();
	});

	/* по умолчанию открывает авторизацию */
	$('.form__btn').on('click', function(){
		$('#login').fadeIn();
	});
	/* по умолчанию открывает авторизацию */


	$('.form-block__row__choice li a').on('click', function(){
		$('.form-block__row__choice li').prev().removeClass('active');
		$(this).parent().addClass('active').next().removeClass('active');
		if ($('.choice-block-open').hasClass('active')) {
			$('.form-block__row__choice-block').slideDown();
		} else {
			$('.form-block__row__choice-block').slideUp();
		}
		return false;
	});


	$('.form-block__option').hide();
	$('header .form-block__option .close').on('click', function(){
		$('header .form-block__option').hide();
		$('.calc-size').css('display', 'inline-block');
	});
	$('.calc-size').on('click', function(){
		$('header .form-block__option').show();
		$('.form-block__option__block .input-number:first-of-type').focus();
		$(this).hide();
	});	

	$('.modal__close').on('click', function(){
		$('.modal').fadeOut();
		return false;
	});


	$('.modal__block__check input[type="radio"]').change(function() {
	  	if ($('#register').is(':checked')) {
	    	$('.check-block-1').show();
	    	$('.check-block-2, .check-block-3').hide();
	  	}
	  	if ($('#register-1').is(':checked')) {
	    	$('.check-block-2').show();
	    	$('.check-block-1, .check-block-3').hide();
	  	} 
	  	if ($('#register-2').is(':checked')) {
	    	$('.check-block-3').show();
	    	$('.check-block-1, .check-block-2').hide();
	  	}
	});
	if ($('#register').is(':checked')) {
    	$('.check-block-1').show();
    	$('.check-block-2, .check-block-3').hide();
  	}
  	if ($('#register-1').is(':checked')) {
    	$('.check-block-2').show();
    	$('.check-block-1, .check-block-3').hide();
  	} 
  	if ($('#register-2').is(':checked')) {
    	$('.check-block-3').show();
    	$('.check-block-1, .check-block-2').hide();
  	}
  	$('#check__block-1, #check__form-2, #check__block-3, #check__block-3, #check__form-3, #check__block-4, #check__block-5, #check__block-6').hide();
  	$('.pages__form input[type="radio"], .pages__form input[type="checkbox"]').change(function() {
  		if($('#option').is(':checked')) {
  			$('#check__block-1, #check__form-2').hide();
  		} else {
  			$('#check__block-1, #check__form-2').show();
  		}
  		if ($('#option-2').is(':checked')) {
  			$('#check__block-2').show();
  		} else {
  			$('#check__block-2').hide();
  		}
  		if($('#option-4').is(':checked')) {
  			$('#check__block-3, #check__form-3').hide();
  		} else {
  			$('#check__block-3, #check__form-3').show();
  		}
  		if($('#option-6').is(':checked')) {
  			$('#check__block-4').show();
  		} else {
  			$('#check__block-4').hide();
  		}
  		if($('#option-7').is(':checked')) {
  			$('#check__block-5').show();
  		} else {
  			$('#check__block-5').hide();
  		}
  		if($('#option-8').is(':checked')) {
  			$('#check__block-6').show();
  		} else {
  			$('#check__block-6').hide();
  		}
  	});

  	$('.add__phone').on('click', function(){
  		$("#block__input-phone .input-other, #block__input-phone .input-phone").clone().prependTo("#block__input-phone-1");
  		return false;
  	});


  	
  	$('.check-block-1 .dropdown').attr("id","dropdown");
  	
  	$('.check-block-2 .dropdown').attr("id","dropdown-1");
	
	$("#dropdown").change(function(){						
		$('.check-block-1 .down__row__choice input').hide();
		id = $('#dropdown option:selected').val();	 
		$('#'+id).show();					
	});     

	$("#dropdown-1").change(function(){						
		$('.check-block-2 .down__row__choice input').hide();  
		id = $('#dropdown-1 option:selected').val();	 
		$('#'+id).show();					
	});  

	$('.close-yellow').on('click', function(){
		$('.attention__block').slideUp('fast');
		return false;
	});

	$('.reference').mouseenter(function(){
		$(this).parent().addClass('help-open');
	});

	$(document).bind("mouseover",function(e) {
	    if (!$(e.target).closest(".form-block__name").length) {
	        $('.form-block__name').removeClass('help-open');
	    }
	    e.stopPropagation();
	});
	$(document).bind("mouseover",function(e) {
	    if (!$(e.target).closest("label").length) {
	        $('label').removeClass('help-open').prev().next().removeClass('help-open');
	    }
	    e.stopPropagation();
	});

	$('.fancy').fancybox();
});



var ww = document.body.clientWidth;

$(window).bind('resize orientationchange', function() {
	ww = document.body.clientWidth;
});

var adjustMenu = function() {
	if (ww < 1170) {
		$(".header__nav li").unbind('mouseenter mouseleave');
		$(".header__nav li a.parent").unbind('click').bind('click', function(e) {
			e.preventDefault();
			$(this).parent("li").toggleClass("hover");
		});
	}
	else if (ww >= 1170) {
		$(".header__nav li").removeClass("hover");
		$(".header__nav li a").unbind('click');
		$(".header__nav li").unbind('mouseenter mouseleave').bind('mouseenter mouseleave', function() {
		 	$(this).toggleClass('hover');
		});

	}
}


var wrapper = $( ".file_upload" ),
      inp = wrapper.find( "input" ),
      btn = wrapper.find( "button" ),
      lbl = wrapper.find( "div" );
  btn.focus(function(){
      inp.focus()
  });
  inp.focus(function(){
      wrapper.addClass( "focus" );
  }).blur(function(){
      wrapper.removeClass( "focus" );
  });
var file_api = ( window.File && window.FileReader && window.FileList && window.Blob ) ? true : false;

  inp.change(function(){
      var file_name;
      if( file_api && inp[ 0 ].files[ 0 ] )
          file_name = inp[ 0 ].files[ 0 ].name;
      else
          file_name = inp.val().replace( "C:\\fakepath\\", '' );

      if( ! file_name.length )
          return;

      if( lbl.is( ":visible" ) ){
          lbl.text( file_name );
          
      }else
          btn.text( file_name );
  }).change();