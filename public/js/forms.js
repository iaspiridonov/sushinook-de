function cartChange(id,count,type){
	if(count == 0) return false;
	var url = '/ru/cart/change';
	if(type == 'combo') url = '/ru/cart/combochange';
	if(type == 'half') url = '/ru/cart/halfpizzachange';
    $.ajax({
        type: "POST",
        url: url,
        data: {
            id: id,
            count: count
        },
        dataType: 'html',
        success: function(data) {
			fillProgressBar();
        }
    });
}

function fillProgressBar(price = 0) {
	let cartPrice = price < 1
		? parseInt($('.cart-popup-amount span[data-output="result"]').text())
		: price
	;

	let progress = cartPrice / maxPrice * 100;
	$('.progress-bar__points').attr(
		'style',
		'background: linear-gradient(to right, #c92139 ' + progress + '%, #344454 0%);');
}


//# sourceMappingURL=forms.js.map

/*
	Попап Корзины
	------------------------------------
	использованные библиотеки:
	- https://jamesflorentino.github.io/nanoScrollerJS/
	- https://idangero.us/swiper/
 	------------------------------------ */
(function() {

	var $popup = $("[data-role=\"cart-popup\"]");

	if (!$popup["length"]) return false;

	var $slider = $popup.find("[data-role=\"slider\"]");
	var slider;

	//калькуляция
	//это функцию надо всего вызывать при добавлении/удалении контента в попапе этом
	var calc = function() {
		var result = 0;
		$popup.find("[data-role=\"item\"]").each(function() {
			var $item 	= $(this);
			var price 	= parseFloat($item.find("[name=\"price\"]").val(), 10);
			var $count 	= $item.find("[name=\"count\"]");
			var count 	= parseInt($count.val(), 10);
			var amount 	= price*count;
			var id = $item.data('id');
			var type = $item.data('type');

			result += amount;

			$item
				.find("[data-output=\"count\"]").text(count).end()
				.find("[data-output=\"amount\"]").text(number_format(amount/100, 2, ".", " ")).end()
				.find("[data-button=\"minus\"]").attr("disabled", count==1);

			cartChange(id,count,type);
			$count.val(count);
		});

		$popup.find("[data-output=\"result\"]").text(number_format(result/100, 2, ".", " "));

		scroll();

		if (slider) slider.update();
	};

	var checkStartEnd = function() {
		if (slider) {
			$slider
				.removeClass("start end")
				.addClass(slider["isBeginning"] ? "start" : "")
				.addClass(slider["isEnd"] ? "end": "");
		}
	};

	//инициализация полос прокруток
	var scroll = function() {
		$popup.find("[data-role=\"scroll\"]").nanoScroller({
			alwaysVisible: true
		});
	};

	if ($slider["length"]) {
		//инициализация слайдера
		slider = new Swiper($slider, {
			loop: false,
			speed: 600,
			slideActiveClass: "active",
			lazy: true,
			navigation: {
				nextEl: ".btn--popup-next",
				prevEl: ".btn--popup-prev",
				disabledClass: "disabled"
			},
			on: {
				init: function() {
					checkStartEnd();
					setTimeout(function() {
						checkStartEnd();
					}, 200);
				}
			}
		});

		slider
			.on("slideChange", function() {
				checkStartEnd();
			})
			.on("lazyImageReady", function(slideEl, imageEl) {
	      $(slideEl).addClass("loaded");
	    });
	}

	scroll();
	calc();

	var updateRycleSum = function(){
	    $.ajax({
	        type: "GET",
	        url: '/ru/cart/info',
	        data: {},
	        dataType: 'json',
	        success: function(data) {
	            $('.headerRycleCount').text(data.count);
	            $('#headerRyclePopup').html(data.popup);
	            $('.completeTotal').html(data.sum/100+' EUR');
	            $('.tableCompleteTotal').text(data.sum/100);
	            $('.completeTotalBtn').find('span').text('Bestellung aufgeben ' + data.sum / 100 +' EUR.');
	        }
	    });
	}

	var cartremove = function(id){
		$.ajax({
            type: "POST",
            url: '/ru/cart/remove',
            data: { id: id },
            dataType: 'json',
            success: function(data) {
            	location.reload(true);
                // updateRycleSum();
                // if(data.count == 0){
                //     location.reload();
                // }
            }
        });
	}

	$popup
		.delegate("[data-button=\"minus\"]", "click", function() {
			var $item 	= $(this).parents("[data-role=\"item\"]");
			var $count 	= $item.find("[name=\"count\"]");
			var count 	= parseInt($count.val(), 10);
			count--;
			$count.val(count);
			calc();
		})
		.delegate("[data-button=\"plus\"]", "click", function() {
			var $item 	= $(this).parents("[data-role=\"item\"]");
			var $count 	= $item.find("[name=\"count\"]");
			var count 	= parseInt($count.val(), 10);
			count++;
			$count.val(count);
			calc();
		})
		.delegate("[data-button=\"remove\"]", "click", function() {
			$('[data-button="remove"]').css('pointer-events','none');
			$(this).parents("[data-role=\"item\"]").slideUp(function() {
				$el = $(this);
				$el_id = $el.data('id');
				cartremove($el_id);
				$el.remove();
				calc();
			});
		});

	$(".cart-popup-wrapper").on("mouseenter", function() {
		$(document).trigger("itemLoadedEvent");
		calc();
	});

})();
//------------------------------------



