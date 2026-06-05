<?php

use App\Enums\PermissionLevel;
use App\Enums\Role;
use App\Enums\Section;

/**
 * Default permission matrix: Role -> Section -> PermissionLevel.
 *
 * This is the SOURCE OF TRUTH for what each role can do by default. Individual
 * memberships may carry a per-user override (stored as JSON on the wedding_user
 * pivot) which is merged on top of these defaults by the PermissionService.
 *
 * Any Section not listed for a Role falls back to PermissionLevel::None.
 */

$w = PermissionLevel::Write->value;
$r = PermissionLevel::Read->value;
$n = PermissionLevel::None->value;

return [

    'matrix' => [

        // Full control of everything.
        Role::Owner->value => array_fill_keys(Section::values(), $w),

        // Manages every working section on the couple's behalf.
        Role::Planner->value => array_fill_keys(Section::values(), $w),

        // The other half of the couple: broad access, no collaborator/settings control.
        Role::Partner->value => [
            Section::Overview->value => $w,
            Section::Budget->value => $w,
            Section::Guests->value => $w,
            Section::Seating->value => $w,
            Section::Vendors->value => $w,
            Section::Timeline->value => $w,
            Section::Checklist->value => $w,
            Section::Inspiration->value => $w,
            Section::Gallery->value => $w,
            Section::Website->value => $w,
            Section::Crew->value => $w,
            Section::Collaborators->value => $r,
            Section::Settings->value => $r,
        ],

        // A trusted helper: writes day-to-day sections, reads the rest.
        Role::Collaborator->value => [
            Section::Overview->value => $r,
            Section::Budget->value => $r,
            Section::Guests->value => $w,
            Section::Seating->value => $w,
            Section::Vendors->value => $r,
            Section::Timeline->value => $w,
            Section::Checklist->value => $w,
            Section::Inspiration->value => $w,
            Section::Gallery->value => $w,
            Section::Website->value => $r,
            Section::Crew->value => $r,
            Section::Collaborators->value => $n,
            Section::Settings->value => $n,
        ],

        // Read-only for family and friends; sensitive sections hidden.
        Role::Viewer->value => [
            Section::Overview->value => $r,
            Section::Budget->value => $n,
            Section::Guests->value => $n,
            Section::Seating->value => $r,
            Section::Vendors->value => $n,
            Section::Timeline->value => $r,
            Section::Checklist->value => $n,
            Section::Inspiration->value => $r,
            Section::Gallery->value => $r,
            Section::Website->value => $r,
            Section::Crew->value => $n,
            Section::Collaborators->value => $n,
            Section::Settings->value => $n,
        ],
    ],
];
