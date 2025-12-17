<template>
  <div style="max-width: 980px; margin: 24px auto; font-family: system-ui; padding: 0 12px;">
    <header style="margin-bottom: 12px;">
      <h1 style="margin: 0 0 6px;">Simulador Rover</h1>
      <p style="margin: 0; opacity: 0.9;">
        Superficie <b>200x200</b> (coordenadas de <b>0</b> a <b>199</b>). Comandos: <b>F</b> (avanzar),
        <b>L</b> (girar izquierda), <b>R</b> (girar derecha).
      </p>
      <p style="margin: 6px 0 0; opacity: 0.9;">
        API: <code>{{ apiBase || "(no configurada)" }}</code>
        <span v-if="!apiBaseOk" style="color:#b00020; font-weight:600;">(falta VITE_API_URL en .env)</span>
      </p>

      <details style="margin-top: 10px;">
        <summary style="cursor:pointer;">📌 Descripción del funcionamiento</summary>
        <div style="margin-top: 8px; line-height: 1.55;">
          <ul style="margin: 0; padding-left: 18px;">
            <li>
              <b>Entrada</b>: define el estado inicial del rover (posición <b>(x,y)</b> + <b>dirección</b>).
            </li>
            <li>
              <b>Comandos</b>: se ejecutan en orden, uno a uno (F/L/R).
            </li>
            <li>
              <b>Obstáculos</b>: casillas bloqueadas. Antes de cada avance, se comprueba la casilla siguiente; si está ocupada,
              el rover se detiene, se aborta la secuencia y se informa del punto bloqueado.
            </li>
            <li>
              <b>Límites del mapa</b>: el rover no puede salir del rango 0–199. Si un avance intentara salir, se aborta la ejecución.
            </li>
          </ul>
        </div>
      </details>
    </header>

    <!-- BOTONES DE EJEMPLOS -->
    <section style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom: 12px;">
      <button @click="cargarEjemplo('obstaculo')" type="button">Ejemplo: con obstáculo</button>
      <button @click="cargarEjemplo('sinObstaculo')" type="button">Ejemplo: sin obstáculo</button>
      <button @click="limpiar" type="button">Limpiar</button>
    </section>

    <!-- ENTRADA -->
    <section style="border:1px solid #ddd; border-radius: 8px; padding: 12px;">
      <h2 style="margin: 0 0 10px; font-size: 18px;">Entrada</h2>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
        <label>
          X (0–199)
          <input v-model.number="x" type="number" min="0" max="199" />
          <div v-if="errores.x" style="color:#b00020; font-size: 12px;">{{ errores.x }}</div>
        </label>

        <label>
          Y (0–199)
          <input v-model.number="y" type="number" min="0" max="199" />
          <div v-if="errores.y" style="color:#b00020; font-size: 12px;">{{ errores.y }}</div>
        </label>

        <label>
          Dirección inicial
          <select v-model="direccion">
            <option>N</option>
            <option>S</option>
            <option>E</option>
            <option>W</option>
          </select>
        </label>

        <label>
          Comandos (solo F/L/R)
          <input v-model="comandos" placeholder="Ej: FFRRFFFRL" />
          <div style="font-size: 12px; opacity: 0.8;">Se aceptan minúsculas.</div>
          <div v-if="errores.comandos" style="color:#b00020; font-size: 12px;">{{ errores.comandos }}</div>
        </label>
      </div>

      <div style="margin-top: 12px;">
        <label>
          Obstáculos (JSON)
          <textarea v-model="obstaculosTexto" rows="4" style="width:100%;"></textarea>
        </label>
        <div style="font-size: 12px; opacity: 0.8;">
          Formato: <code>[{"x":0,"y":2},{"x":10,"y":10}]</code>
        </div>
        <div v-if="errores.obstaculos" style="color:#b00020; font-size: 12px;">{{ errores.obstaculos }}</div>
      </div>
    </section>

    <!-- MODO PASOS -->
    <section style="margin-top: 14px; border:1px solid #ddd; border-radius: 8px; padding: 12px;">
      <h2 style="margin: 0 0 10px; font-size: 18px;">Ejecución paso a paso</h2>

      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <button type="button" @click="iniciarPasos" :disabled="!formularioOk || pasosActivos">
          Iniciar
        </button>

        <button type="button" @click="siguientePaso" :disabled="!pasosActivos || terminadoPasos">
          Siguiente paso
        </button>

        <button type="button" @click="ejecutarTodo" :disabled="!pasosActivos || terminadoPasos">
          Ejecutar todo
        </button>

        <button type="button" @click="resetPasos" :disabled="!pasosActivos">
          Reiniciar
        </button>

        <span v-if="!formularioOk" style="color:#b00020; font-size: 12px;">
          Corrige los errores para iniciar.
        </span>
      </div>

      <div style="margin-top: 10px; display:flex; gap:18px; flex-wrap:wrap;">
        <div style="font-size: 14px;">
          <b>Estado actual:</b>
          ({{ posX }}, {{ posY }}) mirando <b>{{ dirActual }}</b> <span style="opacity:0.85;">{{ simboloDir(dirActual) }}</span>
        </div>

        <div style="font-size: 14px;">
          <b>Progreso:</b> {{ indicePaso }} / {{ totalPasos }}
          <span v-if="comandoActual" style="margin-left: 6px;">
            | <b>Comando:</b> <code>{{ comandoActual }}</code>
          </span>
        </div>

        <div v-if="abortadoPasos" style="color:#b00020; font-weight:700;">
          Abortado: {{ motivoAbortado }}
        </div>

        <div v-if="terminadoOk" style="color:#0a7a2d; font-weight:700;">
          Finalizado sin incidencias ✅
        </div>
      </div>

      <!-- RADAR -->
      <div style="margin: 14px 0 6px;">
        <h3 style="margin: 0 0 8px;">Mini-mapa</h3>

        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom: 8px;">
          <label style="display:flex; gap:8px; align-items:center;">
            Radio:
            <input v-model.number="radioMapa" type="number" min="2" max="15" style="width:70px;" />
            <span style="font-size:12px; opacity:0.8;">(5 = 11x11)</span>
          </label>

          <label style="display:flex; gap:8px; align-items:center;">
            <input type="checkbox" v-model="centrarEnRover" />
            Centrar en el rover
          </label>

          <div style="font-size:12px; opacity:0.85;">
            Centro del mapa: ({{ centroRadarX }}, {{ centroRadarY }})
          </div>
        </div>

        <div
          style="
            display: grid;
            gap: 2px;
            user-select: none;
            width: fit-content;
            border: 1px solid #ddd;
            padding: 6px;
            border-radius: 8px;
            background: #fafafa;
          "
          :style="{ gridTemplateColumns: `repeat(${radioMapa * 2 + 1}, 26px)` }"
        >
          <div
            v-for="celda in celdasMapa"
            :key="celda.key"
            :title="celda.title"
            :style="{
              width: '26px',
              height: '26px',
              display: 'grid',
              placeItems: 'center',
              border: '1px solid #eee',
              borderRadius: '6px',
              fontSize: '14px',
              fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, Liberation Mono, Courier New, monospace',

                fontSize: '16px',

              background: celda.fueraMapa ? '#f2f2f2' : (celda.esRover ? '#e8f2ff' : 'white'),
              fontWeight: celda.esRover ? '800' : '400',
              opacity: celda.fueraMapa ? 0.6 : 1
            }"
          >
            {{ celda.simbolo }}
          </div>
        </div>

        <div style="margin-top: 8px; font-size: 12px; opacity: 0.9;">
          Leyenda: rover = ↑↓→←, obstáculo = ■, vacío = ·, fuera de mapa = ×
        </div>
      </div>

      <!-- LOG PASOS -->
      <details style="margin-top: 10px;">
        <summary style="cursor:pointer;">📜 Registro de ejecución</summary>
        <div style="margin-top: 8px;">
          <div v-if="historial.length === 0" style="opacity:0.7;">(Sin movimientos todavía)</div>
          <ul v-else style="margin: 0; padding-left: 18px; line-height: 1.45;">
            <li v-for="(linea, idx) in historial" :key="idx">{{ linea }}</li>
          </ul>
        </div>
      </details>
    </section>

    <!-- SIMULACIÓN API -->
    <section style="margin-top: 14px;">
      <h2 style="margin: 0 0 8px; font-size: 18px;">Simulación final (backend)</h2>

      <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <button @click="simularApi" :disabled="cargandoApi || !formularioOk || !apiBaseOk" type="button">
          {{ cargandoApi ? "Simulando..." : "Simular (API)" }}
        </button>
        <span v-if="!apiBaseOk" style="color:#b00020; font-size: 12px;">Configura VITE_API_URL en .env</span>
      </div>

      <pre v-if="resultadoApi" style="margin-top:12px; background:#f6f6f6; padding:12px; border-radius: 8px; overflow:auto;">{{ resultadoApi }}</pre>

      <div v-if="errorApi" style="margin-top: 10px; color:#b00020; font-weight:600;">
        {{ errorApi }}
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";

