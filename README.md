# API Dólar y Euro Venezuela (Hostinger)

API ultraligera en PHP diseñada para alojarse en **Hostinger** (Hosting Compartido) bajo el subdominio `api-dolar.leandrus.net`.

---

## 🚀 Pasos de Instalación en Hostinger

### 1. Crear el Subdominio en hPanel
1. Ingresa a tu panel de **Hostinger (hPanel)**.
2. Ve a **Sitios web** > Administrar `leandrus.net`.
3. En la barra de búsqueda o menú lateral, busca **Subdominios**.
4. En **Crear un nuevo subdominio**:
   * Nombre: `api-dolar`
   * Carpeta de destino: Marca la casilla personalizada y asegúrate de que sea `public_html/api-dolar`.
   * Clic en **Crear**.

### 2. Subir los Archivos
1. En hPanel, ve a **Archivos** > **Administrador de Archivos**.
2. Entra a la carpeta: `public_html/api-dolar/`
3. Sube únicamente estos **2 archivos** que están en esta carpeta:
   * `index.php`
   * `.htaccess` (asegúrate de que los archivos ocultos con punto sean visibles o súbelo directamente).

### 3. Activar Certificado SSL (HTTPS)
1. En hPanel, busca la sección **Seguridad** > **SSL**.
2. Confirma que `api-dolar.leandrus.net` tenga su certificado SSL activo.
3. Activa la opción **Forzar HTTPS**.

---

## 📌 Endpoints Disponibles

Una vez subido, tus otros proyectos pueden consumir directamente:

| Método | Endpoint | Descripción |
| :--- | :--- | :--- |
| `GET` | `https://api-dolar.leandrus.net/v1/dolares` | Lista de cotizaciones USD (Oficial BCV y Paralelo) |
| `GET` | `https://api-dolar.leandrus.net/v1/dolares/oficial` | Cotización Dólar Oficial BCV |
| `GET` | `https://api-dolar.leandrus.net/v1/dolares/paralelo` | Cotización Dólar Paralelo |
| `GET` | `https://api-dolar.leandrus.net/v1/euros` | Lista de cotizaciones EUR (Oficial BCV y Paralelo) |
| `GET` | `https://api-dolar.leandrus.net/v1/euros/oficial` | Cotización Euro Oficial BCV |
| `GET` | `https://api-dolar.leandrus.net/v1/euros/paralelo` | Cotización Euro Paralelo |
| `GET` | `https://api-dolar.leandrus.net/v1/cotizaciones` | Cotizaciones Oficiales BCV (Dólar y Euro) |

---

## ⚙️ Características Técnicas
* **CORS Habilitado:** Cabeceras `Access-Control-Allow-Origin: *` incluidas para peticiones desde aplicaciones React, Vue, Flutter, móviles o frontend web.
* **Caché Automática (15 min):** Guarda un archivo `cache.json` local. No satura al BCV ni a Yadio y responde en milisegundos.
* **Tolerancia a Caídas:** Si la web del BCV se cae temporalmente, la API seguirá respondiendo con el último valor válido en caché en lugar de dar error 500.
* **Protección .htaccess:** El archivo de caché no es accesible directamente desde el navegador web.
