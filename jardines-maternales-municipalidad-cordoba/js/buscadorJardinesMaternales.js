(function(window, document, $) {

  const $JMM = $('#JMM');
  const $form = $JMM.find('form');
  const $resultados = $JMM.find('.resultados');
  const $reset = $JMM.find('#filtros__reset');

  $reset.click(function(e) {
    e.preventDefault();
    $form[0].reset();
    $form.submit();
  });

  $form.submit(function(e) {
    e.preventDefault();
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: buscarJardinesMaternales.url,
      data: {
        action: 'buscar_jardines_maternales',
        nonce: buscarJardinesMaternales.nonce,
        nombre: $form.serializeArray()[0].value
      },
      success: function(response) {
        if (response.data) {
          $resultados.html(response.data);
        }
      }
    });
  });

  $(document).on('click','#JMM .paginacion__boton', function(e) {
    const pagina = $(this).data('pagina');
    const $boton = $(e.target);
    const texto = $boton.html();
    $boton.html('...');
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: buscarJardinesMaternales.url,
      data: {
        action: 'buscar_jardines_maternales_pagina',
        nonce: buscarJardinesMaternales.nonce,
        pagina: pagina,
        nombre: $form.serializeArray()[0].value
      },
      success: function(response) {
        if (response.data) {
          $resultados.html(response.data);
          $('body').animate({scrollTop: 50}, 1000);
        }
      },
      done: function() {
        $boton.html(texto);
      }
    });
  });
})(window, document, jQuery);