// ------------------ INPUTS ------------------
const x = ref(0);
const y = ref(0);
const direccion = ref("N");
const comandos = ref("FFRFF");
const obstaculosTexto = ref('[{"x":0,"y":2}]');

// Radar
const radioMapa = ref(5);
const centrarEnRover = ref(false);

// ------------------ API ------------------
const apiBase = import.meta.env.VITE_API_URL || "";
const apiBaseOk = computed(() => Boolean(apiBase));
const cargandoApi = ref(false);
const resultadoApi = ref(null);
const errorApi = ref(null);

// ------------------ PASOS ------------------
const pasosActivos = ref(false);
const posX = ref(0);
const posY = ref(0);
const dirActual = ref("N");

const anclaX = ref(0);
const anclaY = ref(0);

const comandosSnapshot = ref([]);
const indicePaso = ref(0);
const historial = ref([]);

const abortadoPasos = ref(false);
const obstaculoEncontrado = ref(null);
const motivoAbortado = ref("");

// ------------------ VALIDACIONES ------------------
const errores = computed(() => {
  const e = { x: "", y: "", comandos: "", obstaculos: "" };

  if (!Number.isInteger(x.value) || x.value < 0 || x.value > 199) e.x = "X debe ser un entero entre 0 y 199.";
  if (!Number.isInteger(y.value) || y.value < 0 || y.value > 199) e.y = "Y debe ser un entero entre 0 y 199.";

  const cmd = (comandos.value || "").trim();
  if (!cmd) e.comandos = "Comandos es obligatorio.";
  else if (!/^[FfLlRr]+$/.test(cmd)) e.comandos = "Solo se permiten letras F, L y R (sin espacios).";

  const txt = (obstaculosTexto.value || "").trim();
  if (txt) {
    try {
      const parsed = JSON.parse(txt);
      if (!Array.isArray(parsed)) e.obstaculos = "Debe ser un array JSON: [ {x:0,y:2}, ... ].";
      else {
        for (const o of parsed) {
          if (typeof o !== "object" || o === null) {
            e.obstaculos = "Cada obstáculo debe ser un objeto con x e y.";
            break;
          }
          if (!Number.isInteger(o.x) || o.x < 0 || o.x > 199 || !Number.isInteger(o.y) || o.y < 0 || o.y > 199) {
            e.obstaculos = "Obstáculos: x e y deben ser enteros entre 0 y 199.";
            break;
          }
        }
      }
    } catch {
      e.obstaculos = "JSON inválido (revísalo).";
    }
  }

  return e;
});

