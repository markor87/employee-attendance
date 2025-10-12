<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users with pagination, search, and filter.
     */
    public function index(Request $request)
    {
        $perPage = 10;
        $search = $request->input('search');
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');

        $query = User::query();

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('FirstName', 'LIKE', "%{$search}%")
                  ->orWhere('LastName', 'LIKE', "%{$search}%")
                  ->orWhere('Email', 'LIKE', "%{$search}%");
            });
        }

        // Role filter
        if ($roleFilter) {
            $query->where('Role', $roleFilter);
        }

        // Status filter
        if ($statusFilter) {
            $query->where('Status', $statusFilter);
        }

        // Order by UserID
        $query->orderBy('UserID', 'asc');

        // Get pagination data
        $users = $query->paginate($perPage);

        // Ensure currentPage doesn't exceed totalPages
        $totalPages = $users->lastPage();
        $currentPage = $users->currentPage();

        if ($currentPage > $totalPages && $totalPages > 0) {
            return redirect()->route('users.index', [
                'page' => $totalPages,
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
            ]);
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'FirstName' => 'required|string|max:255',
            'LastName' => 'required|string|max:255',
            'Email' => 'required|email|unique:Users,Email',
            'Password' => [
                'required',
                'string',
                'min:8',
                'regex:/[0-9]/', // At least one digit
                'regex:/[!@#$%^&*()_+\-=\[\]{};:\\\'"|,.<>\/?]/', // At least one special char
            ],
            'Role' => 'required|in:SuperAdmin,Admin,Kadrovik,Zaposleni',
        ]);

        // Create user (Status is always 'Odjavljen' for new users)
        $user = User::create([
            'FirstName' => $validated['FirstName'],
            'LastName' => $validated['LastName'],
            'Email' => $validated['Email'],
            'PasswordHash' => Hash::make($validated['Password']),
            'PasswordHashAlgorithm' => 'bcrypt',
            'Role' => $validated['Role'],
            'Status' => 'Odjavljen',
            'PasswordNeedsChange' => false,
        ]);

        return back()->with('success', 'Корисник је успешно креиран.');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        // CRITICAL: Protect UserID = 1
        if ($id == 1) {
            return back()->withErrors(['error' => 'Не можете изменити SuperAdmin корисника.']);
        }

        $user = User::findOrFail($id);

        // Validation
        $validated = $request->validate([
            'FirstName' => 'required|string|max:255',
            'LastName' => 'required|string|max:255',
            'Email' => 'required|email|unique:Users,Email,' . $id . ',UserID',
            'Password' => [
                'nullable',
                'string',
                'min:8',
                'regex:/[0-9]/',
                'regex:/[!@#$%^&*()_+\-=\[\]{};:\\\'"|,.<>\/?]/',
            ],
            'Role' => 'required|in:SuperAdmin,Admin,Kadrovik,Zaposleni',
        ]);

        // Update user (Status is not modified - it's managed by check-in/check-out system)
        $user->FirstName = $validated['FirstName'];
        $user->LastName = $validated['LastName'];
        $user->Email = $validated['Email'];
        $user->Role = $validated['Role'];

        // Update password if provided
        if (!empty($validated['Password'])) {
            $user->PasswordHash = Hash::make($validated['Password']);
            $user->PasswordHashAlgorithm = 'bcrypt';
            $user->PasswordNeedsChange = false;
        }

        $user->save();

        return back()->with('success', 'Корисник је успешно ажуриран.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        // CRITICAL: Protect UserID = 1
        if ($id == 1) {
            return back()->withErrors(['error' => 'Не можете обрисати SuperAdmin корисника.']);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'Корисник је успешно обрисан.');
    }

    /**
     * Force password change for the specified user.
     */
    public function forcePasswordChange($id)
    {
        // CRITICAL: Protect UserID = 1
        if ($id == 1) {
            return back()->withErrors(['error' => 'Не можете форсирати промену лозинке за SuperAdmin корисника.']);
        }

        $user = User::findOrFail($id);
        $user->PasswordNeedsChange = true;
        $user->save();

        return back()->with('success', 'Корисник ће морати да промени лозинку при следећем логовању.');
    }
}
