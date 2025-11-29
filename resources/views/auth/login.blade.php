@extends('auth.app')

@section('content')
<style>
    /* PAGE WRAPPER */
    body, html {
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
        background: #f0f4f8;
    }

    .login-page-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        overflow: hidden;
    }

    /* Animated background blobs */
    .login-page-wrapper::before {
        content: '';
        position: absolute;
        width: 600px;
        height: 600px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
        top: -100px;
        left: -100px;
        animation: float 8s ease-in-out infinite;
    }

    .login-page-wrapper::after {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
        bottom: -80px;
        right: -80px;
        animation: float 6s ease-in-out infinite alternate;
    }

    @keyframes float {
        0% { transform: translateY(0) translateX(0) scale(1); }
        50% { transform: translateY(20px) translateX(20px) scale(1.1); }
        100% { transform: translateY(0) translateX(0) scale(1); }
    }

    .login-container {
        width: 100%;
        max-width: 460px;
        z-index: 2;
    }

    /* LOGO */
    .logo-wrapper {
        text-align: center;
        margin-bottom: 25px;
    }

    .header-brand-img {
        width: 120px;
        height: 120px;
        background: white;
        border-radius: 50%;
        padding: 15px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        object-fit: contain;
        transition: transform 0.3s;
    }

    .header-brand-img:hover {
        transform: rotate(10deg) scale(1.05);
    }

    /* CARD */
    .login-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 25px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
        border: 3px solid transparent;
        background-clip: padding-box;
        position: relative;
    }

    .login-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 25px;
        padding: 3px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: destination-out;
        mask-composite: exclude;
        pointer-events: none;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 50px 30px;
        text-align: center;
        border-top-left-radius: 22px;
        border-top-right-radius: 22px;
        position: relative;
    }

    .card-header-custom::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 6px;
        background: rgba(255,255,255,0.5);
        border-radius: 3px;
    }

    .login100-form-title h2 {
        color: white;
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        letter-spacing: 1px;
    }

    .card-body {
        padding: 40px 30px;
    }

    /* INPUT GROUP */
    .input-group-custom {
        position: relative;
        margin-bottom: 25px;
    }

    .input-label {
        font-weight: 500;
        font-size: 14px;
        margin-bottom: 8px;
        display: block;
        color: #333;
        transition: all 0.3s;
    }

    .form-control-custom {
        width: 100%;
        height: 50px;
        padding-left: 50px;
        padding-right: 15px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #d1d1d1;
        font-size: 15px;
        transition: all 0.3s;
        outline: none;
    }

    .form-control-custom:focus {
        border-color: #667eea;
        background: #fff;
        box-shadow: 0 0 10px rgba(102,126,234,0.25);
    }

    .input-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #999;
        pointer-events: none;
        transition: all 0.3s;
    }

    .form-control-custom:focus ~ .input-icon {
        color: #667eea;
    }

    .text-danger {
        font-size: 13px;
        margin-top: 5px;
        display: block;
        color: #e74c3c !important;
    }

    /* FORGOT PASSWORD */
    .text-end a {
        color: #667eea;
        font-size: 14px;
        text-decoration: none;
        font-weight: 500;
    }
    .text-end a:hover { color: #764ba2; text-decoration: underline; }

    /* BUTTON */
    .login100-form-btn {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .login100-form-btn:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.55);
    }

    /* RESPONSIVE */
    @media(max-width: 500px){
        .card-body { padding: 30px 20px; }
        .card-header-custom { padding: 40px 20px; }
        .login100-form-title h2 { font-size: 26px; }
    }
</style>

<div class="login-page-wrapper">
    <div class="login-container">
        @php $settings = App\Models\Setting::first(); @endphp

        <!-- LOGO -->
        <div class="logo-wrapper">
            <img src="{{ asset($settings->logo ?? 'default/logo.svg') }}" class="header-brand-img">
        </div>

        <!-- CARD -->
        <div class="login-card">
            <div class="card-header-custom">
                <div class="login100-form-title">
                    <h2>Sign In</h2>
                </div>
            </div>

            <div class="card-body">

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- EMAIL -->
                    <div class="input-group-custom">
                        <label class="input-label">Email</label>
                        <input type="text" name="email" class="form-control-custom"
                            placeholder="Enter your email" value="{{ old('email') }}" required>
                        <i class="zmdi zmdi-email input-icon mt-3"></i>
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- PASSWORD -->
                    <div class="input-group-custom">
                        <label class="input-label">Password</label>
                        <input type="password" name="password" class="form-control-custom"
                            placeholder="Enter your password" required>
                        <i class="zmdi zmdi-lock input-icon mt-3"></i>
                        @error('password')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- FORGOT PASSWORD -->
                    <div class="text-end pt-1 mb-3">
                        <a href="{{ route('password.request') }}">Forgot Password?</a>
                    </div>

                    <!-- RECAPTCHA -->
                    @if(config('settings.recaptcha') === 'yes')
                        {!! htmlFormSnippet() !!}
                        @if ($errors->has('g-recaptcha-response'))
                            <span class="text-danger">{{ $errors->first('g-recaptcha-response') }}</span>
                        @endif
                    @endif

                    <!-- BUTTON -->
                    <button type="submit" class="login100-form-btn">
                        Login
                    </button>

                </form>

            </div>
        </div>

    </div>
</div>

@endsection
