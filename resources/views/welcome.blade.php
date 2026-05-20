@extends('layouts.app')

@section('title', 'HRMS Home')

@section('content')
<div class="container mx-auto py-12">
    <div class="flex flex-col items-center justify-center">
        <img src="/images/hrms-logo.png" alt="HRMS Logo" class="w-32 h-32 mb-6 shadow-lg border-4 border-blue-500 rounded-full">
        <h1 class="text-4xl font-bold text-blue-700 mb-4">Welcome to HRMS</h1>
        <p class="text-lg text-gray-600 mb-8">Your Human Resource Management System</p>
        <div class="space-x-4">
            <a href="/login" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">Login</a>
            <a href="/dashboard" class="bg-gray-200 text-blue-700 px-6 py-2 rounded hover:bg-gray-300 transition">Dashboard</a>
            <a href="/attendanceemployee" class="bg-gray-200 text-blue-700 px-6 py-2 rounded hover:bg-gray-300 transition">Attendance</a>
        </div>
    </div>
</div>
@endsection
