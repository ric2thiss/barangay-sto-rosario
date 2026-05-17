<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SsoController extends Controller
{
    /**
     * Generate a signed SSO token and redirect to the treasury-system.
     * 
     * The token is a HMAC-signed payload containing:
     *   - resident_id (from profiling-system.residents)
     *   - username
     *   - name
     *   - timestamp (for expiration check)
     * 
     * The treasury-system will validate the token and auto-login the resident.
     */
    public function redirectToTreasury(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Look up the resident record from profiling-system using the user's username
        $resident = DB::connection('sto_rosario')
            ->table('residents')
            ->where('username', $user->username)
            ->where('is_deleted', 0)
            ->first();

        if (!$resident) {
            return back()->with('error', 'No linked resident account found for treasury access.');
        }

        // Build the SSO payload
        $payload = [
            'resident_id' => $resident->id,
            'username'    => $resident->username,
            'name'        => trim(implode(' ', array_filter([
                $resident->first_name,
                $resident->middle_name,
                $resident->surname,
                $resident->suffix ?? '',
            ]))),
            'user_type'   => $user->hasRole('Resident') ? 'resident' : 'official',
            'timestamp'   => time(),
        ];
        // Encode and sign
        $encoded = base64_encode(json_encode($payload));
        $secret  = config('app.key');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }
        $signature = hash_hmac('sha256', $encoded, $secret);

        $token = $encoded . '.' . $signature;

        // Get the base URL from env, fallback to relative if not set
        $baseUrl = rtrim(env('TREASURY_SYSTEM_URL', '/treasury-system'), '/');
        $treasuryUrl = $baseUrl . '/resident/sso_login.php?token=' . urlencode($token);

        return redirect($treasuryUrl);
    }
}
