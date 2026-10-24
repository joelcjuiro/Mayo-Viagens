<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioAdminController extends Controller
{
    public function index()
    {
        return view('admin.usuarios', ['usuarios' => User::orderBy('role')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in(['admin', 'editor'])],
        ], [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        User::create($datos + ['activo' => true]);

        return back()->with('success', 'Usuario creado. Ya puede entrar en /login con su correo y contraseña.');
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in(['admin', 'editor'])],
        ], ['password.min' => 'La contraseña debe tener al menos 8 caracteres.']);
        $datos['activo'] = $request->boolean('activo');

        if (blank($datos['password'])) {
            unset($datos['password']); // no se cambia
        }

        // Evitar quedarse sin administradores o bloquearse a sí mismo
        $seQuitaAdmin = $user->esAdmin() && ($datos['role'] !== 'admin' || ! $datos['activo']);
        if ($seQuitaAdmin && ($user->id === $request->user()->id || $this->adminsActivos($user->id) === 0)) {
            return back()->withErrors(['name' => 'No puedes quitarte el rol de administrador ni desactivarte a ti mismo, ni dejar el panel sin administradores.']);
        }

        $user->update($datos);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['name' => 'No puedes eliminar tu propio usuario.']);
        }
        if ($user->esAdmin() && $this->adminsActivos($user->id) === 0) {
            return back()->withErrors(['name' => 'Debe quedar al menos un administrador activo.']);
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    /** Administradores activos distintos del usuario indicado. */
    private function adminsActivos(int $excepto): int
    {
        return User::where('role', 'admin')->where('activo', true)->where('id', '!=', $excepto)->count();
    }
}
