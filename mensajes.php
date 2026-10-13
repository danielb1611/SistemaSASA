<?php

declare(strict_types=1);

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/functions.php';

require_login();

$stmt = $pdo->prepare('SELECT id, nombre, apellido, rol, departamento FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$actual = $stmt->fetch();

if (!$actual) {
    $_SESSION = [];
    session_destroy();
    redirect('login.php');
}

$esAdmin = $actual['rol'] === 'admin';
$usuarioId = (int) $actual['id'];

$hayRespondidos = false;
if ($esAdmin) {
    $hayRespondidos = (int) $pdo->query("SELECT COUNT(*) FROM mensajes WHERE respuesta IS NOT NULL AND respuesta <> ''")->fetchColumn() > 0;
}

$busqueda = trim((string) ($_GET['q'] ?? ''));
$estado = (string) ($_GET['estado'] ?? 'todos');

$filtros = [
    'todos'       => 'Todos',
    'sin_leer'    => 'Sin leer',
    'leidos'      => 'Leidos',
    'respondidos' => 'Respondidos',
];

if (!array_key_exists($estado, $filtros)) {
    $estado = 'todos';
}

$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busqueda) . '%';

if ($esAdmin) {
    $condiciones = [];

    switch ($estado) {
        case 'sin_leer':
            $condiciones[] = 'm.leido = 0';
            break;
        case 'leidos':
            $condiciones[] = 'm.leido = 1';
            break;
        case 'respondidos':
            $condiciones[] = 'm.respuesta IS NOT NULL';
            break;
    }

    $params = [];

    if ($busqueda !== '') {
        $condiciones[] = '(m.asunto LIKE ? OR m.cuerpo LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like);
    }

    $sql = 'SELECT m.id, m.asunto, m.cuerpo, m.leido, m.respuesta, m.respondido_en, m.creado_en, '
        . 'u.nombre AS remitente_nombre, u.apellido AS remitente_apellido, u.departamento AS remitente_departamento, u.email AS remitente_email '
        . 'FROM mensajes m JOIN usuarios u ON u.id = m.usuario_id';

    if ($condiciones) {
        $sql .= ' WHERE ' . implode(' AND ', $condiciones);
    }

    $sql .= ' ORDER BY m.creado_en DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $mensajes = $stmt->fetchAll();
} else {
    $condiciones = ['m.usuario_id = ?'];
    $params = [$usuarioId];

    if ($busqueda !== '') {
        $condiciones[] = '(m.asunto LIKE ? OR m.cuerpo LIKE ?)';
        array_push($params, $like, $like);
    }

    $sql = 'SELECT m.id, m.asunto, m.cuerpo, m.leido, m.respuesta, m.respuesta_vista, m.respondido_en, m.creado_en, '
        . 'a.nombre AS admin_nombre, a.apellido AS admin_apellido '
        . 'FROM mensajes m LEFT JOIN usuarios a ON a.id = m.respondido_por '
        . 'WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY m.creado_en DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $mensajes = $stmt->fetchAll();
}

function estado_mensaje(array $fila): array
{
    if ($fila['respuesta'] !== null && $fila['respuesta'] !== '') {
        return ['Respondido', 'badge--respondido'];
    }

    if (!empty($fila['leido'])) {
        return ['Leido', 'badge--leido'];
    }

    return ['Sin leer', 'badge--nuevo'];
}

