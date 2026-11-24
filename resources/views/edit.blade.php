@extends('layouts.main')

@section('title', __('Edit Phonebook'))
@section('page-heading', __('Edit Phonebook'))

@section('content')
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">@lang('Edit Phonebook')</h1>
        <p class="text-body-secondary mb-0">{{ $phonebook->name }}</p>
    </div>

    <x-card>
        <form method="POST" action="{{ route('phonebook.update', $phonebook) }}">
            @csrf
            @method('PUT')
            <div class="row row-cols-md-2 g-3 pb-4">
                <x-select name="enabled" id="edit-enabled" label="Enabled" :value="$phonebook->enabled">
                    <option value="1" @selected((string) old('enabled', (int) $phonebook->enabled) === '1')>Yes</option>
                    <option value="0" @selected((string) old('enabled', (int) $phonebook->enabled) === '0')>No</option>
                </x-select>
                <x-input name="name" id="edit-name" :value="$phonebook->name" label="Name" icon="fa-solid fa-tag" required />
                <x-input name="avatar" id="edit-avatar" :value="$phonebook->avatar" label="Avatar" icon="fa-solid fa-image" />
            </div>
            <div class="pb-4">
                <x-textarea name="data" id="edit-data" value="{!! old('data', $phonebook->data === null ? '' : json_encode($phonebook->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !!}" icon="fa-solid fa-file-lines" rows="5" />
            </div>
            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="{{ route('phonebook.index') }}" class="btn btn-light">@lang('Cancel')</a>
                <x-button variant="dark" type="submit">@lang('Save changes')</x-button>
            </div>
        </form>
    </x-card>
@endsection