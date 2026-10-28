@push('estilos')
<style>
  .pestanas{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
  .pestanas a{padding:7px 14px;border:1px solid var(--borde);border-radius:var(--radio);color:var(--suave);text-decoration:none;font:600 13px var(--mono)}
  .pestanas a:hover{color:var(--texto);background:var(--panel2)}
  .pestanas a.activa{background:var(--acento);color:#13100d;border-color:var(--acento)}
</style>
@endpush

<nav class="pestanas" aria-label="Secciones de administración">
    <a href="{{ route('bitacora.index') }}" class="{{ request()->routeIs('bitacora.*') ? 'activa' : '' }}">Bitácora</a>
    <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'activa' : '' }}">Usuarios</a>
</nav>