$tituloPagina = 'Buzon';

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main class="dashboard dashboard--wide">
    <section class="card card--wide">
        <div class="section-head">
            <div>
                <h1 class="card__title card__title--icon"><svg class="card__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>Mensajes</h1>
                <p class="card__subtitle">
                    <?= $esAdmin
                        ? 'Mensajes enviados por los usuarios.'
                        : 'Envia mensajes al administrador y consulta sus respuestas.' ?>
                </p>
            </div>
            <div class="section-head__tools">
                <?php if ($esAdmin): ?>
                    <button type="button" class="btn btn--red btn--icon" data-modal-open="modal-vaciar-buzon"<?= $hayRespondidos ? '' : ' title="No hay mensajes respondidos para eliminar" disabled' ?>><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>Eliminar</button>
                <?php endif; ?>
                <?php if (!$esAdmin): ?>
                    <button type="button" class="btn btn--blue btn--icon" data-modal-open="modal-nuevo-mensaje"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>Nuevo mensaje</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="alert" id="buzon-aviso" role="status" hidden></div>

        <?php if ($esAdmin): ?>
            <div class="buzon-filtros">
                <?php foreach ($filtros as $clave => $etiqueta): ?>
                    <?php
                    $urlFiltro = 'mensajes.php?estado=' . urlencode($clave);
                    if ($busqueda !== '') {
                        $urlFiltro .= '&q=' . urlencode($busqueda);
                    }
                    ?>
                    <a href="<?= e($urlFiltro) ?>" class="btn btn--ghost btn--sm<?= $estado === $clave ? ' is-active' : '' ?>"><?= e($etiqueta) ?></a>
                <?php endforeach; ?>
            </div>

            <form method="get" action="mensajes.php" class="search-form search-form--table">
                <input type="hidden" name="estado" value="<?= e($estado) ?>">
                <input type="search" name="q" value="<?= e($busqueda) ?>" class="search-form__input" placeholder="Buscar por remitente, asunto o texto..." aria-label="Buscar mensajes">
                <button type="submit" class="btn btn--primary btn--sm btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>Buscar</button>
                <?php if ($busqueda !== ''): ?>
                    <a href="mensajes.php?estado=<?= e($estado) ?>" class="btn btn--outline btn--sm btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Limpiar</a>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <?php if ($mensajes): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <?php if ($esAdmin): ?>
                                <th>Remitente</th>
                            <?php endif; ?>
                            <th>Asunto</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mensajes as $fila): ?>
                            <?php
                            [$etiquetaEstado, $claseEstado] = estado_mensaje($fila);
                            $esNueva = !$esAdmin && !empty($fila['respuesta']) && empty($fila['respuesta_vista']);
                            $datos = [
                                'id' => (int) $fila['id'],
                                'asunto' => $fila['asunto'] !== '' ? $fila['asunto'] : '(Sin asunto)',
                                'cuerpo' => $fila['cuerpo'],
                                'creado_en' => $fila['creado_en'],
                                'leido' => (int) $fila['leido'],
                                'respuesta' => $fila['respuesta'],
                                'respondido_en' => $fila['respondido_en'],
                                'es_admin' => $esAdmin,
                            ];
                            if ($esAdmin) {
                                $datos['remitente'] = $fila['remitente_nombre'] . ' ' . $fila['remitente_apellido'];
                                $datos['remitente_departamento'] = $fila['remitente_departamento'];
                                $datos['remitente_email'] = $fila['remitente_email'];
                            } else {
                                $datos['respuesta_vista'] = (int) $fila['respuesta_vista'];
                                $datos['admin'] = trim((string) $fila['admin_nombre'] . ' ' . (string) $fila['admin_apellido']);
                            }
                            ?>
                            <tr data-mensaje-row="<?= e((string) $fila['id']) ?>">
                                <?php if ($esAdmin): ?>
                                    <td class="buzon-remitente">
                                        <strong><?= e($fila['remitente_nombre'] . ' ' . $fila['remitente_apellido']) ?></strong><br>
                                        <span class="muted"><?= e($fila['remitente_departamento']) ?></span>
                                    </td>
                                <?php endif; ?>
                                <td><?= e($fila['asunto'] !== '' ? $fila['asunto'] : '(Sin asunto)') ?></td>
                                <td><?= e($fila['creado_en']) ?></td>
                                <td>
                                    <span class="badge <?= e($claseEstado) ?>"><?= e($etiquetaEstado) ?></span>
                                    <?php if ($esNueva): ?>
                                        <span class="badge badge--nuevo" data-nueva="<?= e((string) $fila['id']) ?>">Nueva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-actions">
                                    <button type="button" class="btn btn--outline btn--sm" data-modal-open="modal-ver-mensaje" data-ver="<?= e(json_encode($datos, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">Ver</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="table__empty"><?= $esAdmin ? 'No hay mensajes.' : 'Todavia no enviaste mensajes.' ?></p>
        <?php endif; ?>
    </section>