const formularioOk = computed(() => {
  return !errores.value.x && !errores.value.y && !errores.value.comandos && !errores.value.obstaculos;
});

// ------------------ Obstáculos ------------------
const obstaculosParseados = computed(() => {
  const txt = (obstaculosTexto.value || "").trim();
  if (!txt) return [];
  try {
    const arr = JSON.parse(txt);
    return Array.isArray(arr) ? arr : [];
  } catch {
    return [];
  }
});

function dentroMapa(x0, y0) {
  return x0 >= 0 && x0 <= 199 && y0 >= 0 && y0 <= 199;
}

function simboloDir(d) {
  const D = (d || "N").toUpperCase();
  if (D === "N") return "↑";
  if (D === "S") return "↓";
  if (D === "E") return "→";
  if (D === "W") return "←";
  return "▲";
}

function girar(d, lado) {
  const orden = ["N", "E", "S", "W"];
  const i = orden.indexOf(d);
  if (i === -1) return d;
  if (lado === "R") return orden[(i + 1) % 4];
  return orden[(i - 1 + 4) % 4];
}

function siguientePosicion(x0, y0, d) {
  if (d === "N") return [x0, y0 + 1];
  if (d === "S") return [x0, y0 - 1];
  if (d === "E") return [x0 + 1, y0];
  if (d === "W") return [x0 - 1, y0];
  return [x0, y0];
}

