<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Transporter;
use App\Helpers\AjaxResponse;
use Illuminate\Http\Request;
use App\Services\DataTables\TransporterDataTable;
use Illuminate\Support\Str;
use App\Http\Requests\StoreTransporterRequest;
use App\Http\Requests\UpdateTransporterRequest;
use Throwable;

class TransporterController extends Controller
{
    protected TransporterDataTable $dataTable;

    public function __construct(TransporterDataTable $dataTable)
    {
        $this->dataTable = $dataTable;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Transporter::query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $transporters = Transporter::where('company_id', company_id())->orderBy('name')->get();

        return view('company.pages.masters.transporter.index', compact('transporters'));
    }

    public function create()
    {
        $data = [
            'form_mode' => 'create',
            'title'     => 'Add New Transport',
            'uuid'      => (string) Str::uuid(),
            'states'    => State::orderBy('name')->get(),
        ];

        $html = view('company.pages.masters.transporter._modal', ['modalData' => $data])->render();
        return AjaxResponse::success(data: ['html' => $html]);
    }

    public function store(StoreTransporterRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = current_user_id();

        try {
            $transporter = Transporter::create($validated);
            return AjaxResponse::success('Transporter created successfully.', $transporter);
        } catch (\Throwable $e) {
            return AjaxResponse::error('Failed to create transporter: ' . $e->getMessage());
        }
    }

    public function edit(Transporter $transporter)
    {
        $data = [
            'form_mode' => 'edit',
            'title'     => 'Edit Transport',
            'data'      => $transporter,
            'states'    => State::orderBy('name')->get(),
        ];

        $html = view('company.pages.masters.transporter._modal', ['modalData' => $data])->render();
        return AjaxResponse::success(data: ['html' => $html]);
    }

    public function update(UpdateTransporterRequest $request, Transporter $transporter)
    {
        $validated = $request->validated();
        $validated['updated_by'] = current_user_id();

        try {
            $transporter->update($validated);
            return AjaxResponse::success('Transporter updated successfully.', $transporter);
        } catch (\Throwable $e) {
            return AjaxResponse::error('Failed to update transporter: ' . $e->getMessage());
        }
    }

    public function show(Transporter $transporter)
    {
        $data = [
            'form_mode' => 'view',
            'title'     => 'View Transport',
            'data'      => $transporter,
            'states'    => State::orderBy('name')->get(),
        ];

        $html = view('company.pages.masters.transporter._modal', ['modalData' => $data])->render();
        return AjaxResponse::success(data: ['html' => $html]);
    }

    public function destroy(Transporter $transporter)
    {
        dd('Work In Progress');
        try {
            $transporter->update(['deleted_by' => current_user_id()]);
            $transporter->delete();
            return AjaxResponse::success('Transporter deleted successfully.');
        } catch (\Throwable $e) {
            return AjaxResponse::error('Failed to delete transporter.');
        }
    }
}
