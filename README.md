# 🇻🇪 Dolar & Euro API — Venezuela

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![CORS: Enabled](https://img.shields.io/badge/CORS-Enabled-brightgreen.svg)](#)
[![Format: JSON](https://img.shields.io/badge/Format-JSON-orange.svg)](#)

API REST ultraligera, autónoma y sin dependencias externas en PHP, diseñada para consultar las tasas oficiales de cambio del **Banco Central de Venezuela (BCV)** y del mercado **Paralelo** (USD y EUR).

---

## ⚡ Características Principales

* **Cero dependencias:** No requiere Composer, bases de datos complejas ni frameworks pesados. Funciona con PHP nativo con la extensión `cURL`.
* **Caché Inteligente (*Lazy Cache*):** Guarda en disco local (`cache.json`) las cotizaciones durante 15 minutos (configurable). Reduce el consumo de CPU y responde en **< 5 milisegundos**.
* **Alta Disponibilidad y Tolerancia a Fallos:** Si el portal del BCV sufre caídas de servicio o bloqueos temporales de red, la API sirve automáticamente el último valor conocido garantizando 100% de uptime para tus aplicaciones.
* **Seguridad Reforzada:** Cabeceras HTTP (`X-Frame-Options`, `X-Content-Type-Options`), bloqueo de navegación en directorios, protección de archivos ocultos y validación de métodos HTTP.
* **CORS Habilitado:** Permite peticiones directas desde clientes web (React, Vue, Angular, Svelte), apps móviles (Flutter, React Native) o cualquier backend.
* **Compatible con LLMs:** Incluye especificación estándar en [`llms.txt`](llms.txt) para asistentes y agentes de inteligencia artificial.

---

## 📍 Endpoints Disponibles

La URL base en producción para este servicio es:  
`https://api-dolar.leandrus.net`

| Método | Endpoint | Descripción |
| :---: | :--- | :--- |
| `GET` | `/v1/dolares` | Lista con cotizaciones del Dólar USD (Oficial BCV y Paralelo) |
| `GET` | `/v1/dolares/oficial` | Cotización individual del Dólar Oficial BCV |
| `GET` | `/v1/dolares/paralelo` | Cotización individual del Dólar Paralelo |
| `GET` | `/v1/euros` | Lista con cotizaciones del Euro EUR (Oficial BCV y Paralelo) |
| `GET` | `/v1/euros/oficial` | Cotización individual del Euro Oficial BCV |
| `GET` | `/v1/euros/paralelo` | Cotización individual del Euro Paralelo |
| `GET` | `/v1/cotizaciones` | Resumen oficial del BCV (Dólar y Euro) |
| `GET` | `/` | Estado de la API, versión y listado de endpoints |

---

## 📦 Ejemplos de Respuesta JSON

### `GET /v1/dolares`
```json
[
  {
    "moneda": "USD",
    "fuente": "oficial",
    "nombre": "Dólar",
    "compra": null,
    "venta": null,
    "promedio": 859.0629,
    "fechaActualizacion": "2026-09-30T00:00:00-04:00"
  },
  {
    "moneda": "USD",
    "fuente": "paralelo",
    "nombre": "Paralelo",
    "compra": null,
    "venta": null,
    "promedio": 955.8631,
    "fechaActualizacion": "2026-09-30T15:02:43.000Z"
  }
]
```

### `GET /v1/dolares/oficial`
```json
{
  "moneda": "USD",
  "fuente": "oficial",
  "nombre": "Dólar",
  "compra": null,
  "venta": null,
  "promedio": 859.0629,
  "fechaActualizacion": "2026-09-30T00:00:00-04:00"
}
```

---

## 🛠️ Instalación y Despliegue

### Opción 1: Hosting Compartido (Hostinger, cPanel, Apache / LiteSpeed)

1. Crea el subdominio deseado (ej. `api-dolar.tudominio.com`) en tu panel de control y asígnale una carpeta pública (ej. `public_html/api-dolar`).
2. Sube únicamente los siguientes archivos a dicha carpeta:
   * `index.php`
   * `.htaccess`
   * `robots.txt` *(opcional)*
   * `llms.txt` *(opcional)*
3. Asegúrate de tener activo el certificado SSL (HTTPS) en tu panel.

### Opción 2: Servidor Local de Desarrollo

Si dispones de PHP instalado localmente, puedes iniciar el servidor de pruebas con un solo comando:

```bash
php -S localhost:8000 index.php
```

Luego abre en tu navegador o cliente HTTP: `http://localhost:8000/v1/dolares`

### Opción 3: Tarea Programada (Opcional - Mantener Caché Caliente)

Si deseas que la caché se actualice automáticamente en segundo plano sin esperar a que un usuario realice una petición, puedes crear un Cron Job en tu hosting ejecutando cada 30 o 60 minutos:

```bash
curl -s https://api-dolar.leandrus.net/v1/dolares > /dev/null
```

---

## 🔎 Fuentes de Información

* **Tasa Oficial (BCV):** Extraída directamente del sitio oficial del [Banco Central de Venezuela](https://www.bcv.org.ve/).
* **Tasa Paralela:** Obtenida a través de la API pública de cotizaciones de [Yadio](https://api.yadio.io/).

---

## 📄 Licencia

Este proyecto se distribuye bajo la licencia [MIT](LICENSE). Siéntete libre de utilizarlo, modificarlo e integrarlo en tus proyectos comerciales o personales.
