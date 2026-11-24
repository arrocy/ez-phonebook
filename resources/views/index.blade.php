@extends('layouts.main')

@section('title', __('Phonebooks'))
@section('page-heading', __('Phonebooks overview'))

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">@lang('Phonebooks')</h1>
            <p class="text-body-secondary mb-0">@lang('Search and manage Phonebooks across your workspace.')</p>
        </div>
        <x-button variant="dark" type="button" data-bs-toggle="modal" data-bs-target="#createRecordModal">
            <i class="fa-solid fa-plus me-2"></i>@lang('Add New Phonebook')
        </x-button>
    </div>

    <x-card>
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
            <form action="{{ route('phonebook.index') }}" method="GET" class="d-flex gap-2">
                <div class="flex-grow-1">
                    <x-input name="search" id="search" type="search" label="" :value="request('search')" icon="fa-solid fa-magnifying-glass text-body-secondary" placeholder="Search Phonebooks..." aria-label="Search Phonebooks" />
                </div>
                <x-button variant="secondary" outline type="submit">@lang('Search')</x-button>
            </form>
            <div class="d-flex gap-2">
                <x-button variant="secondary" outline type="button" data-bs-toggle="modal" data-bs-target="#importRecordsModal">
                    <i class="fa-solid fa-file-import me-2"></i>@lang('Import')
                </x-button>
                <a href="{{ route('phonebook.export') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-file-export me-2"></i>@lang('Export')</a>
            </div>
        </div>

        <form action="{{ route('phonebook.bulk-destroy') }}" method="POST" data-bulk-delete-form>
            @csrf
            @method('DELETE')
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-body-secondary">{{ $phonebooks->total() }} @lang('Phonebooks found')</span>
                <x-button variant="danger" outline size="sm" type="submit" data-bulk-delete-button hidden><i class="fa-solid fa-trash-can me-1"></i>@lang('Bulk Delete')</x-button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead>
                        <tr>
                            <th scope="col" style="width:2.5rem"><input class="form-check-input" type="checkbox" aria-label="Select all rows" data-select-all="input[name='ids[]']"></th>
                            <th scope="col">@lang('Name')</th>
                            <th scope="col">@lang('Data')</th>
                            <th scope="col" class="text-end">@lang('Actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($phonebooks as $phonebook)
                            <tr>
                                <td><input class="form-check-input" type="checkbox" name="ids[]" value="{{ $phonebook->getKey() }}" aria-label="Select row"></td>
                                <td class="text-body-secondary"><div class="d-flex align-items-center gap-2"><x-avatar :src="$phonebook->avatar" :name="$phonebook->name" />{{ $phonebook->name }}</div></td>
                                <td class="text-body-secondary">{{ $phonebook->data === null ? '' : \Illuminate\Support\Str::limit(json_encode($phonebook->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 37, '...') }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('phonebook.toggle', $phonebook) }}" class="btn btn-sm btn-outline-{{ $phonebook->enabled ? 'success' : 'secondary' }}" title="Phonebook {{ $phonebook->enabled ? 'enabled' : 'disabled' }}. Click to change!" aria-label="Toggle enabled"><i class="fa-solid fa-toggle-{{ $phonebook->enabled ? 'on' : 'off' }}"></i></a>
                                    <a href="{{ route('phonebook.edit', $phonebook) }}" class="btn btn-sm btn-outline-primary" title="Edit phonebook" aria-label="Edit phonebook"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <x-button variant="danger" outline size="sm" type="button" data-bs-toggle="modal" data-bs-target="#deleteRecordModal" :data-delete-url="route('phonebook.destroy', $phonebook)" :data-delete-name="$phonebook->name" title="Delete phonebook" aria-label="Delete phonebook"><i class="fa-solid fa-trash-can"></i></x-button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state title="No Phonebooks found" description="Try another search or add a new Phonebook." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-top pt-3 mt-2">
                <span class="small text-body-secondary">@lang('Showing') {{ $phonebooks->firstItem() ?? 0 }}–{{ $phonebooks->lastItem() ?? 0 }} @lang('of') {{ $phonebooks->total() }}</span>
                {{ $phonebooks->links('pagination::bootstrap-5') }}
            </div>
        </form>
    </x-card>

    <x-modal id="deleteRecordModal" title="Delete Phonebook?" data-delete-modal>
        <form id="delete-phonebook-form" method="POST" action="{{ route('phonebook.index') }}" data-delete-form>
            @csrf
            @method('DELETE')
            @lang('Are you sure you want to delete') <strong data-delete-name></strong>? @lang('This action cannot be undone.')
        </form>
        <x-slot:footer>
            <x-button variant="light" type="button" data-bs-dismiss="modal">@lang('Cancel')</x-button>
            <x-button variant="danger" type="submit" form="delete-phonebook-form">@lang('Delete Phonebook')</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal id="importRecordsModal" title="Import Phonebooks">
        <form id="import-phonebook-form" method="POST" action="{{ route('phonebook.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <x-input name="name" id="import-name" label="Name" icon="fa-solid fa-tag" required />
                <label class="form-label" for="import-file">@lang('CSV file')</label>
                <input class="form-control @error('file') is-invalid @enderror" type="file" id="import-file" name="file" accept=".csv,text/csv" required>
                @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <p class="small text-body-secondary mb-2">@lang('CSV columns and example row:')</p>
            <pre class="bg-body-tertiary p-3 rounded small"><code>name,phone,street,city,state,zip,country
Grace Hopper,555-0101,1 Main St,Arlington,VA,22201,USA</code></pre>
        </form>
        <x-slot:footer>
            <x-button variant="light" type="button" data-bs-dismiss="modal">@lang('Cancel')</x-button>
            <x-button variant="dark" type="submit" form="import-phonebook-form">@lang('Import')</x-button>
        </x-slot:footer>
    </x-modal>

    <x-modal id="createRecordModal" title="Add New Phonebook">
        <form id="create-phonebook-form" method="POST" action="{{ route('phonebook.store') }}">
            @csrf
            <x-input name="name" id="new-name" label="Name" icon="fa-solid fa-tag" required />
        </form>
        <x-slot:footer>
            <x-button variant="light" type="button" data-bs-dismiss="modal">@lang('Cancel')</x-button>
            <x-button variant="dark" type="submit" form="create-phonebook-form">@lang('Create Phonebook')</x-button>
        </x-slot:footer>
    </x-modal>
@endsection