// ------------------ Radar ------------------
const centroRadarX = computed(() => (centrarEnRover.value ? posX.value : (pasosActivos.value ? anclaX.value : x.value)));
const centroRadarY = computed(() => (centrarEnRover.value ? posY.value : (pasosActivos.value ? anclaY.value : y.value)));

const setObstaculos = computed(() => {
  return new Set(
    obstaculosParseados.value
      .filter(o => o && Number.isInteger(o.x) && Number.isInteger(o.y))
      .map(o => `${o.x},${o.y}`)
  );
});

const celdasMapa = computed(() => {
  const r = Math.max(2, Math.min(15, Number(radioMapa.value) || 5));
  const cx = Number.isFinite(centroRadarX.value) ? centroRadarX.value : 0;
  const cy = Number.isFinite(centroRadarY.value) ? centroRadarY.value : 0;

  const celdas = [];
  for (let dy = r; dy >= -r; dy--) {
    for (let dx = -r; dx <= r; dx++) {
      const mx = cx + dx;
      const my = cy + dy;

      const fueraMapa = !dentroMapa(mx, my);
      const hayObs = !fueraMapa && setObstaculos.value.has(`${mx},${my}`);
      const esRover = mx === posX.value && my === posY.value;

      let simbolo = "·";
      if (fueraMapa) simbolo = "×";
      else if (hayObs) simbolo = "■";
      if (esRover) simbolo = simboloDir(dirActual.value);

      celdas.push({
        key: `${dx},${dy}`,
        simbolo,
        esRover,
        fueraMapa,
        title: `(${mx}, ${my})` + (fueraMapa ? " [fuera de mapa]" : "") + (hayObs ? " [obstáculo]" : "") + (esRover ? " [rover]" : ""),
      });
    }
  }
  return celdas;
});

// ------------------ Estado pasos ------------------
const totalPasos = computed(() => comandosSnapshot.value.length);
const comandoActual = computed(() => (indicePaso.value < totalPasos.value ? comandosSnapshot.value[indicePaso.value] : ""));
const terminadoPasos = computed(() => abortadoPasos.value || indicePaso.value >= totalPasos.value);
const terminadoOk = computed(() => pasosActivos.value && !abortadoPasos.value && indicePaso.value >= totalPasos.value);

// Mantener el rover “en la entrada” cuando NO estamos en modo pasos
watch([x, y, direccion], () => {
  if (!pasosActivos.value) {
    posX.value = x.value;
    posY.value = y.value;
    dirActual.value = (direccion.value || "N").toUpperCase();
    anclaX.value = x.value;
    anclaY.value = y.value;
  }
}, { immediate: true });

// ------------------ Acciones ------------------
function limpiar() {
  x.value = 0;
  y.value = 0;
  direccion.value = "N";
  comandos.value = "";
  obstaculosTexto.value = "";
  resultadoApi.value = null;
  errorApi.value = null;
  resetPasos();
}

function cargarEjemplo(tipo) {
  resultadoApi.value = null;
  errorApi.value = null;

  if (tipo === "obstaculo") {
    x.value = 0;
    y.value = 0;
    direccion.value = "N";
    comandos.value = "FFRFF";
    obstaculosTexto.value = '[{"x":0,"y":2}]';
  }

  if (tipo === "sinObstaculo") {
    x.value = 20;
    y.value = 2;
    direccion.value = "N";
    comandos.value = "FFRFF";
    obstaculosTexto.value = '[{"x":0,"y":2}]';
  }

  resetPasos();
}

