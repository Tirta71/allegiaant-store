@extends('admin.layouts.app')

@section('title', 'Login')

@section('content')
    <section class="panel login-card">
        <h1>Login Admin</h1>
        <p>Gunakan akun admin</p>

        <form method="POST" action="{{ route('admin.login.store') }}" class="grid">
            @csrf
            <label>
                Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <label class="check-row">
                <input type="checkbox" name="remember" value="1">
                Ingat login saya
            </label>
            <button type="submit" class="button">Masuk admin</button>
        </form>
    </section>
@endsection
