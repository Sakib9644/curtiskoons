<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\RegistrationNotificationEvent;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use App\Notifications\RegistrationNotification;
use Illuminate\Support\Facades\DB;
use App\Traits\SMS;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{

    use SMS;

    public $select;
    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'name', 'email', 'otp', 'avatar', 'otp_verified_at', 'last_activity_at', 'date', 'sex'];
    }

    public function register(Request $request)
    {

        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|string|email|max:150|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'dob'     => 'required|date',
            'sex'      => 'required|in:Male,Female,Others',
        ]);


        try {
            DB::beginTransaction();

            // Generate unique slug
            do {
                $slug = "user_" . rand(1000000000, 9999999999);
            } while (User::where('slug', $slug)->exists());

            // Create user
            $user = User::create([
                'name'               => $request->input('name'),
                'slug'               => $slug,
                'email'              => strtolower($request->input('email')),
                'password'           => Hash::make($request->input('password')),
                'otp'                => rand(1000, 9999),
                'otp_expires_at'     => Carbon::now()->addMinutes(60),
                'status'             => 'active',
                'date'               => $request->dob,
                'sex'                => $request->sex,
                'last_activity_at'   => Carbon::now()
            ]);

            // Assign default role 'user'
            $userRole = Role::where('name', 'user')->first();
            if ($userRole) {
                $user->assignRole($userRole);
            }

            // Notify admins
            $notiData = [
                'user_id' => $user->id,
                'title' => 'User registered successfully.',
                'body' => 'A new user has registered.'
            ];

         
            // Send OTP email
            Mail::to($user->email)->send(new OtpMail($user->otp, $user, 'Verify Your Email Address'));

            DB::commit();

            // Generate API token
            $token = auth('api')->login($user);

            $data = User::select($this->select)->find($user->id);

            return response()->json([
                'status'     => true,
                'message'    => 'User registered successfully.',
                'code'       => 200,
                'token_type' => 'bearer',
                'token'      => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'data'       => $data
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return Helper::jsonErrorResponse('User registration failed', 500, [$e->getMessage()]);
        }
    }

    public function VerifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|digits:4',
        ]);
        try {
            $user = User::where('email', $request->input('email'))->first();

            //! Check if email has already been verified
            if (!empty($user->otp_verified_at)) {
                return  Helper::jsonErrorResponse('Email already verified.', 409);
            }

            if ((string)$user->otp !== (string)$request->input('otp')) {
                return Helper::jsonErrorResponse('Invalid OTP code', 422);
            }

            //* Check if OTP has expired
            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return Helper::jsonErrorResponse('OTP has expired. Please request a new OTP.', 422);
            }

            //* Verify the email
            $user->otp_verified_at   = now();
            $user->otp               = null;
            $user->otp_expires_at    = null;
            $user->save();

            $token = auth('api')->login($user);

            return Helper::jsonResponse(true, 'Email verification successful.', 200, null, false, null, $token);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), $e->getCode());
        }
    }

    public function ResendOtp(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                return Helper::jsonErrorResponse('User not found.', 404);
            }

            if ($user->otp_verified_at) {
                return Helper::jsonErrorResponse('Email already verified.', 409);
            }

            $newOtp               = rand(1000, 9999);
            $otpExpiresAt         = Carbon::now()->addMinutes(60);
            $user->otp            = $newOtp;
            $user->otp_expires_at = $otpExpiresAt;
            $user->save();

            //* Send the new OTP to the user's email
            Mail::to($user->email)->send(new OtpMail($newOtp, $user, 'Verify Your Email Address'));

            return Helper::jsonResponse(true, 'A new OTP has been sent to your email.', 200);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 200);
        }
    }
}
