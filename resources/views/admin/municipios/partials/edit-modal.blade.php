<!-- Modal de Edición -->
<div class="modal fade" id="editModal{{ $municipio->id }}" tabindex="-1" role="dialog" aria-labelledby="editModalLabel{{ $municipio->id }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel{{ $municipio->id }}">Editar Municipio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.municipios.update', $municipio) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" name="nombre" value="{{ $municipio->nombre }}" required>
                    </div>
                    <div class="form-group">
                        <label for="departamento">Departamento</label>
                         {{-- Campo de texto reemplazado por un select --}}
                        <select class="form-control" name="departamento" required>
                            <option value="">-- Seleccione un Departamento --</option>
                            @foreach($departamentos as $departamento)
                                <option value="{{ $departamento }}" {{ $municipio->departamento == $departamento ? 'selected' : '' }}>
                                    {{ $departamento }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="logo">Nuevo Logo (Opcional)</label>
                        <input type="file" class="form-control-file" name="logo">
                        @if($municipio->logo)
                            <small class="form-text text-muted">Logo actual:</small>
                            <img src="{{ asset('storage/' . $municipio->logo) }}" alt="Logo" class="img-thumbnail mt-2" width="100">
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

