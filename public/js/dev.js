let whatsAppElem   = $('#js-whatsapp');
let mobileCartElem = $('#js-mobile-cart');
let productsCount  = $('.headerRycleCount').eq(0).text();

if (productsCount > 0) {
    whatsAppElem.css('display', 'none');
    mobileCartElem.css('display', 'inline-block');
} else {
    whatsAppElem.css('display', 'block');
    mobileCartElem.css('display', 'none');
}

$('#js-accept-cookie').click(function ($e) {
    $('#js-use-cookie').css('display', 'none');
    let expires = Date.now() + (86400 * 30 * 1000);
    setCookie('acceptCookie', true, {path: '/', expires: new Date(expires)});
});

if (!getCookie('acceptCookie')) {
    $('#js-use-cookie').css('display', 'block');
}

var constructorModalReload = function(){
    $.ajax({
        url: '/ru/constructor-modal',
        data:'',
        type:"GET",
        success:function(data){
            $('.desktop-constructor-modal-container').html(data);
        }
    });
}

var inputSave = function(form){
    form.find('[type="text"]').each(function(){
        var el = $(this);
        var name = el.attr('name');
        var val = el.val();
        if(name == 'promo') return;
        localStorage.setItem(name,val);
    });

    form.find('[type="tel"]').each(function(){
        var el = $(this);
        var name = el.attr('name');
        var val = el.val();
        localStorage.setItem(name,val);
    });

    form.find('[type="email"]').each(function(){
        var el = $(this);
        var name = el.attr('name');
        var val = el.val();
        localStorage.setItem(name,val);
    });
}

var inputSet = function(form){
    $.ajax({
        url: '/ru/cabinet/auth',
        data: {},
        type: "POST",
        success: function(data){
            if(data == '0'){
                form.find('[type="text"]').each(function(){
                    var el = $(this);
                    var name = el.attr('name');
                    if(name == 'promo' || name == 'preorder') return;
                    var val = localStorage.getItem(name);
                    el.val(val);
                });

                form.find('[type="tel"]').each(function(){
                    var el = $(this);
                    var name = el.attr('name');
                    var val = localStorage.getItem(name);
                    el.val(val);
                });

                form.find('[type="email"]').each(function(){
                    var el = $(this);
                    var name = el.attr('name');
                    var val = localStorage.getItem(name);
                    el.val(val);
                });
            }
        }
    });
}

function promocheck(promo){
    $('#promo-alert').fadeOut();
    $.ajax({
        url: '/ru/cart/promo',
        data: {code: promo},
        type: "POST",
        dataType: 'json',
        success: function(data) {
            if(data.result == false){
                alert('Промо-код не найден');
                return false;
            }

            setTimeout(() => location.reload(true), 100);
        }
    });
}


function isShown(){
    $.ajax({
        url:'/ru/isshown',
        data:{},
        type:"POST",
        datatype:'json',
        success:function(data){
            if(data == 'false') return false;
            var $modal = $('#modal--not--work');
            $modal.removeClass('in');
            $modal.hide();
        }
    });
}

function setShown(){
    $.ajax({
        url:'/ru/setshown',
        data:{},
        type:"POST",
        datatype:'json',
        success:function(data){
            console.log(data);
            var $modal = $('#modal--not--work');
            $modal.removeClass('in');
            $modal.hide();
        }
    });
}

// isShown();
// var isShown = localStorage.getItem('isShown');
// if(isShown == 'true'){
//     $('.modal--recovery').removeClass('in');
//     $('.modal--recovery').hide();
// }

