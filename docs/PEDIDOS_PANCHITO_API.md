## API Backend - Pedidos Panchito

Proyecto: Pedidos Panchito UI / Backend

---

Índice

- Mesas
- Pedidos
- Menú
- Dashboard
- Códigos de Error
- Notas

---

Mesas (Tables)

GET /tables
Lista las mesas y su estado.

Headers:

```
Authorization: Bearer {token}
Accept: application/json
```

Query params opcionales:
- `status` = `free`|`busy`
- `limit` (default 50)
- `page` (default 1)

Response 200 (ejemplo):

```json
{
  "success": true,
  "data": {
    "tables": [
      { "name": 1, "status": "free", "currentOrders": [], "totalAmount": 0 },
      { "name": 2, "status": "busy", "currentOrders": [{ "id": "order-123", "totalAmount": 153.5 }], "totalAmount": 153.5 }
    ],
    "pagination": { "total": 20, "page": 1, "limit": 50 }
  }
}
```

GET /tables/{tableId}
Detalles de una mesa específica.

Headers:

```
Authorization: Bearer {token}
Accept: application/json
```

Response 200 (ejemplo):

```json
{
  "success": true,
  "data": {
    "name": "M2",
    "status": "busy",
    "currentOrders": [
      {
        "items": [{ "name": "Pachamanca", "quantity": 2, "pricePerUnit": 60, "subtotal": 120 }],
        "totalAmount": 120,
        "createdAt": "2026-02-23T11:30:00Z"
      }
    ],
    "totalAmount": 153.5
  }
}
```

Response 404:

```json
{ "success": false, "message": "Mesa no encontrada" }
```

POST /tables
Crear una mesa (admin).

Headers:

```
Content-Type: application/json
Authorization: Bearer {token}
```

Body:

```json
{ "name": "M3"}
```

Response 201:

```json
{ "success": true, "data": { "name": "M3", "status": "free" } }
```

Pedidos (Orders)

POST /orders
Crear pedido para una mesa.

Headers:

```
Content-Type: application/json
Authorization: Bearer {token}
```

Body:

```json
{
  "items": [
    { "itemName": "comida-1", "quantity": 2 },
    { "itemName": "bebida-4", "quantity": 3 }
  ],
}
```

Response 201:

```json
{
  "success": true,
  "data": {
    "tableId": "1",
    "items": [],
    "subtotal": 141,
    "totalAmount": 153.5,
    "createdAt": "2026-02-23T11:30:00Z"
  }
}
```

Response 400 (ejemplo):

```json
{ "success": false, "message": "Items vacíos", "errors": { "items": ["Debe seleccionar al menos un item"] } }
```

GET /tables/{tableId}/orders
Listar pedidos de una mesa.

Headers:

```
Authorization: Bearer {token}
Accept: application/json
```

Response 200 (ejemplo):

```json
{ "success": true, "data": { "tableId": "1", "orders": [] } }
```

GET /orders/{orderId}
Obtener detalles de un pedido.

Headers:

```
Authorization: Bearer {token}
Accept: application/json
```

Response 200: pedido completo con items, subtotales y estados por item.

PUT /orders/{orderId}
Actualizar un pedido existente (agregar/modificar items).

Headers:

```
Content-Type: application/json
Authorization: Bearer {token}
```

Body (ejemplo):

```json
{ "items": [{ "itemName": "extra-1", "quantity": 1 }] }
```

Response 200: pedido actualizado con totales recalculados.

----

Menú (Menu)

GET /menu/items
Listar items del menú (filtrado por categoría/ búsqueda).

Headers:

```
Authorization: Bearer {token}
Accept: application/json
```

Query params:
- `category` = `comidas|bebidas|extras`
- `search` (string)
- `available` (true|false)

Response 200: listado de items con campos: `id`, `name`, `category`, `price`, `descripcion`, `image`, `available`, `createdAt`.


POST /menu/items (admin)
Crear item (multipart/form-data para subir imagen).

Headers:

```
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

Body form fields:
- `name`, `category`, `price`, `descripcion`, `image` (file), `available`

Response 201: item creado.

PUT /menu/items/{itemId} (admin)
Actualizar item.

Headers:

```
Content-Type: application/json
Authorization: Bearer {token}
```

Body ejemplo:

```json
{ "price": 65, "available": false }
```

Response 200: item actualizado.

---

Dashboard

GET /dashboard/stats
Estadísticas generales (total orders, revenue, mesas activas, promedio, top dish).

Headers:
```
Authorization: Bearer {token}
Accept: application/json
```

Response 200: objeto con métricas.

GET /dashboard/monthly-overview?months=4
Resumen mensual para gráficas.

Response 200: arreglo con meses, revenue y orderCount.

GET /dashboard/recent-activity?limit=5
Actividad reciente del sistema (pedidos creados/pagados, mesas liberadas).

Response 200: lista de actividades.

Códigos de Error Estándar

- 200 OK
- 201 Created
- 204 No Content
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 422 Unprocessable Entity (validación)
- 500 Internal Server Error

En respuestas de error incluir siempre:

```json
{ "success": false, "message": "Descripción", "errors": { /* opcional */ } }
```

Notas y recomendaciones para backend

- Todas las fechas en ISO 8601 (UTC).
- Montos monetarios en Soles (S/), usar decimal con 2 dígitos.
- Validación de body con errores 422 que incluyan detalles por campo.
- Para crear/actualizar pedidos y items, recalcular totales en backend (no confiar en cliente).
- Considerar eventos o webhooks (Socket/Realtime) para notificar dashboard y cocina cuando se crea o actualiza un pedido.
- Limitar la paginación por default (50) y devolver metadatos de paginación.

Ejemplos de flujo (resumen)

1. Usuario (admin/mesero) hace login → obtiene JWT.
2. Listar mesas (`GET /tables`) → ver estado.
3. Click en mesa libre → abrir detalles (`GET /tables/{id}`) → subir items y `POST /orders`.
4. Pedido creado → mesa pasa a `busy` y `totalAmount` se actualiza.
5. Pedido puede actualizarse con `PUT /orders/{orderId}` (botón "Editar").
6. Pago con `POST /orders/{orderId}/pay` → marca pedido `pagado` y mesa `free`.
