<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuoteRequestMail;
use App\Mail\ReviewMail;

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
            'message' => 'required|string|min:10'
        ]);

        $name = trim($validated['firstName'] . ' ' . $validated['lastName']);

        $submission = Submission::create([
            'type' => 'contact',
            'name' => $name,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'company' => $validated['company'],
            'service' => $validated['serviceRequired'],
            'postcode' => $validated['postcode'],
            'message' => $validated['message']
        ]);

        // Dispatch email notifications
        try {
            Mail::to(env('MAIL_TO', 'info@expets.co.uk'))->send(new QuoteRequestMail($submission));
        } catch (\Exception $e) {
            // Keep going even if email fails so DB is not rolled back
        }

        return response()->json(['message' => 'Quote request saved successfully'], 201);
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
}
