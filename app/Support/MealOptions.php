<?php

namespace App\Support;

use App\Models\Wedding;

/**
 * The couple's meal configuration for a wedding: which courses are on the RSVP
 * form and the choices for each. Stored on wedding.settings['meals']; this is
 * the single source of normalized config for both the couple and public sides.
 *
 * `meal_choice` on a guest is the MAIN course; appetizer/dessert have their own
 * columns. Disabling a course just hides it from the RSVP form.
 */
class MealOptions
{
    /** Ordered course keys. */
    public const COURSES = ['appetizer', 'main', 'dessert'];

    /** Human labels for each course. */
    public const LABELS = [
        'appetizer' => 'Appetizer',
        'main' => 'Main',
        'dessert' => 'Dessert',
    ];

    /**
     * Normalized config (every course present, defaults applied). Main is on by
     * default; appetizer and dessert are off until the couple enables them.
     *
     * @return array<string, array{enabled: bool, options: list<string>}>
     */
    public static function forWedding(Wedding $wedding): array
    {
        $stored = $wedding->settings['meals'] ?? [];

        $config = [];
        foreach (self::COURSES as $course) {
            $courseConfig = is_array($stored[$course] ?? null) ? $stored[$course] : [];

            $config[$course] = [
                'enabled' => (bool) ($courseConfig['enabled'] ?? ($course === 'main')),
                'options' => self::cleanOptions($courseConfig['options'] ?? []),
            ];
        }

        return $config;
    }

    /** @return list<string> course keys the couple has turned on */
    public static function enabledCourses(Wedding $wedding): array
    {
        $config = self::forWedding($wedding);

        return array_values(array_filter(
            self::COURSES,
            fn (string $course) => $config[$course]['enabled'],
        ));
    }

    /** @return list<string> the configured options for one course */
    public static function optionsFor(Wedding $wedding, string $course): array
    {
        return self::forWedding($wedding)[$course]['options'] ?? [];
    }

    /**
     * Trim, drop blanks, de-duplicate (case-insensitive) and cap the list.
     *
     * @param  mixed  $options
     * @return list<string>
     */
    public static function cleanOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        $seen = [];
        $clean = [];
        foreach ($options as $option) {
            if (! is_string($option)) {
                continue;
            }
            $value = trim($option);
            $key = mb_strtolower($value);
            if ($value === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = mb_substr($value, 0, 80);

            if (count($clean) >= 12) {
                break;
            }
        }

        return $clean;
    }
}
