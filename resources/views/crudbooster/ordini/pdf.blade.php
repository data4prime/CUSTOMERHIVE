<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Ordine</title>
	<style type="text/css"></style>
</head>

<body bgcolor="#e5e5e5" style="width:100%">
	<h2>Ordine {{ $order->numero }}</h2>
	<table style="width:100%">
		<tr>
			<td>Codice Cliente : {{ $order->cliente }}</td>
		</tr>
		<tr>
			<td>Ragione sociale : {{ $order->intestatario }}</td>
		</tr>
		<tr>
			<td>Data Ordine : {{ date('d/m/Y', strtotime($order->data)) }}</td>
		</tr>
		<tr>
			<td>Data Consegna : {{ date('d/m/Y', strtotime($order->data_consegna)) }}</td>
		</tr>
		<tr>
			<td>Termini di pagamento : {{ $order->termini_di_pagamento }}</td>
		</tr>
		<tr>
			<td>Indirizzo : {{ isset($cliente->indirizzo) ? $cliente->indirizzo : '' }}</td>
		</tr>
		<tr>
			<td>P.IVA : {{ isset($cliente->piva) ? $cliente->piva : '' }}</td>
		</tr>
		<tr>
			<td>Totale : {{ $total }}</td>
		</tr>
	</table>
	<h3>Righe d'ordine</h3>
	<table style="width:100%; text-align: center">
		@foreach ($righe as $riga)
		<tr>
			<th>Codice Articolo</th>
			<th>Descrizione</th>
			<th>Unità di misura</th>
			<th>Quantità</th>
			<th>Prezzo</th>
			<th>Parziale</th>
		</tr>
		<tr>
			<td>{{ $riga->codice }}</td>
			<td>{{ $riga->descrizione }}</td>
			<td>{{ $riga->unita_misura }}</td>
			<td>{{ $riga->qta }}</td>
			<td>{{ $riga->prezzo }}</td>
			<td>{{ $riga->subtotal }}</td>
		</tr>
		@endforeach
	</table>
</body>

</html>