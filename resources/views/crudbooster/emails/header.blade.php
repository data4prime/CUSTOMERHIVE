{{-- Wrapper condiviso da TUTTE le email dell'app (CRUDBooster::sendEmail()/
     sendEmailQueue() -> crudbooster::emails.blank -> header+content+footer).
     Stili inline ovunque: i client email non applicano <style>/CSS esterno
     in modo affidabile. Colori presi da public/css/theme.css (--ch-*):
     accent indigo #4f46e5, sfondo pagina #f7f7f9, testo #16161d/#8b8b96. --}}
<div style="background:#f7f7f9; padding:32px 16px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <div style="max-width:560px; margin:0 auto; background:#ffffff; border:1px solid #e6e6ea; border-radius:12px; overflow:hidden;">
    <div style="padding:28px 32px; text-align:center; border-bottom:1px solid #e6e6ea;">
      <img src="{{ CRUDBooster::getLogo(null) }}" alt="{{ CRUDBooster::getSetting('appname') ?: 'CustomerHive' }}" style="height:32px; width:auto; border:0;">
    </div>
    <div style="padding:32px; color:#16161d; font-size:14px; line-height:1.6;">
