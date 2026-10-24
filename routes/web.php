
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TourAdminController;
use App\Http\Controllers\SeccionAdminController;
use App\Http\Controllers\ReclamoController;
use App\Http\Controllers\TraduccionController;
use App\Http\Controllers\UsuarioAdminController;
use App\Http\Controllers\ResenaController;
use App\Http\Controllers\ResenaAdminController;
use App\Http\Controllers\ReclamoAdminController;
use App\Http\Middleware\SetLocale;

// Cambio de idioma (pt, es, en)
Route::get('/idioma/{lang}', function (string $lang) {
    if (in_array($lang, SetLocale::SUPPORTED, true)) {
        session(['locale' => $lang]);
    }

    $prev = url()->previous();

    return redirect(str_starts_with($prev, url('/')) ? $prev : route('inicio'));
})->name('idioma');

Route::middleware(SetLocale::class)->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('inicio');

    Route::get('/tours', [TourController::class, 'index'])->name('tours');
    Route::get('/tour/{slug}', [TourController::class, 'show'])->name('tour.detalle');
    Route::get('/tour-detalle/{slug}', fn (string $slug) => redirect()->route('tour.detalle', $slug, 301)); // enlaces antiguos

    Route::get('/nosotros', [HomeController::class, 'nosotros'])->name('nosotros');
    Route::view('/terminos-y-condiciones', 'terminos')->name('terminos');
    Route::view('/politica-de-privacidad', 'privacidad')->name('privacidad');
    Route::view('/codigo-esnna', 'esnna')->name('esnna');
    Route::get('/opiniones', [ResenaController::class, 'index'])->name('opiniones');
    Route::post('/opiniones', [ResenaController::class, 'store'])->middleware('throttle:3,10')->name('opiniones.store');
    Route::get('/libro-de-reclamaciones', [ReclamoController::class, 'create'])->name('reclamaciones');
    Route::post('/libro-de-reclamaciones', [ReclamoController::class, 'store'])->middleware('throttle:5,10')->name('reclamaciones.store');

    Route::get('/reservas', [ReservaController::class, 'create'])->name('reservas');
    Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');

    Route::get('/contacto', [ContactoController::class, 'create'])->name('contacto');
    Route::post('/contacto', [ContactoController::class, 'store'])->name('contacto.store');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');

    // Solo administradores: borrar contenido y gestionar usuarios
    Route::middleware('role:admin')->group(function () {
        Route::get('/usuarios', [UsuarioAdminController::class, 'index'])->name('usuarios');
        Route::post('/usuarios', [UsuarioAdminController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UsuarioAdminController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{id}', [UsuarioAdminController::class, 'destroy'])->name('usuarios.delete');
        Route::delete('/tours/{id}', [TourAdminController::class, 'destroy'])->name('tours.delete');
        Route::delete('/secciones/{id}', [SeccionAdminController::class, 'destroy'])->name('secciones.delete');
        Route::delete('/resenas/{id}', [ResenaAdminController::class, 'destroy'])->name('resenas.delete');
    });

    // Tours (el orden importa: 'crear' antes de '{id}')
    Route::get('/tours', [TourAdminController::class, 'index'])->name('tours');
    Route::post('/tours/traducir', [TraduccionController::class, 'traducir'])->name('tours.traducir');
    Route::get('/tours/crear', [TourAdminController::class, 'create'])->name('tours.create');
    Route::post('/tours', [TourAdminController::class, 'store'])->name('tours.store');
    Route::get('/tours/{id}/editar', [TourAdminController::class, 'edit'])->name('tours.edit');
    Route::get('/tours/{id}/vista-previa', [TourAdminController::class, 'preview'])->middleware(SetLocale::class)->name('tours.preview');
    Route::put('/tours/{id}', [TourAdminController::class, 'update'])->name('tours.update');
    Route::patch('/tours/{id}/estado', [TourAdminController::class, 'estado'])->name('tours.estado');
    Route::post('/tours/{id}/duplicar', [TourAdminController::class, 'duplicar'])->name('tours.duplicar');

    // Secciones del inicio
    Route::get('/secciones', [SeccionAdminController::class, 'index'])->name('secciones');
    Route::post('/secciones', [SeccionAdminController::class, 'store'])->name('secciones.store');
    Route::put('/secciones/{id}', [SeccionAdminController::class, 'update'])->name('secciones.update');

    Route::get('/resenas', [ResenaAdminController::class, 'index'])->name('resenas');
    Route::patch('/resenas/{id}', [ResenaAdminController::class, 'update'])->name('resenas.update');

    Route::get('/reclamos', [ReclamoAdminController::class, 'index'])->name('reclamos');
    Route::get('/reclamos/{id}', [ReclamoAdminController::class, 'show'])->name('reclamos.show');
    Route::patch('/reclamos/{id}', [ReclamoAdminController::class, 'update'])->name('reclamos.update');

    Route::get('/reservas', [AdminController::class, 'reservas'])->name('reservas');
    Route::get('/reservas/exportar', [AdminController::class, 'exportReservas'])->name('reservas.export');
    Route::patch('/reservas/{id}', [AdminController::class, 'updateReserva'])->name('reservas.update');

    Route::get('/contactos', [AdminController::class, 'contactos'])->name('contactos');
    Route::patch('/contactos/{id}', [AdminController::class, 'updateContacto'])->name('contactos.update');
});
