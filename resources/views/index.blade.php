@extends('layouts.app')
<style>
    body {
        background-color: #DFE4EA;
        display: flex;
        /* Activa Flexbox */
        justify-content: center;
        /* Centrado horizontal */
        align-items: center;
        /* Centrado vertical */
        height: 100vh;
        /* Ocupa toda la altura de la ventana */
        margin: 0;
        /* Quita espacios */
    }

    .login {
        width: 400px;
        height: 450px;
        padding: 10px;
        border-radius: 20px;
        background-color: #ffffff;
        text-align: left;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    input {
        border-color: #e9e9e927; 
        width: 250px;
        height: 40px;
        border-radius: 5px;
        padding: 10px;
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .btn-login {
        width: 250px;
        height: 40px;
        border-radius: 5px;
        border: none;
        background-color: #2A327E;
        color: #ffffff;
        cursor: pointer;
        margin-top: 10px;
        margin-bottom: 10px;
    }
    img{
        margin-top: 10px;
        margin-bottom: 30px;
    }
</style>
@section('title', 'Login')

@section('content')
<x-navbar />
<div class="login">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md text-center">
        {{-- Logo --}}
        <div class="mb-6">
            <img src="{{ asset('images/logo.jpg') }}" alt="Vigía Plus Logistics" class="mx-auto h-14">
        </div>

        {{-- Formulario de login --}}
        <form method="POST" action="#">
            @csrf
            <div class="mb-4 text-left">
                <label for="email" class="block text-sm text-gray-700">Correo electrónico</label>
                <div class="relative">
                    <input id="email" type="email" name="email" placeholder="correo"
                        class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                        required autofocus>
                    <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <div class="mb-4 text-left">
                <label for="password" class="block text-sm text-gray-700">Contraseña</label>
                <div class="relative">
                    <input id="password" type="password" name="password" placeholder="••••••••"
                        class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-400"
                        required>
                    <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <div class="mb-4 text-right">
                <a href="#" class="text-sm text-gray-500 hover:underline">
                    Olvidé mi contraseña
                </a>
            </div>

            <button type="submit" class="btn-login">
                Iniciar sesión
            </button>
        </form>
    </div>
</div>
@endsection