# 🚀 Mars Rover Simulator
**Laravel API + Vue**

Proyecto de prueba en el que se simula el movimiento de un rover sobre una cuadrícula de **200x200**
(coordenadas de **0 a 199**) a partir de una secuencia de comandos.

---

## 🕹️ Comandos

- **F** → Avanzar una casilla en la dirección actual  
- **L** → Girar 90º a la izquierda  
- **R** → Girar 90º a la derecha  

---

## ⚠️ Reglas

- Antes de cada avance se comprueba si la siguiente casilla contiene un **obstáculo**.
- Si se detecta un obstáculo, la simulación se **aborta** y se devuelve la posición anterior.
- El rover no puede salir de los límites del mapa (**0–199**).

---

## 📁 Estructura del repositorio

- `mars-rover-api/` → Backend desarrollado en **Laravel**
- `mars-rover-web/` → Frontend desarrollado en **Vue**

---

## 🧰 Requisitos

- PHP **8.x**
- Composer
- Node.js
- npm

---

## ▶️ Cómo ejecutarlo

### 1️⃣ Backend (Laravel)

```bash
cd mars-rover-api
composer install
php artisan key:generate
php artisan serve
```

La API se levanta por defecto en:

```
http://127.0.0.1:8000
```

---

### 2️⃣ Frontend (Vue)

```bash
cd mars-rover-web
npm install
npm run dev
```

El frontend se abre en:

```
http://localhost:5173
```

---

## ⚙️ Configuración del Frontend

En la carpeta `mars-rover-web` crea (o edita) el archivo `.env` con el siguiente contenido:

```env
VITE_API_URL=http://127.0.0.1:8000
```

⚠️ **Importante**  
Después de modificar el archivo `.env`, es necesario reiniciar el servidor de Vue.

---

## 🔌 API (Laravel)

### Endpoint

```
POST /api/rover/simular
```

Este endpoint recibe el estado inicial del rover, los comandos y los obstáculos, y devuelve el estado final de la simulación.

---

### 📤 Ejemplo de petición

```json
{
  "x": 0,
  "y": 0,
  "direccion": "N",
  "comandos": "FFRFF",
  "obstaculos": [
    { "x": 0, "y": 2 }
  ]
}
```

---

### 📥 Ejemplo de respuesta

```json
{
  "x": 0,
  "y": 1,
  "direccion": "N",
  "abortado": true,
  "obstaculo": {
    "x": 0,
    "y": 2,
    "motivo": "OBSTACULO"
  }
}
```

---

### 🧠 Campos importantes

- **abortado** → Indica si la ejecución se ha detenido antes de finalizar los comandos.
- **motivo** → Indica la causa del aborto:
  - `OBSTACULO`
  - `FUERA_DEL_MAPA`

---

## 🖥️ Frontend (Vue)

El frontend permite:

- Introducir la **posición inicial** del rover y su **dirección**.
- Definir la **secuencia de comandos** (`F`, `L`, `R`).
- Añadir **obstáculos** en formato JSON.
- Ejecutar la simulación **paso a paso**.
- Visualizar el estado del rover en un **mini-mapa**.
- Mostrar el **resultado final** devuelto por la API.
