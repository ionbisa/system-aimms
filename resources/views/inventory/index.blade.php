@extends('layouts.app')

@section('content')
<h4>Inventory Flow</h4>

<form method="POST">
@csrf
<input name="item_name" placeholder="Item">
<input name="qty" type="number">
<select name="type">
<option>IN</option>
<option>OUT</option>
</select>
<button class="btn btn-success">Submit</button>
</form>

<hr>

<table class="table">
@foreach($transactions as $t)
<tr>
<td>{{ $t->item_name }}</td>
<td>{{ $t->qty }}</td>
<td>{{ $t->type }}</td>
</tr>
@endforeach
</table>
@endsection
