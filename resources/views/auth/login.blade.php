@extends('auth.app')

@section('content')
<style>
    /* PAGE WRAPPER */
    .login-page-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
    }

    .login-container {
        width: 100%;
        max-width: 460px;
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
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        object-fit: contain;
    }

    /* CARD */
    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 30px;
        text-align: center;
    }

    .login100-form-title h2 {
        color: white;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
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
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 8px;
        display: block;
        color: #333;
    }

    .form-control-custom {
        width: 100%;
        height: 48px;
        padding-left: 52px;
        padding-right: 14px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #d1d1d1;
        font-size: 15px;
        transition: all .3s;
    }

    .form-control-custom:focus {
        border-color: #667eea;
        background: #fff;
        box-shadow: 0 0 6px rgba(102,126,234,0.25);
        outline: none;
    }

    /* ICON INSIDE INPUT */
    .input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 19px;
        color: #777;
        pointer-events: none;
        z-index: 3;
    }

    .form-control-custom:focus ~ .input-icon {
        color: #667eea;
    }

    /* ERROR */
    .text-danger {
        font-size: 13px;
        margin-top: 4px;
        display: block;
        color: #c33 !important;
    }

    /* FORGOT PASSWORD */
    .text-end a {
        color: #667eea;
        font-size: 14px;
        text-decoration: none;
        font-weight: 500;
    }
    .text-end a:hover { color: #764ba2; }

    /* BUTTON */
    .login100-form-btn {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        cursor: pointer;
        transition: 0.3s;
        box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
    }

    .login100-form-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.55);
    }

</style>

<div class="login-page-wrapper">
    <div class="login-container">

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
