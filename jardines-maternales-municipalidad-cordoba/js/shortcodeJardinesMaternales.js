(function() {
  tinymce.create('tinymce.plugins.buscjardinesmaternalescba_button', {
    init: function(ed, url) {
      ed.addCommand('buscjardinesmaternalescba_insertar_shortcode', function() {
        selected = tinyMCE.activeEditor.selection.getContent();
        var content = '';

        ed.windowManager.open({
          title: 'Buscador de Jardines Maternales',
          body: [{
            type: 'textbox',
            name: 'pag',
            label: 'Cantidad de Resultados'
          }],
          onsubmit: function(e) {
            var pags = Number(e.data.pag.trim());
            ed.insertContent( '[buscador_jardines_maternales_cba' + (pags && Number.isInteger(pags) ? ' pag="'+pags+'"' : '') + ']' );
          }
        });
        tinymce.execCommand('mceInsertContent', false, content);
      });
      ed.addButton('buscjardinesmaternalescba_button', {title : 'Insertar buscador de Jardines Maternales', cmd : 'buscjardinesmaternalescba_insertar_shortcode', image: url.replace('/js', '') + '/images/logo-shortcode.png' });
    }
  });
  tinymce.PluginManager.add('buscjardinesmaternalescba_button', tinymce.plugins.buscjardinesmaternalescba_button);
})();