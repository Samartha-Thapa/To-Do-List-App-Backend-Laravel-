<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:5',
        ]);

        $code = random_int(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_code' => $code,
        ]);

        // Send verification email
        Mail::to($user->email)->send(new \App\Mail\VerifyCodeMail($code));

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully! Please check your email for verification code.',
            'user' => $user,
        ], 201);
    }

    public function verifyCode(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User not found'
            ], 404);
        }

        if($user->verification_code != $request->code) {
            return response()->json([
                'success' => false, 
                'message' => 'Invalid verification code!'
            ], 400);
        }
        
        // Update user verification status
        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->save();

        // Create token after successful verification
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'success' => true, 
            'message' => 'Email verified successfully', 
            'access_token' => $token, 
            'user' => $user
        ]);
    }

    // Add login method for email/password authentication
    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if email is verified
        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email first',
            ], 403);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'message' => 'Login successful',
            'access_token' => $token,
        ]);
    }

    public function update(Request $request, $id) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|max:255|unique:users,email,{$id}",
        ]);

        $user = User::findOrFail($id);

        if($request->user()->id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user->update($validated);

        return response()->json($user, 200);
    }
}