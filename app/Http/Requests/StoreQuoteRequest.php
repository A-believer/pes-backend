<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            // Step 1 — Property Info
            'propertyType'  => 'required|string|in:house,apartment,office,retail,restaurant,school,warehouse,airbnb,construction,other',
            'floorArea'     => 'required|numeric|min:1|max:99999',
            'floors'        => 'required|integer|min:1|max:100',
            'bedrooms'      => 'nullable|integer|min:0|max:100',
            'bathrooms'     => 'nullable|integer|min:0|max:100',
            'kitchens'      => 'nullable|integer|min:0|max:100',
            'livingRooms'   => 'nullable|integer|min:0|max:100',
            'officeRooms'   => 'nullable|integer|min:0|max:100',
            'meetingRooms'  => 'nullable|integer|min:0|max:100',
            'toilets'       => 'nullable|integer|min:0|max:100',

            // Step 2 — Service Details
            'serviceType'   => 'required|string|in:regular_cleaning,deep_cleaning,end_of_tenancy,move_in,move_out,post_construction,commercial_cleaning,carpet_cleaning,upholstery_cleaning,window_cleaning,pressure_washing,sanitization',
            'frequency'     => 'required|string|in:one_time,daily,weekly,bi_weekly,monthly,quarterly',
            'preferredDate' => 'nullable|date|after_or_equal:today',
            'urgency'       => 'required|string|in:same_day,24_hours,3_days,flexible',

            // Step 3 — Condition
            'overallCondition'  => 'required|string|in:excellent,good,average,poor,extremely_dirty',
            'dirtLevel'         => 'required|integer|min:1|max:10',
            'specialConditions' => 'nullable|array|max:20',
            'specialConditions.*' => 'string|in:pet_hair,mold,heavy_grease,construction_dust,smoke_stains,water_damage,hoarding,biohazard',

            // Step 4 — Add-ons
            'addOns'            => 'nullable|array|max:20',
            'addOns.*'          => 'string|in:oven_cleaning,fridge_cleaning,inside_cabinets,interior_windows,laundry,ironing,external_windows,gutter_cleaning,patio_cleaning,driveway_cleaning,garden_cleanup,floor_buffing,floor_polishing,waste_removal',

            // Step 5 — Images
            'images'            => 'required|array|min:3|max:20',
            'images.*'          => 'required|file|image|mimes:jpg,jpeg,png,webp|max:10240',

            // Step 6 — Contact
            'firstName'     => 'required|string|max:100',
            'lastName'      => 'required|string|max:100',
            'email'         => 'required|email|max:150',
            'phone'         => 'required|string|max:30',
            'postcode'      => 'required|string|max:15',
            'address'       => 'required|string|max:250',
            'city'          => 'required|string|max:100',
        ];
    }

    public function messages(): array {
        return [
            'images.min'       => 'Please upload at least 3 photos of the property.',
            'images.max'       => 'You may upload a maximum of 20 photos.',
            'images.*.max'     => 'Each photo must be under 10MB.',
            'images.*.mimes'   => 'Photos must be JPG, JPEG, PNG, or WebP format.',
            'preferredDate.after_or_equal' => 'Preferred date must be today or in the future.',
        ];
    }
}
