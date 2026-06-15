<?php

namespace App\Http\Controllers;

use App\Support\CurrentWedding;
use App\Support\MealOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Saves the couple's meal configuration (which courses are on the RSVP form and
 * the choices for each) onto wedding.settings['meals'].
 */
class MealOptionsController extends Controller
{
    public function __construct(protected CurrentWedding $current) {}

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'meals' => ['required', 'array'],
            'meals.*.enabled' => ['required', 'boolean'],
            'meals.*.options' => ['nullable', 'array', 'max:12'],
            'meals.*.options.*' => ['nullable', 'string', 'max:80'],
        ]);

        $wedding = $this->current->get();
        $meals = [];

        foreach (MealOptions::COURSES as $course) {
            $courseData = $data['meals'][$course] ?? [];
            $meals[$course] = [
                'enabled' => (bool) ($courseData['enabled'] ?? ($course === 'main')),
                'options' => MealOptions::cleanOptions($courseData['options'] ?? []),
            ];
        }

        $settings = $wedding->settings ?? [];
        $settings['meals'] = $meals;
        $wedding->update(['settings' => $settings]);

        return back()->with('status', 'meal-options-saved');
    }
}
