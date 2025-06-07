@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Create Account</h2>
                    
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.submit') }}" id="registerForm">
                        @csrf
                        <meta name="csrf-token" content="{{ csrf_token() }}">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password', 'toggleIcon1')">
                                    <i class="bi bi-eye-slash" id="toggleIcon1"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" 
                                       id="password_confirmation" name="password_confirmation" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password_confirmation', 'toggleIcon2')">
                                    <i class="bi bi-eye-slash" id="toggleIcon2"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_admin" name="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_admin">Register as Administrator</label>
                            </div>
                        </div>

                        <div class="mb-3 admin-key-group" style="{{ old('is_admin') ? 'display: block;' : 'display: none;' }}">
                            <label for="admin_key" class="form-label">Admin Key</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('admin_key') is-invalid @enderror" 
                                       id="admin_key" name="admin_key" value="{{ old('admin_key') }}">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('admin_key', 'toggleIcon3')">
                                    <i class="bi bi-eye-slash" id="toggleIcon3"></i>
                                </button>
                                @error('admin_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>

                    <div class="text-center mt-3">
                        <p class="mb-0">Already have an account? <a href="{{ route('login') }}" class="text-success">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<style>
    .card {
        border: none;
        border-radius: 10px;
    }
    .btn-success {
        background-color: #28a745;
    }
    .btn-success:hover {
        background-color: #218838;
    }
    .input-group .btn-outline-secondary {
        border-color: #ced4da;
    }
    .input-group .btn-outline-secondary:hover {
        background-color: #f8f9fa;
    }
    .invalid-feedback {
        display: block;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add CSRF token to all AJAX requests
    let token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    document.querySelectorAll('form').forEach(form => {
        if (!form.querySelector('input[name="_token"]')) {
            let csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = token;
            form.appendChild(csrfInput);
        }
    });

    // Password visibility toggle function
    window.togglePasswordVisibility = function(fieldId, iconId) {
        const passwordInput = document.getElementById(fieldId);
        const toggleIcon = document.getElementById(iconId);
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        }
    }

    // Admin checkbox and key field handling
    const adminCheckbox = document.getElementById('is_admin');
    const adminKeyGroup = document.querySelector('.admin-key-group');
    const adminKeyInput = document.getElementById('admin_key');

    adminCheckbox.addEventListener('change', function() {
        adminKeyGroup.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            adminKeyInput.value = '';
        }
    });

    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        if (adminCheckbox.checked && !adminKeyInput.value) {
            e.preventDefault();
            alert('Please enter the Admin Key');
            adminKeyInput.focus();
        }
    });
});
</script>
@endpush 