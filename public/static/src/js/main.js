var countryMask;
var oldval;
var CaptchaCallback = function() {
    grecaptcha.render('forgot-recaptcha', {'sitekey' : '6LdfnmcUAAAAAE8CuJRGq5vbMU51bvFLLfvpnM-u'});
	grecaptcha.render('r1-recaptcha', {'sitekey' : '6LdfnmcUAAAAAE8CuJRGq5vbMU51bvFLLfvpnM-u'});
	grecaptcha.render('r2-recaptcha', {'sitekey' : '6LdfnmcUAAAAAE8CuJRGq5vbMU51bvFLLfvpnM-u'});
	grecaptcha.render('r3-recaptcha', {'sitekey' : '6LdfnmcUAAAAAE8CuJRGq5vbMU51bvFLLfvpnM-u'});
};


$(function(){
	$(window).on('load', function () {
	    var $preloader = $('#preloader'),
	        $svg_anm   = $preloader.find('.svg_anm');
	    // $svg_anm.fadeOut();
	    $preloader.delay(800).fadeOut('slow');
	});
});

$(function(){
	
	$('.show-dislocation').click(function(e) {
		e.preventDefault();
		
		var location = $(this).data('location');
		$(this).parent().html(location);
	});
	
	$('.open-calc').click(function(e){
		e.preventDefault();
		
		$('.header__down__btn').first().click();
	});

	 $('.option').click(function() {
         var id = $(this).attr('for'); 
         if($('#'+id).prop('checked') == true) { 
            setTimeout(function() {$('#'+id).prop('checked',false); }, 100);
         } 
     });

	$('#jsPackVolume').keydown(function(e) {
		oldval = + $(this).val();
	});
	$('#jsPackVolume').keyup(function(e) {
		var self = this;
		setTimeout(function() {
			var val = + $(self).val();
			var val2 = + $('#jsVolume').val();
			console.log(val>val2);
			if(val > val2) {
				alert('объем и вес упаковываемого груза, не может быть больше объема, рассчитываемого для перевозки');
				$(self).val(val2);
			}
		}, 50);
		
	});

	$('.send-order').click(function(e){
		e.preventDefault();

		var id = $(this).data('id');
		var self = this;
		$.post('/ru/cabinet/orders/send',{id:id},function(data) {
			location.reload();
		});
	});

	$('.country-reg').change(function(e) {
            var country = $(this).val();
            if(country == 'Казахстан') {
                $('#iin-title').html('ИИН <sup>*</sup>');
            } else {
                $('#iin-title').html('ИНН <sup>*</sup>');
            }
        }).change();

	$('.form-block__option .close').click(function(e) {
		e.preventDefault();
		$('.form-block__option input').val('');
		$('.form-block__option select').val('Выбрать...');
		$('.form-block__option__check input[type=checkbox]').prop('checked',false);
		
	});
	$('.turn-on').click(function(e) {
		e.preventDefault();

		var id = $(this).data('id');
		var type = $(this).data('type');
		var self = this;
		if($(this).hasClass('off')) {
			$.post('/ru/cabinet/orders/notification', {id: id, type: type, status: 0}).done(function() {
	          	$(self).text('вкл'); 
	          	$(self).toggleClass('off');	
	       	});	
		} else {
			$.post('/ru/cabinet/orders/notification', {id: id, type: type, status: 1}).done(function() {
	          	$(self).text('выкл');
	          	$(self).toggleClass('off');
	       	});	
		}
	});

	 $('#gruz_stoimost').keyup(function(e) {
        if(!$(this).val().length) {
            $('select[name=gruz_stoimost_currency]').attr('required',false);
            $('.gruz-need').find('sup').remove();
        } else {
            $('select[name=gruz_stoimost_currency]').attr('required','required');
            if(!$('.gruz-need sup').length)
            $('.gruz-need').append($('<sup>').text('*'));
        }
    });

	$("#need_pack").click(function(e) {
		$('#jsPackVolume').val($('#jsVolume').val());
		$('#jsPlacesAmount').val(1);
	});

	$('.header__phones__col__row a, .footer__lurking ul a').each(function(index,el){
		$(this).attr('href','tel:+'+$(this).text().replace(/\D+/g,''));
	});

	$('.header__phones .arrow').on('click', function(){
		if ($(this).siblings('ul').is(':visible')) {
			$(this).siblings('ul').slideUp();
		} else {
			$('.header__phones ul').slideUp();
			$(this).siblings('ul').slideDown();
		}
		return false;
	});

	$('.login-link').click(function(e) {
		e.preventDefault();
		$('#login').fadeIn();
	});

	$('.contact-city').click(function(e) {
		e.preventDefault();
		var id = $(this).data('id');

		$.get('/ru/getContactCity?id='+id).done((res)=>{
			$(this).parent().addClass('active').siblings().removeClass('active');
			var result = JSON.parse(res);

			$('#c-image').attr('src', result.image);
			$('#c-address').html(result.address);
			$('#c-phone').html(result.phone);
			$('#c-email').attr('href','mailto:'+result.email).html(result.email);
			$('#c-text').html(result.text);
			$('#c-map').html(result.map);
			$('#c-rezhim').html(result.rezhim);
		});;
	});
	$('.contacts__col__nav').find('li:first-child').find('.contact-city').click();

	/* Код масок */
    $.get('/ru/getCountryMask').done((res)=>{
		countryMask = JSON.parse(res);
		/* ставка маски для телефона при выборе страны */
		$('#dropdown-1, #dropdown-2, #dropdown-3').change(function(e){
			var country = $(this).val();
			var mask = countryMask[country];
			if(mask) {
				$('.input-phone').inputmask(mask);
			} else {
                $('.input-phone').unmask();
			}
		}).change();
    });

    /* Положить в архив */
    $('.to-archive').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post('/ru/cabinet/orders/archive/move/'+id).done(function() {
            $('#order-'+id).remove();
        });
    });

    /* Положить в архив */
    $('.to-myorder').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post('/ru/cabinet/orders/list/move/'+id).done(function() {
            $('#order-'+id).remove();
        });
    });

    $('#login-form').submit(function () {

        var $form = $(this);

        $('div.login-error').hide();

        $.post('/ru/cabinet', $form.serialize()).done(function (data) {
            if (data === '1') {
            	if($('.fromCalc').length) {
            		location.href="/ru/cabinet/orders/new?fromCalc=true";
            	} else {
            		location.href="/ru/cabinet/";
            	}
            }else{
                $('#login-error-'+data).show();
            }
        });

        return false;
    });

    $('#help-calc-form').submit(function () {
		
        var $form = $(this);
		var $btn = $form.find('input[type=submit]');
		var val = $btn.val();
		$btn.attr('disabled',true).val('Идет отправка...');
		
        $.post('/ru/help-calc', $form.serialize()).done(function (data) {
            $form.find("input[type=text]").val("");
            $('.modal').fadeOut();
            setTimeout(function () {
                alert('Заявка отправлена!');
				$btn.removeAttr('disabled');
				$btn.val(val);
            }, 1000);
        });

        return false;
    });
	
 	$('#ask-form').submit(function () {

        var $form = $(this);
		var $btn = $form.find('input[type=submit]');
		var val = $btn.val();
		$btn.attr('disabled',true).val('Идет отправка...');

        $.post('/ru/help-calc/ask', $form.serialize()).done(function (data) {
            $form.find("input[type=text]").val("");
            $('.modal').fadeOut();
            setTimeout(function () {
                alert('Заявка отправлена!');
				$btn.removeAttr('disabled');
				$btn.val(val);
            }, 1000);
        });

        return false;
    });

    $('#simple-order-form').submit(function () {

        var $form = $(this);
		var $btn = $form.find('input[type=submit]');
		var val = $btn.val();
		$btn.attr('disabled',true).val('Идет отправка...');

        $.post('/ru/simple-order', $form.serialize()).done(function (data) {
            $form.find("input[type=text], input[type=email], textarea").val("");
            alert('Заявка отправлена!');
			$btn.removeAttr('disabled');
			$btn.val(val);
        });

        return false;
    });

     $(window).scroll(function() {
       var the_top = jQuery(document).scrollTop();
       if (the_top > 150) {
       		$('.header__down').addClass('transform');
       		$('.header__down__form-block').addClass('scroll');
       } else {
	       	$('.header__down').removeClass('transform');
	       	$('.header__down__form-block').removeClass('scroll');
        }
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
	// $('.input-code').mask('9999');
	// $('.input-number').mask('999');
	//$('.input-phone').mask('+7 (999) 999-99-99');
	//$('.input-other').mask('+ (999) 999-99-99');

	//$('#login .input-phone').mask('+9 (999) 999-99-99');

	adjustMenu();

	$('.btn-calc').on('click', function(){
		$('.btn-calc').toggleClass('active');
		if($(this).hasClass('active')) {
			$('.header__down__form-block').addClass('header__down__form-block-open');
		} else {
			$('.header__down__form-block').removeClass('header__down__form-block-open');
		}
		return false;
	});

	$('.btn-help').on('click', function(){
		$('#help-calc').fadeIn();
		return false;
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

	// $('.header__reg').on('click', function(){
	// 	$('#registration').fadeIn();
	// 	$('#login').fadeOut();
	// 	return false;
	// });



	$('.header__log').on('click', function(){
		$('#login').fadeIn();
		return false;
	});

	$('.plan').on('click', function(){
		$('#map').fadeIn();
		return false;
	});

	$('.modal__block .forgot').on('click', function(){
        $('.down__row__sms').hide();
        $('.down__row__num, .del').show();
		$('.forgot-form').show().prev('form').hide();
		$('.modal__close').on('click', function(){
			$('.forgot-form').hide('2000').prev('form').show('2000');
		});
	});

	$('.modal__block .back').on('click', function(){
		$('.login-form').show().next('form').hide();
        $('.down__row__sms').hide();
        $('.down__row__num, .del').show();
	});

	$('.forgot-form .btn').on('click', function(e){
		e.preventDefault();
		if($('.down__row__sms').is(':visible')) {
			var phone = $('#forgot-phone').val();
			var code = $('#forgot-code').val();
			var recaptcha = $('#forgot-recaptcha').find('textarea[name=g-recaptcha-response]').val();
            $.post('/ru/registration/forgot/check', { phone: phone, code:code, recaptcha: recaptcha }).done(function(res) {
                res = JSON.parse(res);
                if(res.status) {
                    $('.forgot-form').submit();
                } else {
                    alert(res.message);
                }
            });
            grecaptcha.reset()
		} else {
            var phone = $('#forgot-phone').val();
            var recaptcha = $('#forgot-recaptcha').find('textarea[name=g-recaptcha-response]').val();
            $.post('/ru/registration/forgot', { phone: phone, recaptcha: recaptcha }).done(function(res) {
                res = JSON.parse(res);
                if(res.status) {
                    $('.forgot-form').attr('action','/ru/registration/forgot/'+res.activation_url);
                    $('.down__row__sms').show();
                    $('.down__row__num, .del').hide();
                } else {
                    alert(res.message);
                }
            });
            grecaptcha.reset()
		}
	});

	/* по умолчанию открывает авторизацию */
	$('#calc-form').on('click', '.to-btn', function(e){
		var authStatus = $(this).data('auth-status');
		if(authStatus) {

		} else {
			e.preventDefault();
			$('#login-form').append('<input type="hidden" class="fromCalc" name="fromCalc" value="true">');
			$('#login').fadeIn();
		}
	});
	
	/* по умолчанию открывает авторизацию */

	$('.openMap').on('click',function(){
        var aID = $(this).attr('href');
        $(''+aID).fadeIn();
    });

	$('.question-answer__left li a').on('click', function(){
		var question = $(this).attr('href');
		$('.question-answer__left li').removeClass('active');
		$('.question-answer__right div').hide();
		$(this).parent().addClass('active');
        $(''+question).show();
        return false;

	});

	$('.open--btn').on('click', function(){
		var modal = $(this).attr('href');
		$(''+modal).fadeIn();
		return false;
	});

	$('.form-block__price').hide();

	// $('.form-block__row__choice li a').on('click', function(){
	// 	$('.form-block__row__choice li').prev().removeClass('active');
	// 	$(this).parent().addClass('active').next().removeClass('active');
	// 	if ($('.choice-block-open').hasClass('active')) {
	// 		$('.form-block__row__choice-block').slideDown();
	// 	} else {
	// 		$('.form-block__row__choice-block').slideUp();
	// 	}
	// 	return false;
	// });

	$('#tags').tagsInput({
		'height':'39px',
   		'width':'100%',
   		'defaultText':'77777-zzzzzz',
   		'maxChars' : 1,
   		'placeholderColor' : '#9fa5aa'
	});

	$('#tags_tag').mask('99999-aaaaaa', {
		placeholder: "77777-zzzzzz",
		definitions: {
			'9': "[0-2]",

			'a': "[A-Za-z]",

			'*': "[A-Za-z0-9]"
		}

	});
	
	$('.form-block__option').hide();
	$('header .form-block__option .close').on('click', function(){
		$('header .form-block__option').hide();
		$('.calc-size').css('display', 'inline-block');
	});
	$('.calc-size').on('click', function(e){
		e.preventDefault();
		$('header .form-block__option').css('display', 'inline-block');
		$('.form-block__option__block .input-number:first-of-type').focus();
		$(this).hide();
	});	

	$('.modal__close').on('click', function(){
		$('.modal').fadeOut();
		return false;
	});

	$('.nav-btn').on('click', function(){
		if($('.header__nav').is(':visible')) {
			$('.header__nav').slideUp();
		} else {
			$('.header__nav').slideDown();
		}
		$(this).toggleClass('open');
		return false;
	});

	$('.phones-btn').on('click', function(){
		$('.header__phones').fadeIn().addClass('open');
		$(document).bind("touchstart",function(e) {
		    if (!$(e.target).closest(".header__phones.open").length) {
		        $('.header__phones').slideUp().removeClass('open');
		    }
		    e.stopPropagation();
		});
		return false;
	});
	$('.header__phones__btn').on('click', function(){
		$('.header__phones').fadeOut();
		return false;
	});


	$('.modal__block__check input[type="radio"]').change(function() {
		$('.dropdown').prop('selectedIndex',0);
		$('.down__row__choice input').hide();
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
  	}).change();

  	$('#calc-form #need_pack').change(function() {
  		if($('#need_pack').is(':checked')) {
  			$(this).parent().addClass('choice-block-open');
  		} else {
  	    	$(this).parent().removeClass('choice-block-open');
  		}
  	});

  	$('.down__row__choice input').hide();

  	$('.dropdown').change(function(){
  		if($('.dropdown option:selected').text().includes('Другое')) {
  			$(this).parents('.down__row__choice').children('input').show().attr("required", "true");
  		} else {
 			$('.down__row__choice input').hide().removeAttr("required", "false");
  		}
  	});
  	// $('#dropdown-2').change(function(){
  	// 	if($('#dropdown-2 option:selected').text() == 'Другое') {
  	// 		$(this).parents('.down__row__choice').children('input').show().attr("required", "true");
  	// 	} else {
 		// 	$('.down__row__choice input').hide().removeAttr("required", "false");
  	// 	}
  	// });

  	$('.add__phone').on('click', function(){
  		$("#block__input-phone .input-other, #block__input-phone .input-phone").clone().prependTo("#block__input-phone-1");
  		return false;
  	});


  	
  	$('.check-block-1 .dropdown').attr("id","dropdown");
  	
  	$('.check-block-2 .dropdown').attr("id","dropdown-1");
	
	// $("#dropdown").change(function(){						
	// 	$('.check-block-1 .down__row__choice input').hide();
	// 	id = $('#dropdown option:selected').val();	 
	// 	$('#'+id).show();					
	// });     

	// $("#dropdown-1").change(function(){						
	// 	$('.check-block-2 .down__row__choice input').hide();  
	// 	id = $('#dropdown-1 option:selected').val();	 
	// 	$('#'+id).show();					
	// });  

	$('.close-yellow').on('click', function(){
		$('.attention__block').slideUp('fast');
		return false;
	});

	$('.reference').hover(function(){
    $(this).parent().addClass('help-open');
    $('.header__down__form-block').css('overflow', 'visible');
  }, function() {
    $(this).parent().removeClass('help-open');
    $('.header__down__form-block').removeAttr('style');
  });

	$(document).on("mouseover",function(e) {
	    if (!$(e.target).closest(".form-block__name").length) {
	        $('.form-block__name').removeClass('help-open');
	    }
	    e.stopPropagation();
	});
	$(document).on("mouseover",function(e) {
	    if (!$(e.target).closest("label").length) {
	        $('label').removeClass('help-open').prev().next().removeClass('help-open');
	    }
	    e.stopPropagation();
	});

	$('.header__phones__btn').on('click', function(){
		if($('.header__phones__block').is(':visible')) {
			$(this).siblings('.header__phones__block').fadeOut().removeClass('open');
		} else {
			$(this).siblings('.header__phones__block').fadeIn().addClass('open');
		}
		$(document).on("click",function(e) {
		    if (!$(e.target).closest(".header__phones__block.open").length) {
		        $('.header__phones__block').fadeOut().removeClass('open');
		    }
		    e.stopPropagation();
		});
		return false;
	});

	$('.container__right__btn').on('click', function(){
		if ($(this).next('.container__right__nav').is(':visible')) {
			$(this).next('.container__right__nav').slideUp();
			$(this).removeClass('open');
		} else {
			$(this).next('.container__right__nav').slideDown();
			$(this).addClass('open');
		}
		return false;
	})

	$('.contacts__col__nav li a').click(function(){
		var data = $(this).data('id');
		var elem = $(this);
		$.ajax({
			url: '/Faq.php',
			data: "id="+data,
			type: "POST",
			success: function(data){
				$('.contacts__col__changing').html(data);
				$('.contacts__col__nav ul li').removeClass('active');
				elem.parent().addClass('active');
			}
		});
		return false;
	});
	$('.fancy').fancybox();

	var delay = 0;
	var offset = 150;

	document.addEventListener('invalid', function(e){
	   $(e.target).addClass("invalid");
	   $('html, body').animate({scrollTop: $($(".invalid")[0]).offset().top - offset }, delay);
	}, true);
	document.addEventListener('change', function(e){
	   $(e.target).removeClass("invalid")
	}, true);

	// $('.input-phone').mask('+9 (999) 999-9999');


});


var ww = document.body.clientWidth;

$(window).bind('resize orientationchange', function() {
	ww = document.body.clientWidth;
});

var adjustMenu = function() {
	if (ww < 1170) {
		$(".header__nav li").unbind('mouseenter mouseleave');
		$(".header__nav .nav > li:nth-of-type(1)> a").unbind('click').bind('click', function(e) {
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


