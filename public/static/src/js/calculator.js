var tariffs;

$(function() {

$.get('/ru/help-calc/tariff').done(function(data) {
  tariffs = JSON.parse(data);
});

$('#change-currency').change(function(e) {
    var name = $('#change-currency option:selected').data('name');
    var kurs = $(this).val();
    $('#currency-name').text(name);
    var price = $('#result-price').attr('data-value');
    $('#result-price').text((price / kurs).toFixed(2));
  });

  $.get('/ru/getCities')

    .done(res => createSelectList(JSON.parse(res)));



  const cargoWeight = $('#jsWeight'),

    cargoVolume = $('#jsVolume'),

    valueUnits = $('#jsValueUnits'),

    cargoLength = $('#jsCargoLength'),

    cargoWidth = $('#jsCargoWidth'),

    cargoHeight = $('#jsCargoHeight'),

    calcVolume = $('#jsCalcVolume'),

    volumeResult = $('#jsVolumeResult'),

    applyVolume = $('#delivery-1'),

    onlyDecimal = $('.only-decimal'),

    onlyFloat = $('.only-float'),

    calcBtn = $('#jsCalculate'),

    packBlock = $('.jsPackOptions'),

    deliveryBlock = $('.jsDeliveryOptions'),

    packBlockToggle = $('.jsChoiseBlockToggle'),

    packVolume = $('#jsPackVolume'),

    placesAmount = $('#jsPlacesAmount'),

    cityFrom = $('#jsFrom'),

    cityTo = $('#jsTo'),

    citySelectors = $('.jsCities'),

    firstRow = $('.form-block__row-first'),

    priceBlock = $('.jsPriceBlock');





  /**

   * Action Handlers

   */



  calcBtn.on('click', function() {

    const from = cityFrom.val();

    const to = cityTo.val();

    const weight = cargoWeight.val();

    const volume = cargoVolume.val();



    if (validateAllForm(from, to, weight, volume)) {

      const data = {

        ...getDeliveryOptions(),

        ...getPackOptions(),

        from, to, weight, volume

      };

      console.log(data);

      $.ajax('/ru/HelpCalc/calculate', {

        type: 'POST',

        dataType: 'json',

        data,

        success: data => {
          if(data.cost == 0) {
              $('.not-price').show();
              $('.jsPriceBlock').hide();
              $('#calc-form .to-btn').hide();
          } else {
            $('.not-price').hide();
              $('.jsPriceBlock').show();
              $('#calc-form .to-btn').show();
             priceBlock.find('.form-block__price__total b').html(data.cost/100).attr('data-value',data.cost).end().show();
            $('#currency-name').text('EUR');
            $('.to-btn').remove();
            $('#calc-form').append(data.btn);
          }
         

        },

        error: ({responseText}) => console.error(responseText)

      });

    }

  });



  citySelectors.on('change', () => validateCities(cityFrom.val(), cityTo.val()));



  onlyFloat.on('input', function() {

    $(this).val($(this).val().replace(/[^0-9.,]/g, '').replace(/(\..*)\./g, '$1'));

  });



  $('#jsCargoHeight, #jsCargoLength, #jsCargoWidth').on('input', function() {

    $(this).removeClass('invalid');

  });



  onlyDecimal.on('input', function() {

    $(this).val($(this).val().replace(/[^0-9.,]/g, ''));

  });



  calcVolume.on('click', () => {

    if (validateVolumeCalc()) calculateVolume();

  });



  packBlockToggle.on('change', (e) => {

    const { target: { checked } } = e;

    if(checked) {

      packBlock.slideDown()

    } else {

      packBlock.slideUp();

    }

  });



  applyVolume.on('change', e => {

    const { target: { checked } } = e;

    const result = volumeResult.val();

    if (checked && result) cargoVolume.val(result);

  });



  valueUnits.on('change', function () {

    $(this).removeClass('invalid');

  })







  /**

   * Helpers Functions

   */



  // валидация формы расчета стоимости груза

  function validateAllForm(from, to, weight, volume) {

    const packVolumeValue = packVolume.val();

    if (!from || !to || !weight || !volume ) { //|| (needPack() && !packVolumeValue)

      if (!calcBtn.siblings().is('.validate_error')) {

        calcBtn.before('<div class="validate_error">Заполните все обязательные поля, помеченые *</div>');

        if (!packVolumeValue) packVolume.addClass('invalid');

      }

      return null;

    }

    if (!validateCities(from, to)) return null;

    calcBtn.siblings('.validate_error').remove();

    packVolume.removeClass('invalid');

    return true;

  }



  // валидация формы расчета объема

  function validateVolumeCalc() {

    const { unit, length, width, height } = getVolumesData();



    if (!unit) {

      valueUnits.addClass('invalid');

      return null;

    }

    valueUnits.removeClass('invalid');



    if (!length) {

      cargoLength.addClass('invalid');

      return null;

    }

    cargoLength.removeClass('invalid');



    if (!width) {

      cargoWidth.addClass('invalid');

      return null;

    }

    cargoWidth.removeClass('invalid');



    if (!height) {

      cargoHeight.addClass('invalid');

      return null;

    }

    cargoHeight.removeClass('invalid');



    return true;

  }



  function validateCities(from, to) {

    if (from && to && from === to) {

      if (!firstRow.find('.validate_error').length) {

        firstRow.append('<div class="validate_error">Города не должны совпадать!</div>');

      }

      return null;

    }

    firstRow.find('.validate_error').remove();

    return true;

  }



  // проверка чекбокаса "применить в расчетах"

  function isApply() { //

    return applyVolume.is(':checked');

  }



  function needPack() {

    return packBlockToggle.is(':checked');

  }



  // расчет объема

  function calculateVolume() {

    const { unit, length, width, height } = getVolumesData();

    const result = length * width * height * unit;

    const updated = result % 1 === 0 ? result : result.toFixed(1)

    volumeResult.val(updated);

    if (isApply()) cargoVolume.val(updated);

  }



  // генерация спииска городов

  function createSelectList(options) {

    citySelectors.easyAutocomplete({

      data: options,

     // getValue: 'title',

      highlightPhrase: false,

      placeholder: 'Укажите город',

      list: {

        maxNumberOfElements: 20,

        match: {

          enabled: true,
          method: function(element, phrase) {
              let focused_element = $(':focus').attr('id');
              if(focused_element == 'jsTo') {
                  let val = $('#jsFrom').val();
                  //console.log(tariffs[val]);
                  if($.inArray(element,tariffs[val]) !== -1) {
                    return element.slice(0, phrase.length) == phrase;    
                  } else {
                    return false;
                  }
              } else {
                  return element.slice(0, phrase.length) == phrase;    
              }
              
          }

        },

        showAnimation: {

          type: "slide",

          time: 200

        },

        hideAnimation: {

          type: "slide",

          time: 200,

        },
        sort: {
          enabled: true
        }

      }

    });



    // select.each((i, el) => {

    //   $(el).append('<option disabled selected>Выберете город</option>')

    //   options.forEach(_opt => {

    //     $(el).append(`<option value="${_opt.code}">${_opt.title}</option>`);

    //   });

    // });

    // select.on('change', e => {

    //   // console.log(e);

    // });

  }



  //получить данные с формы расчета объема

  function getVolumesData() {

    return {

      unit: +valueUnits.val().replace(',','.'),

      length: +cargoLength.val().replace(',','.'),

      width: +cargoWidth.val().replace(',','.'),

      height: +cargoHeight.val().replace(',','.')

    };

  }



  // получить опции упавковки

  function getPackOptions () { 

    const options = {};
    if (needPack()) {

      const packVolumeValue = packVolume.val();

      const placeAmountValue = placesAmount.val();

      if (placeAmountValue) options.places = placeAmountValue;

      options.pack_volume = packVolumeValue;

      
      options.pack_type = $('input[name=pack_type]:checked').data('value');
     /* packBlock.find('input[type="checkbox"]').each((i, el) => {

        const {checked, id} = el;

        var val = $(el).data('value');

        if (checked) options.pack_type.push(val);

      }); */

    }

    return options;

  }



  function getDeliveryOptions() {

    const options = {};

    deliveryBlock.find('input[type="radio"]').each((i, el) => {

      const { checked, id } = el;

      if (checked) options.delivery_type = id;
      
    });

    if(document.getElementById('pick_up').checked) {
      options.pick_up = 1;  
    } else {
      options.pick_up = 0;
    }

    if(document.getElementById('deliver').checked) {
      options.deliver = 1;  
    } else {
      options.deliver = 0;
    }

    return options;

  }



});

