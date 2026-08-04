<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

final class SettingsController extends ApiController
{
    public function show(Request $request)
    {
        return $this->ok(['settings' => $request->user()->settings ?? []]);
    }

    public function update(Request $request)
    {
        $settings = $request->validate(['settings' => ['required', 'array']])['settings'];
        $request->user()->update(['settings' => [...($request->user()->settings ?? []), ...$settings]]);

        return $this->show($request);
    }

    public function profile(Request $request)
    {
        $data = $request->validate(['name' => ['sometimes', 'string', 'max:255'], 'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$request->user()->id]]);
        $request->user()->update($data);

        return $this->ok(['user' => $request->user()->fresh()]);
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::defaults()]]);
        $request->user()->update(['password' => Hash::make($data['password'])]);

        return $this->ok(['updated' => true]);
    }
}