</main>

<?php if (!$esAdmin): ?>
<div class="modal" id="modal-nuevo-mensaje" data-modal>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modal-nuevo-mensaje-titulo">
        <header class="modal__header">
            <h2 class="card__title card__title--icon" id="modal-nuevo-mensaje-titulo"><svg class="card__title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>Nuevo mensaje</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">&times;</button>
        </header>

        <div class="alert alert--error" id="nuevo-mensaje-error" role="alert" hidden></div>

        <form class="form" id="form-nuevo-mensaje" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="field">
                <label for="mensaje-asunto">Asunto</label>
                <input type="text" id="mensaje-asunto" name="asunto" maxlength="50" autocomplete="off" placeholder="Tema del mensaje" required>
            </div>
            <div class="field">
                <label for="mensaje-cuerpo">Mensaje</label>
                <textarea id="mensaje-cuerpo" name="cuerpo" rows="6" maxlength="100" class="textarea" placeholder="Escribi tu mensaje al administrador..." required></textarea>
                <small class="field__hint">Maximo 100 caracteres.</small>
            </div>
            <div class="form__actions">
                <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn--primary" id="nuevo-mensaje-btn">Enviar mensaje</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="modal" id="modal-ver-mensaje" data-modal>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog modal__dialog--wide modal__dialog--scroll" role="dialog" aria-modal="true" aria-labelledby="modal-ver-mensaje-titulo">
        <header class="modal__header">
            <h2 class="card__title" id="modal-ver-mensaje-titulo">Mensaje</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">&times;</button>
        </header>

        <div class="alert alert--error" id="ver-mensaje-error" role="alert" hidden></div>

        <p class="buzon-detalle__meta" id="ver-mensaje-meta"></p>

        <div class="buzon-detalle__bloque">
            <span class="field__label">Mensaje</span>
            <p class="buzon-detalle__texto" id="ver-mensaje-cuerpo"></p>
        </div>

        <div class="buzon-detalle__bloque" id="ver-respuesta-wrap" hidden>
            <span class="field__label">Respuesta del administrador</span>
            <p class="buzon-detalle__texto" id="ver-respuesta-texto"></p>
            <p class="field__hint" id="ver-respuesta-meta"></p>
        </div>

        <?php if ($esAdmin): ?>
            <form class="form" id="form-responder">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" id="ver-mensaje-id" value="">
                <div class="field">
                    <label for="ver-respuesta-input">Responder</label>
                    <textarea id="ver-respuesta-input" name="respuesta" rows="4" maxlength="2000" class="textarea" placeholder="Escribi la respuesta al usuario..."></textarea>
                </div>
                <div class="form__actions">
                    <button type="button" class="btn btn--outline btn--sm" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn btn--primary" id="ver-responder-btn">Enviar respuesta</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($esAdmin): ?>
