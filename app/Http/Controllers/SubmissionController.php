<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\QuoteRequestMail;
use App\Mail\ReviewMail;
use App\Mail\CareerApplicationMail;

class SubmissionController extends Controller {
    public function index() {
        return response()->json(Submission::orderBy('created_at', 'desc')->get());
    }

    public function storeContact(Request $request) {
        $validated = $request->validate([
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:30',
            'company' => 'required|string|max:100',
            'serviceRequired' => 'required|string|max:100',
            'postcode' => 'required|string|max:15',
            'message' => 'required|string|min:10',
            'landingPageSlug' => 'nullable|string|max:100',
            'utmSource' => 'nullable|string|max:100',
            'utmMedium' => 'nullable|string|max:100',
            'utmCampaign' => 'nullable|string|max:100',
        ]);

        $name = trim($validated['firstName'] . ' ' . $validated['lastName']);

        $submission = Submission::create([
            'type' => 'contact',
            'landing_page_slug' => $validated['landingPageSlug'] ?? null,
            'name' => $name,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'company' => $validated['company'],
            'service' => $validated['serviceRequired'],
            'postcode' => $validated['postcode'],
            'message' => $validated['message'],
            'utm_source' => $validated['utmSource'] ?? null,
            'utm_medium' => $validated['utmMedium'] ?? null,
            'utm_campaign' => $validated['utmCampaign'] ?? null,
        ]);

        // Dispatch email notifications
        try {
            Mail::to(env('MAIL_TO', 'info@expets.co.uk'))->send(new QuoteRequestMail($submission));
        } catch (\Exception $e) {
            // Keep going even if email fails so DB is not rolled back
        }

        return response()->json(['message' => 'Quote request saved successfully'], 201);
    }

    public function storeLandingPageLead(Request $request) {
        $validated = $request->validate([
            'firstName'       => 'nullable|string|max:100',
            'lastName'        => 'nullable|string|max:100',
            'name'            => 'nullable|string|max:150',
            'email'           => 'required|email|max:150',
            'phone'           => 'required|string|max:30',
            'company'         => 'required|string|max:150',
            'serviceRequired' => 'nullable|string|max:150',
            'service'         => 'nullable|string|max:150',
            'postcode'        => 'required|string|max:20',
            'message'         => 'nullable|string|max:3000',
            'landingPageSlug' => 'nullable|string|max:100',
            'landing_page_slug' => 'nullable|string|max:100',
            'utmSource'       => 'nullable|string|max:100',
            'utm_source'      => 'nullable|string|max:100',
            'utmMedium'       => 'nullable|string|max:100',
            'utm_medium'      => 'nullable|string|max:100',
            'utmCampaign'     => 'nullable|string|max:100',
            'utm_campaign'    => 'nullable|string|max:100',
        ]);

        $fullName = !empty($validated['name'])
            ? $validated['name']
            : trim(($validated['firstName'] ?? '') . ' ' . ($validated['lastName'] ?? ''));

        $slug = $validated['landingPageSlug'] ?? $validated['landing_page_slug'] ?? null;
        $service = $validated['serviceRequired'] ?? $validated['service'] ?? 'Commercial & Office Cleaning';

        $submission = Submission::create([
            'type'              => 'contact',
            'landing_page_slug' => $slug,
            'name'              => $fullName,
            'email'             => $validated['email'],
            'phone'             => $validated['phone'],
            'company'           => $validated['company'],
            'service'           => $service,
            'postcode'          => $validated['postcode'],
            'message'           => $validated['message'] ?? 'Landing page quote request',
            'utm_source'        => $validated['utmSource'] ?? $validated['utm_source'] ?? null,
            'utm_medium'        => $validated['utmMedium'] ?? $validated['utm_medium'] ?? null,
            'utm_campaign'      => $validated['utmCampaign'] ?? $validated['utm_campaign'] ?? null,
        ]);

        try {
            Mail::to(env('MAIL_TO', 'info@expets.co.uk'))->send(new QuoteRequestMail($submission));
        } catch (\Exception $e) {
            // Keep going so DB submission is saved regardless of SMTP status
        }

        return response()->json([
            'message' => 'Landing page lead recorded successfully',
            'submission' => $submission
        ], 201);
    }

    public function storeReview(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|min:10'
        ]);

        $submission = Submission::create([
            'type' => 'review',
            'name' => $validated['name'],
            'email' => $validated['email'] ?? '',
            'rating' => $validated['rating'],
            'message' => $validated['message']
        ]);

        try {
            Mail::to(env('MAIL_TO', 'info@expets.co.uk'))->send(new ReviewMail($submission));
        } catch (\Exception $e) {
            // Keep going
        }

        return response()->json(['message' => 'Review registered successfully'], 201);
    }

    public function storeCareer(Request $request) {
        $validated = $request->validate([
            'fullName'        => 'nullable|string|max:150',
            'name'            => 'nullable|string|max:150',
            'email'           => 'required|email|max:150',
            'phone'           => 'required|string|max:30',
            'postcode'        => 'required|string|max:20',
            'roleAppliedFor'  => 'nullable|string|max:150',
            'service'         => 'nullable|string|max:150',
            'availability'    => 'nullable|string|max:100',
            'experienceLevel' => 'nullable|string|max:100',
            'hasRightToWork'  => 'nullable',
            'hasDrivingLicence' => 'nullable',
            'notes'           => 'nullable|string|max:3000',
            'cvFile'          => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $name = !empty($validated['fullName']) ? $validated['fullName'] : ($validated['name'] ?? '');
        if (empty($name)) {
            return response()->json(['error' => 'Full name is required.'], 422);
        }

        $role = !empty($validated['roleAppliedFor']) ? $validated['roleAppliedFor'] : ($validated['service'] ?? 'Cleaning Operative / Cleaner');
        $hasRightToWork = filter_var($validated['hasRightToWork'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $hasDrivingLicence = filter_var($validated['hasDrivingLicence'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $cvPath = null;
        $cvOriginalName = null;
        if ($request->hasFile('cvFile') && $request->file('cvFile')->isValid()) {
            $file = $request->file('cvFile');
            $cvOriginalName = $file->getClientOriginalName();
            $cvPath = $file->store('resumes', 'public');
        }

        $submission = Submission::create([
            'type'                => 'career',
            'name'                => $name,
            'email'               => $validated['email'],
            'phone'               => $validated['phone'],
            'service'             => $role,
            'postcode'            => $validated['postcode'],
            'message'             => $validated['notes'] ?? 'Career application for ' . $role,
            'availability'        => $validated['availability'] ?? 'Flexible',
            'experience_level'    => $validated['experienceLevel'] ?? '1 - 2 years',
            'has_right_to_work'   => $hasRightToWork,
            'has_driving_licence' => $hasDrivingLicence,
            'cv_path'             => $cvPath,
            'cv_original_name'    => $cvOriginalName,
        ]);

        try {
            Mail::to(env('MAIL_TO', 'info@expets.co.uk'))->send(new CareerApplicationMail($submission));
        } catch (\Exception $e) {
            // Keep going so submission record is preserved
        }

        return response()->json([
            'message' => 'Job application recorded successfully',
            'submission' => $submission,
        ], 201);
    }

    public function downloadCv($id) {
        $submission = Submission::findOrFail($id);

        if (!$submission->cv_path || !Storage::disk('public')->exists($submission->cv_path)) {
            return response()->json(['error' => 'No CV file found for this applicant.'], 404);
        }

        $filename = $submission->cv_original_name ?: basename($submission->cv_path);
        return Storage::disk('public')->download($submission->cv_path, $filename);
    }
}
