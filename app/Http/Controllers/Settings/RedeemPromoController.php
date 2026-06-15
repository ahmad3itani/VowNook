<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\PromoRedemption;
use App\Support\PlanComp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedeemPromoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);

        $user = $request->user();
        $code = PromoCode::where('code', strtoupper(trim($data['code'])))->first();

        if ($code === null || ! $code->isRedeemableBy($user)) {
            throw ValidationException::withMessages([
                'code' => 'That code isn’t valid, has expired, or has already been used.',
            ]);
        }

        DB::transaction(function () use ($code, $user) {
            PromoRedemption::create(['promo_code_id' => $code->id, 'user_id' => $user->id]);
            $code->increment('redeemed_count');
            PlanComp::grant($user, $code->plan, $code->duration_days);
        });

        return back()->with('status', 'promo-redeemed');
    }
}