$(function(){

    $('.catalog-item-types [type="radio"]:first').attr('checked','checked');

    $(document).on('keydown input','[name="preorder"]',function(){
        return false;
    });

    $(document).on('click','.redirectBtn',function(){
        var $el = $(this);
        var $url = $el.data('href');
        window.location.href = $url;
        return false;
    });

    $(document).on('click','.closeModalBtn',function(){
        // var $el = $(this);
        // localStorage.setItem('isShown','true');
        setShown();
        return false;
    });



    // var days = 2;
    // var maxDate = new Date(Date.now() + days*24*60*60*1000);

    var maxDate = new Date();
    maxDate.setHours(23);
    maxDate.setDate(maxDate.getDate()+ 2);

    var date = new Date(Date.now() + 60*60*1000);
    // var date = new Date();
    date.setMinutes(0);
	var currentHour = date.getHours();
	var currentDay = date.getDate();



    $( ".datepicker-area" ).datepicker({
        autoClose: true,
        // timepicker: true,
        minDate: date,
        maxDate: maxDate,
        // minHours: currentHour + 2,
        // maxHours: 23,
        // minutesStep: 30,
        // maxMinutes: 30,
        // minMinutes: 0,
        // setMinutes: 0,
        onSelect: function(fd, d, picker) {
            // $('#fast_order').removeClass('active').find('input').attr('disabled', true);
            if (!d) return;
            var fullDate = d;
            // alert(fullDate);
            var selectedDay = d.getDate();


            $(document).on('keydown input','[name="preorder"]',function(){
                return false;
            });

            if(selectedDay == currentDay){

                $('#otherTimes').hide();
                $('#currentTimes').show();

                $('.time_item').removeClass('active');
                var input = $('#current_item');
                $(input).addClass('active');
                $('.time_input').val('Sofort');


            	 // picker.update({
              //       setMinutes: 0,
              //      minHours: currentHour + 1
              //   });

              //    if(fullDate.getHours() <= (currentHour + 1) ){
              //       $('#fast_order').addClass('active').find('input').attr('disabled', false);;
              //   }else{
              //       $('#fast_order').removeClass('active').find('input').attr('disabled', true);;
              //   }
                 
            	
            }else{
                $('#otherTimes').show();
                $('#currentTimes').hide();

                $('.time_item').removeClass('active');
                var input = $('.current_item_link');
                $(input).addClass('active');
                $('.time_input').val($(input).data('hour1') + ' - ' + $(input).data('hour2'));

                
                
            	// picker.update({
             //       minHours: 9
             //    });
     		}

            var timeBox = $('.time_item.active');
            var time = $(timeBox).data("hour1");
            if($(timeBox).is('#current_item')){
                time = $(timeBox).data('current');
            }
            $('#preInput').val(fd + ' ' + time);
     		 // console.log(fullDate);
     	}
           
    });



 	$('.time_item').on('click',function(e){
        e.preventDefault();
        var hour1 = $(this).data('hour1');
        var hour2 = $(this).data('hour2');

        if(hour1 || hour2){
             $('.time_input').val('bit ' + hour1 + ' uhr ' + hour2);
             $('[name="time_from"]').val(hour1);
             $('[name="time_to"]').val(hour2);
        }else{
           $('.time_input').val('Sofort');
           $('[name="time_from"]').val($(this).data('current'));
             $('[name="time_to"]').val('');
        }
        $('.time_item').removeClass('active');
        $(this).addClass('active');

        $('#modal--time').modal('hide');
       
    });

    $('#find_street').keyup(function(){
        $('.suggestions').show();
    });

    $(document).on('click','.suggestions__item-link',function(){
        var $el = $(this);
        var $city = $el.find('.suggestions__item-link-description').text();
        var $street = $el.find('.suggestions-message').text();
        $('#find_street').val($city + ', ' + $street);
        $('.suggestions').hide();
        return false;
    });

   


    $('.t_tipe_close').click(function(){
        $(this).parents('.t_tipe').fadeOut();
    });

    $('.promo_box input').keyup(function(){
        $(this).parents('.promo_box').find('.t_tipe').fadeOut();
    });
    $(document).on('click','.sumCheck',function(){
        var total = $('[data-output="result"]:first').text();
        total = total.replace(' ','');
        total = parseInt(total) * 100;
        if(total >= 2500){
            $('.delivery-options').html(
                '<input type="radio" name="delivery" id="delivery-1" data-type="1" value="Доставка" checked />'+
                '<label class="radio" for="delivery-1">Lieferung</label>'+
                '<input type="radio" name="delivery" id="delivery-2" data-type="2" value="Самовывоз" />'+
                '<label class="radio" for="delivery-2">Abholung</label>'
            );
            $('.deliveryHide').show();
            $('[data-delivery="1"]').show();
            $('[data-delivery="2"]').hide();
        }else{
            $('.delivery-options').html(
                '<input type="radio" name="delivery" id="delivery-2" data-type="2" value="Самовывоз" checked />'+
                '<label class="radio span_block" for="delivery-2">Abholung</label>'
            );
            $('.deliveryHide').hide();
            $('[data-delivery="2"]').show();
            $('[data-delivery="1"]').hide();
        }

         $([document.documentElement, document.body]).animate({
            scrollTop: $(".cart-title").offset().top
        }, 600);

    });

    $('.promoResetBtn').click(function(){
        $.ajax({
            url: '/ru/cart/resetpromo',
            data: {},
            type: "POST",
            success: function(data){
                location.reload(true);
            }
        });
        return false;
    });

    inputSet($('.orderForm'));

    $('.promoCheckBtn').click(function(){
        var promo = $('[name="promo"]').val();
        if(!promo){
            alert('Введите промо-код');
            return false;
        }
        promocheck(promo);
        return false;
    });

    $('.sm_logo').click(function (e) {
        e.preventDefault();
        $('body,html').animate({
            scrollTop: 0
        }, 700);
        return false;
    });

    $('#surrender').change(function(){
        if($(this).is(':checked')){
            $('.surrender').prop('disabled', true).val('');
        }else{
            $('.surrender').prop('disabled', false);
        }
    });

    $(document).on('keyup','[street-search="true"]',function(){
        var $el = $(this);
        var $val = $el.val();
        $('#address-error').hide();
        $.get('/ru/city/',{w:$val},function(data){
            if(data.length == 0 || data == '""'){
                $el.val('');
                $('#address-error').show();
            }
            if(data != '""') $('.suggestions').html(data);
        });
    });

    setTimeout(function(){
        $('#post_map').html('<script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A1d7a2c8bd010df56a5104f14643adc4a83c3292c7bdebafecc91a522c390a3e6&amp;width=100%&amp;height=452&amp;lang=ru_RU&amp;scroll=true"></script>');
    }, 4000);

    

    $('.recoverForm').submit(function(){
        var form = $(this);
        var data = form.serialize();
        $.ajax({url:'/ru/passrecover',data:data,type:"POST",dataType:'json',success:function(data){
            if(data.error.length > 0){
                form.find('.result').html(data.error);
            }else{
                form.find('.result').html(data.message);
            }
        }});
        return false;
    });

    // $('[name="payment"]').change(function(){
    //     var el = $(this);
    //     var val = el.val();
    //     if(val == 'Картой курьеру') $('.group--surrender').hide();
    //     else $('.group--surrender').show();
    // });

    var price = $('.item--halve [name="price"]').val();
    $('.item--halve [data-output="amount"]').text(number_format(price/100, 2, ".", " "));
   
    var width = $(window).width();
    if(width < 769){

    	var $window = $(window),
        $body = $("body"),
	        $modal = $(".modal"),
	        scrollDistance = 0;

	    $modal.on("show.bs.modal", function() {
	        // Get the scroll distance at the time the modal was opened
	        scrollDistance = $window.scrollTop();

	        // Pull the top of the body up by that amount
	        $body.css("top", scrollDistance * -1);
	    });

	    $modal.on("hidden.bs.modal", function() {
		    // Remove the negative top value on the body
		    $body.css("top", "");

		    // Set the window's scroll position back to what it was before the modal was opened
		    $window.scrollTop(scrollDistance);  
		});

         $("#trigger_radio2").trigger("click");
        $('.mob_show_c_control').click(function(){
            $('.m_fixed').fadeIn();
        });
        $('.mob_hide_c_control').click(function(){
            $('.m_fixed').hide();
        });
        $('.mobile-fixed ul li:not(:last-child)').addClass('prevent');
        $('.mobile-fixed ul li:nth-child(7)').removeClass('prevent');
        $(window).scroll(function() {
          var scrollTop = $(window).scrollTop();
          if ( scrollTop > $('#combo-block').offset().top - 80 ) { 
            // alert();
            $('.mobile-fixed').addClass('active');
          }else{
            $('.mobile-fixed').removeClass('active');
          }

            $links = $('.prevent a');
            $links.each(function () {
                var $currLink = $(this),
                    $href = $($currLink).attr("href");
                    $refElement = $href.substring($href.indexOf('#'));
                if ($($refElement).offset().top <= scrollTop + 80 && $($refElement).offset().top + $($refElement).height() > scrollTop + 80) {
                    $links.removeClass("active");
                    $currLink.addClass("active");
                } else {
                    $currLink.removeClass("active");
                }
            });

        });

    }else{
         $("#trigger_radio").trigger("click");
    }
    if($('span').is('#this_index')){
        $('.nav-main ul li:not(:last-child)').addClass('prevent');
        $('.nav-main ul li:nth-child(7)').removeClass('prevent');
    }
    $('.prevent a').click(function(e){
        // $('.prevent a').removeClass('active');
        // $(this).addClass('active');
         e.preventDefault();

         if(width < 769){
         	if($('body').hasClass('show--nav')){
         		$('.btn--nav').trigger('click');
         	}
         }
        var href = $(this).attr('href');
        var hash = href.substring(href.indexOf('#'));
        $([document.documentElement, document.body]).animate({
            scrollTop: $(hash).offset().top - 70
        }, 700);  
    })
    // $('.showData').click(function(e){
    //     e.preventDefault();
    //     var id = $(this).data('id');
    //     var lang = $('body').data('lang');
    //     $.ajax({
    //         url:'/'+ lang +'/product-modal/',
    //         type:"POST",
    //         data: {id: id},
    //         success:function(data){
    //             var block = $('#product-modal');
    //             $(block).html(data);
    //             $(block).modal();
    //         }
    //     });
    //     return false;
    // });

    $('.showCombo').click(function(e){
        e.preventDefault();
        var id = $(this).data('id');
        var lang = $('body').data('lang');
        $.ajax({
            url:'/'+ lang +'/combo-modal/',
            type:"POST",
            data: {id: id},
            success:function(data){
                $('#combo-modal').html(data);
                $('#combo-modal').modal();
            }
        });
        return false;
    });
    
    $('.callbackForm').submit(function(){
        var form = $(this);
        $.ajax({url:'/ru/contacts/send',data:form.serialize(),type:"POST",dataType:'json',success:function(data){
            if(data.error.length > 0){
                form.find('.formResult').html(data.error);
            }else{
                form.find('.formResult').html();
                form.find('input[type=text]').val('');
                form.find('input[type=tel]').val('');
                form.find('input[type=email]').val('');
                form.find('textarea').val('');
                form.find('.modalShow').trigger('click');
            }
            $.each(captchas,function(i,e){
                grecaptcha.reset(e);
            });
        }});
        return false;
    });

    $('.halfChangeCount button').on('click',function(){
        var el = $(this);
        var countEl = el.parents('.halfChangeCount').find('[data-output="count"]');
        var count = countEl.text();
        var id = countEl.data('id');
        $.ajax({
            type: "POST",
            url: '/ru/cart/halfpizzachange',
            data: {
                id: id,
                count: count
            },
            dataType: 'html',
            success: function(data) {
                $('.completeHalfAmount[data-id="'+id+'"]').html(data);
                updateRycleSum();
            }
        });
    });

    $(document).on('click','.halfPizzaRemove',function(){
        var el = $(this);
        var id = el.data('id');
        $.ajax({
            type: "POST",
            url: '/ru/cart/halfpizzaremove',
            data: {
                id: id
            },
            dataType: 'json',
            success: function(data) {
                if(data.count == 0){
                    window.location.reload();
                }
            }
        });
        return false;
    });

    $(document).on('click','.halfPizzaAdd',function(){
        var el = $(this);
        var block = $('.halves-item');
        var leftPartID = $('.left_disable [name=halv_checkbox]').data('name');
        var rightPartID = $('.right_disable [name=halv_checkbox]').data('name');
        var size = 35;
        var typeID = $('.halves-values [name=type]:checked').attr('id');
        var type = $('.halves-values label[for='+typeID+']:first').text();
        // var price = $('.halvesTotal [data-output="result"]:first').text();
        // var ingsRemoveLeft = [];
        // $('[data-output="ings-left"] .ing_active').find('.ingRemove').each(function(){
        //     var ingName = $(this).data('name');
        //     ingsRemoveLeft.push('-'+ingName);
        // });
        // var ingsRemoveRight = [];
        // $('[data-output="ings-right"] .ing_active').find('.ingRemove').each(function(){
        //     var ingName = $(this).data('name');
        //     ingsRemoveRight.push('-'+ingName);
        // });
        $.ajax({
            type: "POST",
            url: '/ru/cart/halfpizzaadd',
            data: {
                left: leftPartID,
                right: rightPartID,
                size: size,
                type: type,
                // price: price,
                // leftIngs: ingsRemoveLeft.join(', '),
                // rightIngs: ingsRemoveRight.join(', ')
            },
            dataType: 'json',
            success: function(data) {
                el.text('Hinzugefügt');
                el.removeClass('btn--primary');
                el.addClass('btn--green');
                el.css('pointer-events','none');

                location.reload();

                // setTimeout(function(){
                //     $('.modal').modal('hide');
                //     updateRycleSum();
                //     constructorModalReload();
                // }, 1500);

                // $('.halves-item input').removeAttr('checked');
                // $('.halves-item').removeClass('disabled');
                // $('.halves-item').removeClass('left_disable');
                // $('.halves-item').removeClass('right_disable');
                
                // el.removeClass('btn--primary');
                // el.addClass('btn--green');
                // el.text('Hinzugefügt');
                // el.css('pointer-events','none');
                // setTimeout(function(){
                //     el.addClass('btn--primary');
                //     el.removeClass('btn--green');
                //     el.text('In den Warenkorb');
                //     el.css('pointer-events','auto');
                //     $('.modal').modal('hide');
                //     updateRycleSum();
                // }, 1000);
            }
        });
        return false;
    })

    $('.comboChangeCount button').on('click',function(){
        var el = $(this);
        var countEl = el.parents('.comboChangeCount').find('[data-output="count"]');
        var count = countEl.text();
        var id = countEl.data('id');
        $.ajax({
            type: "POST",
            url: '/ru/cart/combochange',
            data: {
                id: id,
                count: count
            },
            dataType: 'html',
            success: function(data) {
                $('.completeComboAmount[data-id="'+id+'"]').html(data);
                updateRycleSum();
            }
        });
    });

    $('.comboRemove').click(function(){
        var el = $(this);
        var id = el.data('id');
        $.ajax({
            type: "POST",
            url: '/ru/cart/comboremove',
            data: {
                id: id
            },
            dataType: 'json',
            success: function(data) {
                window.location.reload();
            }
        });
        return false;
    });

    var getIngs = function(block,id){
        $id = id.replace('pizza-','');
        $.ajax({
            url: '/ru/constructor/ings',
            data: {id:$id},
            type: "POST",
            datatype:'html',
            success:function(data){
                $(block).html(data);
            }
        });
    }



    var c_left = true;
    var c_right = true;
    $(document).on('change', '.constructor_checkbox', function(){
        var parent = $(this).parents('.p_item');
        let title = $(this).data('name');
        let id = $(this).data('id');
        let image = $(this).data('image');
        let price = $(this).data('price');
        if($(this).is(':checked')){ 
            if(c_left){
                c_left = false;
                $(parent).addClass('left_disable');
                $('#post_left_img').attr('src', image);
                $('#post_left_img2').attr('src', image);
                $('#post_left_img3').attr('src', image);
                $('#post_left_img4').attr('src', image);
                $('[data-output="placeholder-left"]').hide();
                $('[data-output="description-left"]').html("<b>" + title + "</b>").show();
                getIngs('[data-output="ings-left"]',id);
            }else if(c_right){
                c_right = false;
                $(parent).addClass('right_disable');
                $('#post_right_img').attr('src', image);
                $('#post_right_img2').attr('src', image);
                $('#post_right_img3').attr('src', image);
                $('#post_right_img4').attr('src', image);
                $('[data-output="placeholder-right"]').hide();
                $('[data-output="description-right"]').html("<b>" + title + "</b>").show();
                getIngs('[data-output="ings-right"]',id);
            }
            halvesTotal();
            setDisable();
            return false;
        }
        if($(parent).hasClass('left_disable')){
            c_left = true;
            $(parent).removeClass('left_disable');
            $('#post_left_img').attr('src', '');
            $('#post_left_img2').attr('src', '');
            $('#post_left_img3').attr('src', '/img/half_p.png');
            $('#post_left_img4').attr('src', '/img/half_p.png');
            $('[data-output="placeholder-left"]').show();
            $('[data-output="description-left"]').html('').hide();
            $('[data-output="ings-left"]').html('');
        }
        if($(parent).hasClass('right_disable')){
            c_right = true;
            $(parent).removeClass('right_disable');
            $('#post_right_img').attr('src', '');
            $('#post_right_img2').attr('src', '');
            $('#post_right_img3').attr('src', '/img/half_p.png');
            $('#post_right_img4').attr('src', '/img/half_p.png');
            $('[data-output="placeholder-right"]').show();
            $('[data-output="description-right"]').html('').hide();
            $('[data-output="ings-right"]').html('');
        }

        halvesTotal();
        setDisable();
    });

    $('.mobileHalfControl').click(function(e){
        e.preventDefault;
        var count = 0;
        $('.halves-item input:checked').each(function(){
            count++;
        });
        if(count < 1){
            ViewMessage('error', 'Вы ещё не выбрали ни одной половинки');
            return false
        }
        if(count == 1){
            ViewMessage('error', 'Выберите вторую половинку');
            return false
        }
        if(count == 2){
            $('.m_fixed').hide();
            $('.modal').modal('hide');
            $('#mobile-constructor-modal').modal('show');
        }
        return false
    })

    var cMes = 0;
    function ViewMessage (type, text) {
        var savecMes = cMes;
        $('.mob_result_box').html('<p id="message_' + cMes + '" class="message-block m-' + type + '">' + text + '</p>');
        $('#message_' + savecMes).fadeIn();
        setTimeout(function () {
            $('.mob_result_box').html('')
        }, 2000);
        cMes += 1;
    }

    function halvesTotal(){
        var total = 0;
        var count = 0;
        $('.halves-item input:checked').each(function(){
            var price = $(this).parents('.halves-item').find('[name="price"]').val();
            total += parseInt(price);
            count++;
        });
        $('.halves-result [data-output="result"]').text(total);
        if(count == 2) $('.halfPizzaAdd').removeClass('btnDisabled');
        else $('.halfPizzaAdd').addClass('btnDisabled');
    }

    function setDisable(){
        var i = 0;
        $('.constructor_checkbox').each(function(){
            if($(this).is(':checked')){
                i ++;
            } 
        });
        if(i >= 2){
            $('.p_item').addClass("disabled");
            $('.p_item.right_disable, .p_item.left_disable').removeClass("disabled");
        }else{
            $('.p_item').removeClass("disabled");
        }
        return true;
    };

    $('body').on('click', '.comboAddCart', function(){
        let comboList = {};
        $('.combo-list').each(function(){
            var block = $(this);
            var name = block.data('name');
            var names = [];
            block.find('.combo-item[style="pointer-events: none;"]').each(function(){
                var id = $(this).data('name');
                names.push(id);
            });
            comboList[name] = names;
        });

        var json = JSON.stringify(comboList);

        var btn = $(this);
        // var total = btn.data('total');
        // if(!total) total = $('.comboTotal').text();
        var comboID = btn.data('id');
        $.ajax({
            type: "POST",
            url: '/ru/cart/comboadd',
            data: {
                comboList: json,
                // total: total,
                comboID: comboID
            },
            dataType: 'json',
            success: function(data) {
                btn.text('Hinzugefügt');
                updateRycleSum();
                console.log(data);
                if (data > 0) {
                    whatsAppElem.css('display', 'none');
                    mobileCartElem.css('display', 'inline-block');
                } else {
                    whatsAppElem.css('display', 'block');
                    mobileCartElem.css('display', 'none');
                }
                setTimeout(function(){
                    btn.text('In den Warenkorb');
                }, 1500);
            }
        });
        return false;
    });

    $('.replyOrder').click(function(){
        var el = $(this);
        var id = el.data('id');
        $.ajax({
            type: "POST",
            url: '/ru/cabinet/replyorder',
            data: {
                id: id
            },
            dataType: 'json',
            success: function(data) {
                el.removeClass('btn--primary');
                el.addClass('btn--green');
                el.text('В корзине');
                el.css('pointer-events','none');
                setTimeout(function(){
                    el.addClass('btn--primary');
                    el.removeClass('btn--green');
                    el.text('Повторить заказ');
                    el.css('pointer-events','auto');
                }, 1500);
                updateRycleSum();
            }
        });
        return false;
    });

    $('.userPassForm').submit(function(){
        var form = $(this);
        var data = form.serialize();
        $.ajax({
            type: "POST",
            url: '/ru/cabinet/passchange',
            data: data,
            dataType: 'json',
            success: function(data) {
                form.find('.formResult').html('');
                if(data.error.length > 0){
                    form.find('.formResult').html(data.error);
                }else{
                    form.find('.formResult').html(data.message);
                    var btn = form.find('button[type="submit"]');
                    btn.find('span').text('Сохранено');
                    btn.addClass('btn--green');
                    setTimeout(function(){
                        btn.find('span').text('Сохранить изменения');
                        btn.removeClass('btn--green');
                    }, 1000);
                }
            }
        });
        return false;
    });

    $('.logOut').click(function(){
        $.ajax({
            type: "POST",
            url: '/ru/cabinet/logout',
            data: '',
            dataType: 'html',
            success: function(data) {
                location.reload()
            }
        });
        return false;
    });

    $('.userDataForm').submit(function(){
        var form = $(this);
        var data = form.serialize();
        $.ajax({
            type: "POST",
            url: '/ru/cabinet/save',
            data: data,
            dataType: 'html',
            success: function(data) {
                var btn = form.find('button[type="submit"]');
                btn.find('span').text('Сохранено');
                btn.addClass('btn--green');
                setTimeout(function(){
                    btn.find('span').text('Сохранить изменения');
                    btn.removeClass('btn--green');
                }, 1000);
            }
        });
        return false;
    });

    $('.regForm').submit(function(){
        var form = $(this);
        var data = form.serialize();
        $.ajax({
            type: "POST",
            url: '/ru/auth/register',
            data: data,
            dataType: 'json',
            success: function(data) {
                form.find('.formResult').html('');
                if(data.error.length > 0){
                    form.find('.formResult').html(data.error);
                }else{
                    // form.html(data.message);
                    window.location.href = '/ru/cabinet/';
                }
            }
        });
        return false;
    });

    $('.loginForm').submit(function(){
        var form = $(this);
        var data = form.serialize();
        $.ajax({
            type: "POST",
            url: '/ru/auth/login',
            data: data,
            dataType: 'json',
            success: function(data) {
                form.find('.formResult').html('');
                if(data.error.length > 0){
                    form.find('.formResult').html(data.error);
                }else{
                    window.location.href = data.redirect;
                }
            }
        });
        return false;
    });

    $(document).on('change','[name=delivery]',function(){
        var el = $(this);
        var type = el.data('type');
        console.log(type);
        if(type == 2){
            $('[data-delivery="1"]').hide();
            $('[data-delivery="1"] .form-control').removeAttr('required');
            $('[data-delivery="2"]').show();
        }else{
            $('[data-delivery="2"]').hide();
            $('[data-delivery="1"] [data-required="true"]').attr('required','required');
            $('[data-delivery="1"]').show();
        }
    });

    $('.cartChangeCount button').on('click',function(){
        var el = $(this);
        var countEl = el.parents('.cartChangeCount').find('[data-output="count"]');
        var count = countEl.text();
        var id = countEl.data('id');

        $.ajax({
            type: "POST",
            url: '/ru/cart/change',
            data: {
                id: id,
                count: count
            },
            dataType: 'json',
            success: function(data) {
                if(data.error != null && data.error.length > 0){
                    location.reload(true);
                    return false;
                }
                // $('.completeProdAmount[data-id="'+id+'"]').html(data);
                updateRycleSum();
            }
        });
    });

    $('.cartAddAdditional').click(function(){
        var el = $(this);
        var id = el.data('id');
        var reload = el.data('reload');
        $.ajax({
            type: "POST",
            url: '/ru/cart/add',
            data: {
                id: id,
                count: 1,
                size: '',
                type: '',
                ingsAdd: '',
                ingTotal: '',
                ingsRemove: ''
            },
            dataType: 'html',
            success: function(data) {
                if(reload){
                    location.reload();
                }else{
                    el.text('Hinzugefügt');
                    setTimeout(function(){
                        el.text('In den Warenkorb');
                        updateRycleSum();
                    }, 1500);
                }
            }
        });
        return false;
    });

    $('.cartAdd').click(function(){
        var el = $(this);
        var id = el.data('id');
        var count = $('.catalog-item-controls .prodCount[data-id="'+id+'"]:first').text();
        var size = $('input[type=radio][name="size-'+id+'"]:checked').data('size');
        if(size == undefined){
            size = '';
        }
        var typeID = $('input[type=radio][name="type-'+id+'"]:checked').attr('id');
        var type = $('input[type=radio][name="type-'+id+'"]:checked').parents('.catalog-item-types').find('label[for="'+typeID+'"]').text();
        if(type == undefined){
            type = '';
        }
        var ingsAdd = [];
        $('.ingridientsAdd .additionalIng[data-id="'+id+'"] .ingBlock[data-active="true"]').each(function(){
            var ing = $(this);
            var ingName = ing.find('input[type="checkbox"]').data('name');
            ingsAdd.push(ingName);
        });
        $.ajax({
            type: "POST",
            url: '/ru/cart/add',
            data: {
                id: id,
                count: count,
                size: size,
                type: type,
                ingsAdd: ingsAdd.join(', '),
            },
            dataType: 'json',
            success: function(data) {
                $('.ingBlock').removeClass('ingDisabled');
                el.removeClass('btn--primary');
                el.addClass('btn--green');
                el.text('Hinzugefügt');
                el.css('pointer-events','none');
                setTimeout(function(){
                    el.addClass('btn--primary');
                    el.removeClass('btn--green');
                    el.text('In den Warenkorb');
                    el.css('pointer-events','auto');
                    $('.modal').modal('hide');
                    updateRycleSum();
                    $('.ingBlock').attr('data-active','false');
                    $('.ingBlock').find('[type=checkbox]').prop('checked', false);
                }, 1000);
            }
        });
        return false;
    });

    $('.popupToggle').click(function(){
        var el = $(this);
        var blockClass = el.data('class');
        var id = el.data('id');
        el.parents('.catalog-item-addons').fadeOut();
        $('.'+blockClass+'[data-id="'+id+'"]').fadeIn();
    });

    $('.IngHideBtn').click(function(){
        var el = $(this);
        var id = el.data('id');
        el.parents('.catalog-item-addons').find('button[data-button="toggle"]').trigger('click');
    });

    $(document).on('click','.ingRemove',function(){
        $(this).toggleClass('ing_active');
    });

    $('.ingridientsRemove .ingBlock').find('input[type="checkbox"]').on('change',function(){
        var el = $(this);
        var isChecked = el.is(':checked');
        var block = el.parents('.ingBlock');
        block.attr('data-active', isChecked);

        var blockID = block.data('id');
        var i = 0;
        $('.ingridientsRemove .additionalIng[data-id="'+blockID+'"] .ingBlock[data-active="true"]').each(function(){
            i++;
        });
        
        $('.IngRemoveBtn[data-id="'+blockID+'"] sup').html(i);
    });

    var comboSelected = {};

    $(document).on('click','.tab-pane .combo-item',function(){
        var el = $(this);
        var sectID = el.parents('.tab-pane').data('id');

        var fullPrice = 0;
        $('.combo-item').each(function(){
            var checked = $(this).find('input[type=checkbox]:checked');
            if(checked.length > 0){
                var price = $(this).find('input[name="price"]').data('full-price');
                fullPrice += price;
            }
        });

        $('.comboTotal').html(fullPrice);
        if(fullPrice > 0){
            $('#combo-modal').find('.comboAddCart').show();
            $('.amount').css('display','block');
        }else{
            $('#combo-modal').find('.comboAddCart').hide();
        }

        var IDs = [];
        $('.tab-pane[data-id="'+sectID+'"] .combo-item').each(function(){
            var checked = $(this).find('input[type=checkbox]:checked');
            if(checked.length > 0){
                var id = $(this).data('id');
                IDs.push(id);
            }
        });
        comboSelected[sectID] = IDs;

        var limit = el.parents('.tab-pane').data('limit');
        var count = 0;
        if(comboSelected[sectID] != undefined) count = comboSelected[sectID].length;
        if(count == limit){
            $('.tab-pane[data-id="'+sectID+'"] .combo-item').css('pointer-events','none');
            $('.tab-pane[data-id="'+sectID+'"] .combo-item').each(function(){
                var checked = $(this).find('input[type=checkbox]:checked');
                if(checked.length > 0){
                    $(this).css('pointer-events','auto');
                }
            });
        }else{
            $('.tab-pane[data-id="'+sectID+'"] .combo-item').css('pointer-events','auto');
        }
    });

    $('.comboTabs li:first').addClass('active');
    $('.tab-pane:first').addClass('active');
});

function updateRycleSum() {
    $.ajax({
        type: "GET",
        url: '/ru/cart/info',
        data: {},
        dataType: 'json',
        success: function(data) {
            $('.headerRycleCount').text(data.totalcount);
            $('#headerRyclePopup').html(data.popup);
            $('.checkBlock').html(data.check);
            $('.completeTotal').html(data.sum/100+' EUR');
            $('.completeTotalBtn').find('span').text('Bestellung aufgeben '+data.sum/100+' EUR');
            if(data.promoamount){
                $('.completeTotal').html(data.promoamount/100+' EUR');
                 $('.completeTotalBtn').find('span').text('Bestellung aufgeben '+data.promoamount/100+' EUR');
            }
            $('[data-output="result"]').text(data.sum/100);
            $('[data-output="promoamount"]').text(data.promoamount/100);
        }
    });
}