<div class="modal" id="modal-vaciar-buzon" data-modal>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modal-vaciar-buzon-titulo">
        <header class="modal__header">
            <h2 class="card__title" id="modal-vaciar-buzon-titulo">Eliminar Buzon</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">&times;</button>
        </header>

        <div class="alert alert--error" id="vaciar-buzon-error" role="alert" hidden></div>

        <p class="buzon-detalle__texto">Esta accion elimina de forma permanente <strong>solo</strong> los mensajes que ya fueron respondidos. Los pendientes se conservan. No se puede deshacer.</p>

        <div class="form__actions form__actions--confirm">
            <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
            <button type="button" class="btn btn--red" id="vaciar-buzon-btn">Vaciar buzon</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    (function () {
        var esAdmin = <?= $esAdmin ? 'true' : 'false' ?>;
        var csrfToken = <?= json_encode(csrf_token()) ?>;
        var aviso = document.getElementById('buzon-aviso');

        function mostrarAviso(texto, esError) {
            if (!aviso) {
                return;
            }
            aviso.className = 'alert ' + (esError ? 'alert--error' : 'alert--success');
            aviso.textContent = texto;
            aviso.hidden = false;
            setTimeout(function () {
                aviso.hidden = true;
            }, 4000);
        }

        function abrirModal(id) {
            var modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('is-open');
            }
        }

        function cerrarModal(modal) {
            modal.classList.remove('is-open');
        }

        function post(url, datos) {
            var body = new URLSearchParams();
            body.append('csrf_token', csrfToken);
            Object.keys(datos).forEach(function (clave) {
                body.append(clave, datos[clave]);
            });
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).then(function (res) { return res.json(); });
        }

        document.addEventListener('click', function (event) {
            var abrir = event.target.closest('[data-modal-open]');
            if (!abrir) {
                return;
            }
            var modal = document.getElementById(abrir.getAttribute('data-modal-open'));
            if (!modal) {
                return;
            }
            var error = modal.querySelector('.alert--error');
            if (error) {
                error.hidden = true;
            }
            modal.classList.add('is-open');

            if (abrir.getAttribute('data-modal-open') === 'modal-ver-mensaje') {
                var d = JSON.parse(abrir.getAttribute('data-ver'));
                abrirDetalle(d);
            }
        });

        function abrirDetalle(d) {
            document.getElementById('modal-ver-mensaje-titulo').textContent = d.asunto;
            var meta = 'Fecha: ' + d.creado_en;
            if (d.es_admin) {
                meta = 'De: ' + d.remitente + ' (' + d.remitente_departamento + ' - ' + d.remitente_email + ')\n' + meta;
            } else if (d.admin) {
                meta = meta + ' · Para: ' + d.admin;
            }
            document.getElementById('ver-mensaje-meta').textContent = meta;
            document.getElementById('ver-mensaje-cuerpo').textContent = d.cuerpo;

            var wrapRespuesta = document.getElementById('ver-respuesta-wrap');
            var textoRespuesta = document.getElementById('ver-respuesta-texto');
            var metaRespuesta = document.getElementById('ver-respuesta-meta');
            if (d.respuesta) {
                wrapRespuesta.hidden = false;
                textoRespuesta.textContent = d.respuesta;
                metaRespuesta.textContent = d.respondido_en ? 'Respondido el ' + d.respondido_en : '';
            } else {
                wrapRespuesta.hidden = true;
            }

            if (esAdmin) {
                document.getElementById('ver-mensaje-id').value = d.id;
                document.getElementById('ver-respuesta-input').value = '';
                if (!d.leido) {
                    post('marcar_mensaje.php', { id: d.id, campo: 'leido', valor: 1 });
                }
            } else if (d.respuesta && !d.respuesta_vista) {
                post('marcar_mensaje.php', { id: d.id, campo: 'respuesta_vista' });
                var nueva = document.querySelector('[data-nueva="' + d.id + '"]');
                if (nueva) {
                    nueva.remove();
                }
            }
        }

        document.querySelectorAll('[data-modal]').forEach(function (modal) {
            modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
                el.addEventListener('click', function () {
                    cerrarModal(modal);
                });
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('[data-modal].is-open').forEach(function (modal) {
                    cerrarModal(modal);
                });
            }
        });

        var formNuevo = document.getElementById('form-nuevo-mensaje');
        if (formNuevo) {
            formNuevo.addEventListener('submit', function (event) {
                event.preventDefault();
                var error = document.getElementById('nuevo-mensaje-error');
                error.hidden = true;

                var asunto = document.getElementById('mensaje-asunto').value.trim();
                if (asunto.length < 1) {
                    error.textContent = 'Escribi el asunto del mensaje.';
                    error.hidden = false;
                    return;
                }

                var cuerpo = document.getElementById('mensaje-cuerpo').value.trim();
                if (cuerpo.length < 1) {
                    error.textContent = 'Escribi el mensaje antes de enviarlo.';
                    error.hidden = false;
                    return;
                }

                var btn = document.getElementById('nuevo-mensaje-btn');
                btn.disabled = true;

                post('enviar_mensaje.php', {
                    asunto: document.getElementById('mensaje-asunto').value.trim(),
                    cuerpo: cuerpo
                }).then(function (data) {
                    btn.disabled = false;
                    if (!data.ok) {
                        error.textContent = data.mensaje;
                        error.hidden = false;
                        return;
                    }
                    formNuevo.reset();
                    cerrarModal(document.getElementById('modal-nuevo-mensaje'));
                    mostrarAviso('Mensaje enviado al administrador.', false);
                    setTimeout(function () { window.location.reload(); }, 800);
                }).catch(function () {
                    btn.disabled = false;
                    error.textContent = 'Error de conexion. Intenta de nuevo.';
                    error.hidden = false;
                });
            });
        }

        var formResponder = document.getElementById('form-responder');
        if (formResponder) {
            formResponder.addEventListener('submit', function (event) {
                event.preventDefault();
                var error = document.getElementById('ver-mensaje-error');
                error.hidden = true;

                var respuesta = document.getElementById('ver-respuesta-input').value.trim();
                if (respuesta.length < 1) {
                    error.textContent = 'Escribi la respuesta antes de enviarla.';
                    error.hidden = false;
                    return;
                }

                var btn = document.getElementById('ver-responder-btn');
                btn.disabled = true;

                post('responder_mensaje.php', {
                    id: document.getElementById('ver-mensaje-id').value,
                    respuesta: respuesta
                }).then(function (data) {
                    btn.disabled = false;
                    if (!data.ok) {
                        error.textContent = data.mensaje;
                        error.hidden = false;
                        return;
                    }
                    cerrarModal(document.getElementById('modal-ver-mensaje'));
                    mostrarAviso('Respuesta enviada.', false);
                    setTimeout(function () { window.location.reload(); }, 800);
                }).catch(function () {
                    btn.disabled = false;
                    error.textContent = 'Error de conexion. Intenta de nuevo.';
                    error.hidden = false;
                });
            });
        }

        var btnVaciar = document.getElementById('vaciar-buzon-btn');
        if (btnVaciar) {
            btnVaciar.addEventListener('click', function () {
                if (btnVaciar.classList.contains('is-loading')) {
                    return;
                }
                var error = document.getElementById('vaciar-buzon-error');
                error.hidden = true;
                btnVaciar.classList.add('is-loading');
                btnVaciar.disabled = true;

                post('vaciar_buzon.php', {}).then(function (data) {
                    btnVaciar.classList.remove('is-loading');
                    btnVaciar.disabled = false;
                    if (!data.ok) {
                        error.textContent = data.mensaje;
                        error.hidden = false;
                        return;
                    }
                    cerrarModal(document.getElementById('modal-vaciar-buzon'));
                    mostrarAviso('Buzon vaciado.', false);
                    setTimeout(function () { window.location.reload(); }, 800);
                }).catch(function () {
                    btnVaciar.classList.remove('is-loading');
                    btnVaciar.disabled = false;
                    error.textContent = 'Error de conexion. Intenta de nuevo.';
                    error.hidden = false;
                });
            });
        }

        document.querySelectorAll('.search-form--table').forEach(function (buscarForm) {
            var buscarBtn = buscarForm.querySelector('button[type="submit"]');
            var buscarInput = buscarForm.querySelector('input[type="search"]');
            if (!buscarBtn) {
                return;
            }

            if (buscarInput) {
                var actualizarBotonBuscar = function () {
                    buscarBtn.disabled = buscarInput.value.trim().length < 1;
                };
                buscarInput.addEventListener('input', actualizarBotonBuscar);
                actualizarBotonBuscar();
            }

            buscarForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (buscarBtn.classList.contains('is-loading')) {
                    return;
                }
                buscarBtn.classList.add('is-loading');
                buscarBtn.innerHTML = '<span class="btn__spinner" aria-hidden="true"></span>Buscando...';
                setTimeout(function () {
                    buscarForm.submit();
                }, 1000);
            });
        });
    })();
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
