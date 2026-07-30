{{-- !-- Delete Warning Modal -->  --}}
<form action="{{ route('plantilla_destroy',['type_id'=>$data[0]->type_id,'id'=>$data[0]->id]) }}" method="post">
    <div class="modal-body">
        @csrf
        @method('DELETE')
        <h5 class="text-center">Are you sure you want to delete data <b>{{ $data[0]->name }}</b>?</h5>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Yes, Delete</button>
    </div>
</form>