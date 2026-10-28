<?php
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\AlmacenamientoController;
use App\Http\Controllers\InterbloqueosController;
use App\Http\Controllers\MemoriaController;
use App\Http\Controllers\PlanificacionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BitacoraController;
use App\Http\Middleware\RolMiddleware;
use App\Http\Controllers\ProcesosController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'mostrarLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    // Ruta de prueba: solo el rol Administrador puede entrar (se puede borrar al final)
    Route::get('/prueba-admin', fn () => 'Acceso de administrador correcto')
        ->middleware(RolMiddleware::class . ':admin')
        ->name('prueba.admin');

    // Bitácora: solo Administrador
    Route::get('/bitacora', [BitacoraController::class, 'index'])
        ->middleware(RolMiddleware::class . ':admin')
        ->name('bitacora.index');

    // ===== M6 Gestión de usuarios (solo Administrador) =====
    Route::middleware(RolMiddleware::class . ':admin')
        ->prefix('usuarios')
        ->name('usuarios.')
        ->group(function () {
            Route::get('/', [UsuariosController::class, 'index'])->name('index');
            Route::get('/crear', [UsuariosController::class, 'create'])->name('create');
            Route::post('/', [UsuariosController::class, 'store'])
                ->middleware('throttle:20,1')
                ->name('store');
        });
    // ===== fin gestión de usuarios =====

	    // ===== M3 Planificación =====
    Route::get('/planificacion', [PlanificacionController::class, 'index'])->name('planificacion.index');
    Route::post('/planificacion/simular', [PlanificacionController::class, 'simular'])->name('planificacion.simular');
    Route::get('/planificacion/{simulacion}', [PlanificacionController::class, 'ver'])->name('planificacion.ver');
    // ===== fin M3 =====

	    // ===== M2 CPU y Memoria =====
    Route::get('/memoria', [MemoriaController::class, 'index'])->name('memoria.index');
    Route::get('/memoria/datos', [MemoriaController::class, 'datos'])
        ->middleware('throttle:120,1')
        ->name('memoria.datos');
    // ===== fin M2 =====

	// ===== M1 Procesos =====
    Route::get('/procesos', [ProcesosController::class, 'index'])->name('procesos.index');
    Route::post('/procesos/lanzar', [ProcesosController::class, 'lanzar'])
        ->middleware(RolMiddleware::class . ':admin')
        ->name('procesos.lanzar');
    Route::post('/procesos/senal', [ProcesosController::class, 'senal'])
        ->middleware(RolMiddleware::class . ':admin')
        ->name('procesos.senal');
    Route::post('/procesos/prioridad', [ProcesosController::class, 'prioridad'])
        ->middleware(RolMiddleware::class . ':admin')
        ->name('procesos.prioridad');
    // ===== fin M1 =====

	    // ===== M4 Interbloqueos =====
    Route::get('/interbloqueos', [InterbloqueosController::class, 'index'])->name('interbloqueos.index');
    Route::post('/interbloqueos/banquero', [InterbloqueosController::class, 'banquero'])->name('interbloqueos.banquero');
    Route::post('/interbloqueos/solicitud', [InterbloqueosController::class, 'solicitud'])->name('interbloqueos.solicitud');
    Route::post('/interbloqueos/detectar', [InterbloqueosController::class, 'detectar'])->name('interbloqueos.detectar');
    // ===== fin M4 =====

	    // ===== M5 Almacenamiento =====
    Route::get('/almacenamiento', [AlmacenamientoController::class, 'index'])->name('almacenamiento.index');
    Route::get('/almacenamiento/usuarios', [AlmacenamientoController::class, 'usuarios'])->name('almacenamiento.usuarios');
    Route::get('/almacenamiento/explorador', [AlmacenamientoController::class, 'explorador'])
        ->middleware('throttle:60,1')
        ->name('almacenamiento.explorador');
    // ===== fin M5 =====
});
