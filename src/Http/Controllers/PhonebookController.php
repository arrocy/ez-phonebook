<?php

namespace Arrocy\Phonebook\Http\Controllers;

use App\Http\Controllers\Controller;
use Arrocy\Phonebook\Models\Phonebook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhonebookController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $phonebooks = Phonebook::query()
            ->where('user_id', auth()->id())
            ->search($request->string('search')->toString())
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('phonebook::index', compact('phonebooks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $phonebook = new Phonebook($validated);
        $phonebook->user()->associate(auth()->user());
        $phonebook->save();

        return redirect()->route('phonebook.index')->with('success', __('Phonebook created successfully.'));
    }

    public function edit(Phonebook $phonebook): View
    {
        $this->authorizeOwner($phonebook);

        return view('phonebook::edit', compact('phonebook'));
    }

    public function update(Request $request, Phonebook $phonebook): RedirectResponse
    {
        $this->authorizeOwner($phonebook);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'url:http,https'],
            'data' => ['nullable', 'json'],
        ]);

        if (isset($validated['data'])) {
            $validated['data'] = $this->decodeJsonData($validated['data']);
        }

        $phonebook->update($validated);

        return redirect()->route('phonebook.index')->with('success', __('Phonebook updated successfully.'));
    }

    public function toggle(Phonebook $phonebook): RedirectResponse
    {
        $this->authorizeOwner($phonebook);
        $phonebook->update(['enabled' => ! $phonebook->enabled]);

        return back()->with('success', __('Phonebook updated successfully.'));
    }

    public function destroy(Phonebook $phonebook): RedirectResponse
    {
        $this->authorizeOwner($phonebook);
        $phonebook->delete();

        return redirect()->route('phonebook.index')->with('success', __('Phonebook deleted successfully.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        DB::transaction(function () use ($validated): void {
            Phonebook::query()
                ->where('user_id', auth()->id())
                ->whereIn('id', $validated['ids'])
                ->delete();
        });

        return redirect()->route('phonebook.index')->with('success', __('Selected Phonebooks were deleted.'));
    }

    public function export(): StreamedResponse
    {
        return \Helper::csvDownload(
            'phonebooks.csv',
            ['id', 'user_id', 'enabled', 'name', 'avatar', 'data', 'created_at', 'updated_at'],
            Phonebook::query()
                ->where('user_id', auth()->id())
                ->orderBy('id')
                ->cursor()
                ->map(static fn (Phonebook $phonebook): array => [
                    $phonebook->getKey(),
                    $phonebook->user_id,
                    $phonebook->enabled ? 1 : 0,
                    $phonebook->name,
                    $phonebook->avatar,
                    $phonebook->data === null
                        ? null
                        : json_encode($phonebook->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $phonebook->created_at?->toDateTimeString(),
                    $phonebook->updated_at?->toDateTimeString(),
                ]),
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'extensions:csv', 'max:10240'],
        ]);

        $filePath = $validated['file']->getRealPath();
        $stream = $filePath === false ? false : fopen($filePath, 'r');

        if ($stream === false) {
            throw new \RuntimeException('Unable to open the uploaded CSV file.');
        }

        $expectedHeaders = ['name', 'phone', 'street', 'city', 'state', 'zip', 'country'];
        $records = [];
        $rowNumber = 1;

        try {
            $headers = fgetcsv($stream, null, ',', '"', '');

            if ($headers === false) {
                throw ValidationException::withMessages([
                    'file' => __('The CSV file is empty.'),
                ]);
            }

            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? (string) $headers[0];

            if ($headers !== $expectedHeaders) {
                throw ValidationException::withMessages([
                    'file' => __('The CSV headers must be: :headers.', [
                        'headers' => implode(',', $expectedHeaders),
                    ]),
                ]);
            }

            while (true) {
                $row = fgetcsv($stream, null, ',', '"', '');

                if ($row === false) {
                    if (! feof($stream)) {
                        throw new \RuntimeException('Unable to read the uploaded CSV file.');
                    }

                    break;
                }

                $rowNumber++;

                if ($row === [null]) {
                    continue;
                }

                if (count($row) !== count($expectedHeaders)) {
                    throw ValidationException::withMessages([
                        'file' => __('CSV row :row has an unexpected number of columns.', [
                            'row' => $rowNumber,
                        ]),
                    ]);
                }

                $records[] = array_combine($expectedHeaders, $row);
            }
        } finally {
            fclose($stream);
        }

        if ($records === []) {
            throw ValidationException::withMessages([
                'file' => __('The CSV file does not contain any records.'),
            ]);
        }

        $recordName = $validated['name'];

        DB::transaction(function () use ($records, $recordName): void {
            $phonebook = new Phonebook([
                'name' => $recordName,
                'data' => $records,
            ]);
            $phonebook->user()->associate(auth()->user());
            $phonebook->save();
        });

        return redirect()->route('phonebook.index')->with('success', __('Imported :count CSV rows into :name.', [
            'count' => count($records),
            'name' => $recordName,
        ]));
    }

    private function decodeJsonData(string $data, ?int $rowNumber = null): array
    {
        $decodedData = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decodedData)) {
            $field = $rowNumber === null ? 'data' : 'file';
            $message = $rowNumber === null
                ? __('The data field must contain a JSON object or array.')
                : __('CSV row :row must contain data as a JSON object or array.', ['row' => $rowNumber]);

            throw ValidationException::withMessages([$field => $message]);
        }

        return $decodedData;
    }

    private function authorizeOwner(Phonebook $phonebook): void
    {
        abort_unless($phonebook->user_id === auth()->id(), 403, __('You are not the owner of this phonebook.'));
    }
}