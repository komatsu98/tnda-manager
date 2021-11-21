@extends('layouts.app')

@section('content')


<div id="json">
</div>

@push('scripts')
<link href="../../css/jquery.json-viewer.css" type="text/css" rel="stylesheet">
<script src="../../js/jquery.json-viewer.js"></script>
<script>
$(document).ready(function() {
    var txt = document.createElement("textarea");
    txt.innerHTML = `{{$data}}`;
    $('#json').jsonViewer(JSON.parse(txt.value));
})
</script>

@endpush    
@endsection