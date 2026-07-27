<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    /**
     * Handle SSO Callback from portal.sehatlink.cloud for Dashboard / Patologi Klinik Lab (views-lab)
     */
    public function handleCallback(Request $request)
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                return redirect()->route('login')->with('failed', 'Token SSO tidak ditemukan.');
            }

            $secret = env('SEHATLINK_PORTAL_SECRET', 'sehatlink_portal_shared_secret_key_2026');

            // 1. Split token (payload.signature)
            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                return redirect()->route('login')->with('failed', 'Format token SSO tidak valid.');
            }

            list($payloadB64, $signatureB64) = $parts;

            // 2. Verify Signature
            $expectedSignature = hash_hmac('sha256', $payloadB64, $secret, true);
            $expectedSignatureB64 = $this->base64UrlEncode($expectedSignature);

            if (!hash_equals($expectedSignatureB64, $signatureB64)) {
                Log::warning('SSO Failed (Views Lab): Signature mismatch.');
                return redirect()->route('login')->with('failed', 'Tanda tangan token SSO tidak valid.');
            }

            // 3. Decode Payload
            $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'));
            $payload = json_decode($payloadJson, true);

            if (!$payload) {
                return redirect()->route('login')->with('failed', 'Gagal memproses data SSO token.');
            }

            // 4. Check Expiry
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return redirect()->route('login')->with('failed', 'Token SSO telah kadaluarsa. Silakan masuk kembali melalui Portal.');
            }

            // 5. Identity & Lookup Official User (ika@soedarso.com)
            $username = $payload['username'] ?? 'dr.ika';
            $namaUser = $payload['name'] ?? 'dr. Ika Ridlawati, M.Sc, Sp.PK';
            $email = $payload['email'] ?? ($username . '@sehatlink.cloud');

            $user = null;

            try {
                // Search user in local DB (prioritize official Dr. Ika account: ika@soedarso.com)
                $user = User::where('email', 'ika@soedarso.com')
                    ->orWhere('email', $email)
                    ->orWhere('name', 'LIKE', '%Ika%')
                    ->orWhere('name', 'LIKE', '%' . $username . '%')
                    ->orWhere('name', $namaUser)
                    ->first();
            } catch (\Exception $e) {
                Log::warning('SSO DB search warning in Views Lab: ' . $e->getMessage());
            }

            // Auto-provisioning fallback if missing
            if (!$user) {
                try {
                    $userData = [
                        'name' => $namaUser,
                        'email' => 'ika@soedarso.com',
                        'password' => Hash::make(Str::random(16)),
                    ];

                    if (Schema::hasColumn('users', 'role')) {
                        $userData['role'] = 'superadmin';
                    }

                    $user = User::create($userData);

                    if (method_exists($user, 'assignRole')) {
                        try {
                            $user->assignRole('superadmin');
                        } catch (\Exception $re) {
                            try { $user->assignRole('admin'); } catch (\Exception $e2) {}
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('SSO Auto-provisioning error in Views Lab: ' . $e->getMessage());
                    $user = User::first();
                }
            } else {
                // Ensure Superadmin access role if role column exists
                if (Schema::hasColumn('users', 'role') && $user->role !== 'superadmin' && $user->role !== 'admin') {
                    $user->role = 'superadmin';
                    $user->save();
                }
                if (method_exists($user, 'assignRole')) {
                    try {
                        $user->assignRole('superadmin');
                    } catch (\Exception $re) {}
                }
            }

            if (!$user) {
                return redirect()->route('login')->with('failed', 'Gagal membuat atau menemukan akun pengguna di Dashboard Lab.');
            }

            // 6. Perform Login as official ika@soedarso.com user
            Auth::login($user);

            // Redirect to active dashboard route
            if (\Route::has('klinik.index')) {
                return redirect()->route('klinik.index')->with('success', 'Berhasil masuk melalui SehatLink Portal!');
            }
            if (\Route::has('dashboard.index')) {
                return redirect()->route('dashboard.index')->with('success', 'Berhasil masuk melalui SehatLink Portal!');
            }

            return redirect('/dashboard')->with('success', 'Berhasil masuk melalui SehatLink Portal!');
        } catch (\Exception $e) {
            Log::error('SSO Exception (Views Lab): ' . $e->getMessage());
            return redirect('/login')->with('failed', 'Terjadi kendala saat login SSO: ' . $e->getMessage());
        }
    }

    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
