<!-- Modal de Creación -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Crear Nuevo Municipio</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.municipios.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="departamento">Departamento</label>
                        {{-- Campo de texto reemplazado por un select --}}
                        <select class="form-control" name="departamento" required>
                            <option value="">-- Seleccione un Departamento --</option>
                            @foreach($departamentos as $departamento)
                                <option value="{{ $departamento }}">{{ $departamento }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="logo">Logo <b>(*)</b></label>
                        {{-- Se añade el atributo 'required' al input --}}
                        <input type="file" class="form-control-file" name="logo" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

