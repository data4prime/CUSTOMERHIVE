<script>
  // Conferma inline: un clic su "Rimuovi" mostra "Rimuovere? Si / Annulla" sulla stessa riga.
  $(function () {
    $(document).on('click', '.rm-ask', function () {
      var w = $(this).closest('.rm-wrap');
      $('.rm-wrap').not(w).find('.rm-confirm').addClass('d-none').end().find('.rm-ask').removeClass('d-none');
      $(this).addClass('d-none');
      w.find('.rm-confirm').removeClass('d-none');
    });
    $(document).on('click', '.rm-cancel', function () {
      var w = $(this).closest('.rm-wrap');
      w.find('.rm-confirm').addClass('d-none');
      w.find('.rm-ask').removeClass('d-none');
    });
  });
</script>
