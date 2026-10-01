# 🇻🇪 Dolar & Euro API — Venezuela

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![CORS: Enabled](https://img.shields.io/badge/CORS-Enabled-brightgreen.svg)](#)
[![Format: JSON](https://img.shields.io/badge/Format-JSON-orange.svg)](#)

API REST ultraligera, autónoma y sin dependencias externas en PHP, diseñada para consultar las tasas oficiales de cambio del **Banco Central de Venezuela (BCV)** y del mercado **Paralelo** (USD y EUR).

---

## ⚡ Características Principales

* **Cero dependencias:** No requiere Composer, bases de datos complejas ni frameworks pesados. Funciona con PHP nativo con la extensión `cURL`.
* **Vigencia Oficial Estricta (BCV y Feriados):** La API extrae la **Fecha Valor** oficial estipulada en el portal del BCV (`YYYY-MM-DD`). Si el BCV publica una tasa para un día posterior (por ejemplo, en la tarde para el día siguiente, para el lunes o tras múltiples días feriados/asueto bancario), la API mantiene activa la tasa del día en curso. La nueva tasa entra en vigencia única y automáticamente a partir de la medianoche (00:00 hora de Venezuela `America/Caracas`) de la fecha exacta indicada por el BCV.
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
| `GET` | `/v1/dolares` | Lista con cotizaciones vigentes del Dólar USD (Oficial BCV y Paralelo) |
| `GET` | `/v1/dolares/oficial` | Cotización individual del Dólar Oficial BCV vigente para el día |
| `GET` | `/v1/dolares/oficial/siguiente` | Cotización oficial BCV de la próxima fecha valor (disponible tras publicación de la tarde) |
| `GET` | `/v1/dolares/paralelo` | Cotización individual del Dólar Paralelo |
| `GET` | `/v1/euros` | Lista con cotizaciones vigentes del Euro EUR (Oficial BCV y Paralelo) |
| `GET` | `/v1/euros/oficial` | Cotización individual del Euro Oficial BCV vigente para el día |
| `GET` | `/v1/euros/oficial/siguiente` | Cotización oficial BCV de la próxima fecha valor (disponible tras publicación de la tarde) |
| `GET` | `/v1/euros/paralelo` | Cotización individual del Euro Paralelo |
| `GET` | `/v1/cotizaciones` | Resumen oficial del BCV vigente del día (Dólar y Euro) |
| `GET` | `/v1/cotizaciones/siguiente` | Resumen oficial del BCV para la próxima fecha valor |
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
    "promedio": 860.1753,
    "fechaActualizacion": "2026-10-01T00:00:00-04:00"
  },
  {
    "moneda": "USD",
    "fuente": "paralelo",
    "nombre": "Paralelo",
    "compra": null,
    "venta": null,
    "promedio": 954.1634,
    "fechaActualizacion": "2026-10-01T15:30:11.000Z"
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
  "promedio": 860.1753,
  "fechaActualizacion": "2026-10-01T00:00:00-04:00"
}
```

### `GET /v1/dolares/oficial/siguiente` (Tras publicación de la tarde)
```json
{
  "moneda": "USD",
  "fuente": "oficial",
  "nombre": "Dólar (Siguiente Día Hábil)",
  "compra": null,
  "venta": null,
  "promedio": 865.5000,
  "fechaActualizacion": "2026-10-02T00:00:00-04:00"
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

Si deseas que la caché se actualice automáticamente en segundo plano sin esperar a que un usuario realice una petición, puedes crear un Cron Job en tu hosting ejecutando cada 15 o 30 minutos:

```bash
curl -s https://api-dolar.leandrus.net/v1/dolares > /dev/null
```

---

## 🔎 Fuentes de Información

* **Tasa Oficial (BCV):** Extraída directamente del sitio oficial del [Banco Central de Venezuela](https://www.bcv.org.ve/). La API respeta la fecha valor oficial y entrega la tasa correspondiente al día actual de Venezuela, activando la nueva tasa a la medianoche (00:00 VET).
* **Tasa Paralela:** Obtenida a través de la API pública de cotizaciones de [Yadio](https://api.yadio.io/).

---

## 📄 Licencia

Este proyecto se distribuye bajo la licencia [MIT](LICENSE). Siéntete libre de utilizarlo, modificarlo e integrarlo en tus proyectos comerciales o personales.
