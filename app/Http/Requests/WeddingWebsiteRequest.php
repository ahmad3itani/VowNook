<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WeddingWebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_published' => ['boolean'],
            'template' => ['nullable', 'string', 'in:classic,modern,botanical,blush,royal,dolce,destination,vibrant'],
            'headline' => ['nullable', 'string', 'max:200'],
            'welcome_message' => ['nullable', 'string', 'max:2000'],
            'our_story' => ['nullable', 'string', 'max:5000'],
            'venue_name' => ['nullable', 'string', 'max:200'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'ceremony_time' => ['nullable', 'string', 'max:100'],
            'dress_code' => ['nullable', 'string', 'max:100'],
            'hero_image_url' => ['nullable', 'url', 'max:2048'],
            'hero_video_url' => ['nullable', 'url', 'max:2048'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'music_title' => ['nullable', 'string', 'max:120'],
            'timeline_items' => ['nullable', 'array', 'max:20'],
            'timeline_items.*.year' => ['required_with:timeline_items.*', 'string', 'max:20'],
            'timeline_items.*.title' => ['required_with:timeline_items.*', 'string', 'max:120'],
            'timeline_items.*.body' => ['nullable', 'string', 'max:1000'],
            'travel_notes' => ['nullable', 'string', 'max:5000'],
            'show_travel_stays' => ['sometimes', 'boolean'],
            'faq_items' => ['nullable', 'array', 'max:30'],
            'faq_items.*.question' => ['required_with:faq_items.*', 'string', 'max:200'],
            'faq_items.*.answer' => ['nullable', 'string', 'max:2000'],
            'local_recommendations' => ['nullable', 'array', 'max:30'],
            'local_recommendations.*.title' => ['required_with:local_recommendations.*', 'string', 'max:160'],
            'local_recommendations.*.category' => ['nullable', 'string', 'max:60'],
            'local_recommendations.*.description' => ['nullable', 'string', 'max:1000'],
            'local_recommendations.*.url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = ['is_published' => $this->boolean('is_published')];

        // Only coerce the travel toggle when the form actually sent it, so other
        // saves never silently flip it off.
        if ($this->has('show_travel_stays')) {
            $merge['show_travel_stays'] = $this->boolean('show_travel_stays');
        }

        $this->merge($merge);
    }
}
