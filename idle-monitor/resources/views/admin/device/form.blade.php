@extends('admin.layouts.app')

@section('title', isset($device) ? 'Edit Device' : 'Add Device')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h3><i class="fas fa-car"></i> {{ isset($device) ? 'Edit Device' : 'Add Device' }}</h3>

            <div class="card mt-3">
                <div class="card-body">
                    <form action="{{ isset($device) ? route('admin.device.update', $device) : route('admin.device.store') }}" method="POST">
                        @csrf
                        @if(isset($device))
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Device ID *</label>
                                    <input type="text" name="device_id" class="form-control @error('device_id') is-invalid @enderror" 
                                           value="{{ old('device_id', $device->device_id ?? '') }}" 
                                           {{ isset($device) ? 'readonly' : '' }} required>
                                    @error('device_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Device Name *</label>
                                    <input type="text" name="device_name" class="form-control @error('device_name') is-invalid @enderror" 
                                           value="{{ old('device_name', $device->device_name ?? '') }}" required>
                                    @error('device_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Unit Code</label>
                                    <input type="text" name="unit_code" class="form-control @error('unit_code') is-invalid @enderror" 
                                           value="{{ old('unit_code', $device->unit_code ?? '') }}">
                                    @error('unit_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Plate No</label>
                                    <input type="text" name="plate_no" class="form-control @error('plate_no') is-invalid @enderror" 
                                           value="{{ old('plate_no', $device->plate_no ?? '') }}">
                                    @error('plate_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <select name="location" class="form-control @error('location') is-invalid @enderror">
                                        <option value="">-- Select Location --</option>
                                        <option value="JO SELATAN" {{ old('location', $device->location ?? '') === 'JO SELATAN' ? 'selected' : '' }}>JO SELATAN</option>
                                        <option value="M.SERVICE" {{ old('location', $device->location ?? '') === 'M.SERVICE' ? 'selected' : '' }}>M.SERVICE</option>
                                        <option value="MUD" {{ old('location', $device->location ?? '') === 'MUD' ? 'selected' : '' }}>MUD</option>
                                        <option value="SELATAN" {{ old('location', $device->location ?? '') === 'SELATAN' ? 'selected' : '' }}>SELATAN</option>
                                        <option value="STB_001" {{ old('location', $device->location ?? '') === 'STB_001' ? 'selected' : '' }}>STB_001</option>
                                        <option value="STB_SITE" {{ old('location', $device->location ?? '') === 'STB_SITE' ? 'selected' : '' }}>STB_SITE</option>
                                        <option value="UTARA" {{ old('location', $device->location ?? '') === 'UTARA' ? 'selected' : '' }}>UTARA</option>
                                    </select>
                                    @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Series</label>
                                    <select name="series" class="form-control @error('series') is-invalid @enderror">
                                        <option value="">-- Select Series --</option>
                                        <option value="DT HINO" {{ old('series', $device->series ?? '') === 'DT HINO' ? 'selected' : '' }}>DT HINO</option>
                                        <option value="DT VOLVO" {{ old('series', $device->series ?? '') === 'DT VOLVO' ? 'selected' : '' }}>DT VOLVO</option>
                                        <option value="HD 465" {{ old('series', $device->series ?? '') === 'HD 465' ? 'selected' : '' }}>HD 465</option>
                                        <option value="HD 785" {{ old('series', $device->series ?? '') === 'HD 785' ? 'selected' : '' }}>HD 785</option>
                                        <option value="OHT 773" {{ old('series', $device->series ?? '') === 'OHT 773' ? 'selected' : '' }}>OHT 773</option>
                                    </select>
                                    @error('series') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Device Group *</label>
                                    <select name="group_id" class="form-control @error('group_id') is-invalid @enderror" required onchange="updateGroupName()">
                                        <option value="">-- Select Group --</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group->id }}" data-name="{{ $group->group_name }}"
                                                {{ (old('group_id', $device->group_id ?? '') == $group->id) ? 'selected' : '' }}>
                                                {{ $group->group_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Group Name (Auto-filled)</label>
                                    <input type="text" name="group_name" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">IMEI</label>
                                    <input type="text" name="imei" class="form-control @error('imei') is-invalid @enderror" 
                                           value="{{ old('imei', $device->imei ?? '') }}">
                                    @error('imei') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">SIM Number</label>
                                    <input type="text" name="sim_number" class="form-control @error('sim_number') is-invalid @enderror" 
                                           value="{{ old('sim_number', $device->sim_number ?? '') }}">
                                    @error('sim_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="">-- Select Status --</option>
                                <option value="active" {{ old('status', $device->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $device->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('admin.device.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">{{ isset($device) ? 'Update' : 'Create' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateGroupName() {
    const select = document.querySelector('[name="group_id"]');
    const selectedOption = select.options[select.selectedIndex];
    const groupNameInput = document.querySelector('[name="group_name"]');
    groupNameInput.value = selectedOption.dataset.name || '';
}

// Call on page load
document.addEventListener('DOMContentLoaded', updateGroupName);
</script>
@endsection