/*
	форма Регистрации
	------------------------------------
	использованные библиотеки:
	- https://getbootstrap.com/docs/3.3/javascript/#modals
	- http://robinherbots.github.io/Inputmask/
 	------------------------------------ */
(function() {

	var $form = $("[data-form=\"reg\"]").find("form");

	if (!$form["length"]) return false;

	//маски
	$form.find("[data-inputmask]").inputmask({
		showMaskOnHover: false,
		placeholder: "_"
	});
	
})();
//------------------------------------



/*
	форма Личные данные
	------------------------------------
	использованные библиотеки:
	- http://robinherbots.github.io/Inputmask/
 	------------------------------------ */
(function() {

	var $form = $("[data-form=\"profile\"]").find("form");

	if (!$form["length"]) return false;

	//маски
	$form.find("[data-inputmask]").inputmask({
		showMaskOnHover: false,
		placeholder: "_"
	});

})();
//------------------------------------



/*
	форма Напишите нам
	------------------------------------
	использованные библиотеки:
	- http://robinherbots.github.io/Inputmask/
 	------------------------------------ */
(function() {

	var $form = $("[data-form=\"feedback\"]").find("form");

	if (!$form["length"]) return false;

	//маски
	$form.find("[data-inputmask]").inputmask({
		showMaskOnHover: false,
		placeholder: "_"
	});

})();
//------------------------------------



/*
	форма Корзина
	------------------------------------
	использованные библиотеки:
	- http://robinherbots.github.io/Inputmask/
 	------------------------------------ */
(function() {

	var $form = $("[data-form=\"cart\"]").find("form");

	if (!$form["length"]) return false;

	var $result = $form.find("[data-output=\"result\"]");

	var calc = function(isAsync) {

		var result = 0;

		$form.find("[data-role=\"item\"]").each(function() {
			var $item 	= $(this);
			var price 	= $item.data("price");
			var count 	= $item.attr("data-count");
			var amount 	= price*count;

			result += amount;

			$item
				.find("[data-output=\"count\"]").text(count).end()
				.find("[data-output=\"amount\"]").text(number_format(amount/100, 2, ".", " "));

			$item
				.find("[data-button=\"minus\"]").attr("disabled", count==1);

		});
		if (!isAsync) {
			result = result / 2;
			$result.text(number_format(result/100, 2, ".", " "))
		} else {
			$result.text('XXX');
		}
	};

	calc();

	$form.find("[data-role=\"item\"]").each(function() {
		var $item = $(this);
		var count = $item.data("count");

		var updateRycleSum = function(){
		    $.ajax({
		        type: "GET",
		        url: '/ru/cart/info',
		        data: {},
		        dataType: 'json',
		        success: function(data) {
					fillProgressBar(parseInt(data.sum.replace(/ /g,''))/100);
		            $('.headerRycleCount').text(data.count);
		            $('#headerRyclePopup').html(data.popup);
		            $('.completeTotal').html(data.sum/100+' EUR');
		            $('.completeTotalBtn').find('span').text('Bestellung aufgeben '+data.sum/100+' EUR');
		        }
		    });
		}

		var cartremove = function(id){
			$.ajax({
	            type: "POST",
	            url: '/ru/cart/remove',
	            data: { id: id },
	            dataType: 'json',
	            success: function(data) {
	            	location.reload();
	                // updateRycleSum();
	                // if(data.count == 0){
	                //     location.reload();
	                // }
	            }
	        });
		}

		$item
			.find("[data-button=\"remove\"]").on("click", function() {
				$el = $(this);
				$el_id = $el.data('id');
				cartremove($el_id);
				$item.remove();
				calc(true);
				setTimeout(function () {
					location.reload();
				}, 1000);
			}).end()
			.find("[data-button=\"minus\"]").on("click", function() {
				count--;
				$item.attr("data-count", count);
				calc(true);
				setTimeout(function () {
					location.reload();
				}, 1000);
			}).end()
			.find("[data-button=\"plus\"]").on("click", function() {
				count++;
				$item.attr("data-count", count);
				calc(true);
				setTimeout(function () {
					location.reload();
				}, 1000);

			});
	});

	$("[data-step]").on("click", function() {
		var tab = $(this).data("step");
		$("[data-role=\"cart\"]").find("[aria-controls=\"" + tab + "\"]").tab("show");
	});

})();
//------------------------------------



/*
	форма Оформление заказа
	------------------------------------
	использованные библиотеки:
	- http://robinherbots.github.io/Inputmask/
 	------------------------------------ */
(function() {

	var $form = $("[data-form=\"order\"]").find("form");

	if (!$form["length"]) return false;

	//маски
	$form.find("[data-inputmask]").inputmask({
		showMaskOnHover: false,
		placeholder: "_"
	});

	var checkDelivery = function() {
		var type = $form.find("[name=\"delivery\"]").filter(":checked").data("type");
		$form.find("[data-delivery]")
			.hide()
			.filter("[data-delivery=\"" + type +"\"]").show();
	};

	checkDelivery();

	$form.find("[name=\"delivery\"]")	.on("change", function() {
		checkDelivery();
	});

})();
//------------------------------------