function iniciarPasos() {
  if (!formularioOk.value) return;

  pasosActivos.value = true;
  abortadoPasos.value = false;
  obstaculoEncontrado.value = null;
  motivoAbortado.value = "";
  historial.value = [];
  indicePaso.value = 0;

  posX.value = x.value;
  posY.value = y.value;
  dirActual.value = (direccion.value || "N").toUpperCase();

  anclaX.value = x.value;
  anclaY.value = y.value;

  comandosSnapshot.value = (comandos.value || "").trim().toUpperCase().split("");

  historial.value.push(`Inicio en (${posX.value}, ${posY.value}) mirando ${dirActual.value} ${simboloDir(dirActual.value)}`);
}

function resetPasos() {
  pasosActivos.value = false;
  abortadoPasos.value = false;
  obstaculoEncontrado.value = null;
  motivoAbortado.value = "";
  historial.value = [];
  indicePaso.value = 0;
  comandosSnapshot.value = [];
}

function siguientePaso() {
  if (!pasosActivos.value || terminadoPasos.value) return;

  const cmd = comandoActual.value;
  if (!cmd) return;

  if (cmd === "L" || cmd === "R") {
    const antes = dirActual.value;
    dirActual.value = girar(dirActual.value, cmd);
    historial.value.push(`Paso ${indicePaso.value + 1}: ${cmd} (giro) ${antes} → ${dirActual.value}`);
    indicePaso.value += 1;
    return;
  }

  if (cmd === "F") {
    const [nx, ny] = siguientePosicion(posX.value, posY.value, dirActual.value);

    // Límite del mapa
    if (!dentroMapa(nx, ny)) {
      abortadoPasos.value = true;
      obstaculoEncontrado.value = { x: nx, y: ny };
      motivoAbortado.value = `límite del mapa al intentar ir a (${nx}, ${ny}).`;
      historial.value.push(`Paso ${indicePaso.value + 1}: F → LÍMITE al intentar (${nx}, ${ny}). Se aborta.`);
      return;
    }

    // Obstáculo antes de mover
    if (setObstaculos.value.has(`${nx},${ny}`)) {
      abortadoPasos.value = true;
      obstaculoEncontrado.value = { x: nx, y: ny };
      motivoAbortado.value = `obstáculo en (${nx}, ${ny}).`;
      historial.value.push(`Paso ${indicePaso.value + 1}: F → OBSTÁCULO en (${nx}, ${ny}). Se aborta.`);
      return;
    }

    posX.value = nx;
    posY.value = ny;
    historial.value.push(`Paso ${indicePaso.value + 1}: F → (${nx}, ${ny}) mirando ${dirActual.value}`);
    indicePaso.value += 1;
  }
}

function ejecutarTodo() {
  while (pasosActivos.value && !terminadoPasos.value) {
    siguientePaso();
  }
}

async function simularApi() {
  errorApi.value = null;
  resultadoApi.value = null;

  if (!formularioOk.value) {
    errorApi.value = "Hay errores en el formulario.";
    return;
  }
  if (!apiBaseOk.value) {
    errorApi.value = "Falta VITE_API_URL en el .env del frontend.";
    return;
  }

  let obstaculos = [];
  const txt = (obstaculosTexto.value || "").trim();
  if (txt) obstaculos = JSON.parse(txt);

  cargandoApi.value = true;

  try {
    const res = await fetch(`${apiBase}/api/rover/simular`, {
      method: "POST",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({
        x: x.value,
        y: y.value,
        direccion: direccion.value,
        comandos: comandos.value,
        obstaculos,
      }),
    });

    const texto = await res.text();
    const data = texto ? JSON.parse(texto) : null;

    if (!res.ok) {
      errorApi.value = data ? JSON.stringify(data, null, 2) : `Error HTTP ${res.status}`;
      return;
    }

    resultadoApi.value = JSON.stringify(data, null, 2);
  } catch {
    errorApi.value = "No se pudo conectar con la API. ¿Está Laravel levantado? (php artisan serve)";
  } finally {
    cargandoApi.value = false;
  }
}
</script>
