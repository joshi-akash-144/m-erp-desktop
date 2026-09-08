<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(has_permission('email_template.list'), 403);

        if ($request->ajax()) {
            return $this->list($request);
        }

        return view('company-selector.pages.email-templates.index');
    }

    public function list(Request $request): JsonResponse
    {
        abort_unless(has_permission('email_template.list'), 403);

        $query = EmailTemplate::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $total    = $query->count();
        $page     = max(1, (int) $request->input('page', 1));
        $pageSize = (int) $request->input('pageSize', 15);

        $templates = $query->orderBy('id', 'desc')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get(['id', 'name', 'slug', 'subject', 'status', 'created_at']);

        return response()->json([
            'data'         => $templates,
            'last_page'    => (int) ceil($total / $pageSize),
            'current_page' => $page,
            'total'        => $total,
        ]);
    }

    public function create()
    {
        abort_unless(has_permission('email_template.create'), 403);

        return view('company-selector.pages.email-templates.create');
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(has_permission('email_template.create'), 403);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'slug'    => 'required|string|max:255|unique:email_templates,slug',
            'subject' => 'required|string|max:500',
            'message' => 'required|string',
        ]);

        EmailTemplate::create([
            'name'          => $validated['name'],
            'slug'          => $validated['slug'],
            'template_name' => $validated['slug'],
            'subject'       => $validated['subject'],
            'message'       => $validated['message'],
            'status'        => 0,
            'created_by'    => current_user_id(),
            'updated_by'    => current_user_id(),
        ]);

        return response()->json(['status' => true, 'message' => 'Email template created successfully.']);
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        abort_unless(has_permission('email_template.update'), 403);

        return view('company-selector.pages.email-templates.edit', compact('emailTemplate'));
    }

    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        abort_unless(has_permission('email_template.update'), 403);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'slug'    => 'required|string|max:255|unique:email_templates,slug,' . $emailTemplate->id,
            'subject' => 'required|string|max:500',
            'message' => 'required|string',
        ]);

        $emailTemplate->update([
            'name'          => $validated['name'],
            'slug'          => $validated['slug'],
            'template_name' => $validated['slug'],
            'subject'       => $validated['subject'],
            'message'       => $validated['message'],
            'updated_by'    => current_user_id(),
        ]);

        return response()->json(['status' => true, 'message' => 'Email template updated successfully.']);
    }

    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        abort_unless(has_permission('email_template.delete'), 403);

        $emailTemplate->delete();

        return response()->json(['status' => true, 'message' => 'Email template deleted successfully.']);
    }
}
