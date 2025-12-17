@extends('layouts.app')

@section('content')

<h2 style="color:red">HOME VIEW RENDERED</h2>

<pre>
{{ print_r($home, true) }}
</pre>

@endsection
