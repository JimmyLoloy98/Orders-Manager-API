# Endpoints API - Scrap Payments Manager

Este documento describe todos los endpoints (EP) necesarios para consumir desde la aplicación Scrap Payments Manager UI, basado en las funcionalidades implementadas.

## Base URL
```
/api/v1
```

## Autenticación

### POST /auth/login
**Descripción:** Iniciar sesión en la aplicación

**Request Body:**
```json
{
  "email": "string",
  "password": "string"
}
```

**Response:**
```json
{
  "user": {
    "id": "string",
    "email": "string",
    "name": "string",
    "companyId": "string",
    "company": {
      "id": "string",
      "name": "string",
      "createdAt": "date"
    },
    "avatar": "string (optional)"
  },
  "token": "string"
}
```

### POST /auth/logout
**Descripción:** Cerrar sesión

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

---

## Dashboard

### GET /dashboard/stats
**Descripción:** Obtener estadísticas generales del dashboard

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "totalActiveClients": "number",
  "totalCreditExtended": "number",
  "totalScrapPaymentsReceived": "number",
  "totalPendingDebt": "number"
}
```

### GET /dashboard/recent-activity
**Descripción:** Obtener actividad reciente (créditos y pagos)

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `limit` (optional): número de registros a retornar (default: 10)

**Response:**
```json
{
  "activities": [
    {
      "id": "string",
      "type": "credit" | "payment",
      "clientName": "string",
      "description": "string",
      "amount": "number",
      "date": "date"
    }
  ]
}
```

### GET /dashboard/monthly-overview
**Descripción:** Obtener datos para el gráfico de resumen mensual

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `months` (optional): número de meses a retornar (default: 6)

**Response:**
```json
{
  "data": [
    {
      "month": "string (MMM format)",
      "credits": "number",
      "payments": "number"
    }
  ]
}
```

---

## Clientes (Negocios)

### GET /clients
**Descripción:** Listar todos los clientes

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `origin` (optional): filtrar por lugar de procedencia
- `search` (optional): búsqueda por nombre, RUC, DNI, etc.
- `page` (optional): número de página (default: 1)
- `limit` (optional): registros por página (default: 10)

**Response:**
```json
{
  "clients": [
    {
      "id": "string",
      "companyId": "string",
      "name": "string",
      "ownerName": "string",
      "dni": "string",
      "ruc": "string",
      "businessName": "string",
      "photoUrl": "string (optional)",
      "phone": "string",
      "email": "string",
      "address": "string",
      "origin": "string",
      "notes": "string",
      "currentDebt": "number",
      "createdAt": "date"
    }
  ],
  "total": "number",
  "page": "number",
  "limit": "number"
}
```

### GET /clients/:id
**Descripción:** Obtener detalles de un cliente específico

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "ownerName": "string",
  "dni": "string",
  "ruc": "string",
  "businessName": "string",
  "photoUrl": "string (optional)",
  "phone": "string",
  "email": "string",
  "address": "string",
  "origin": "string",
  "notes": "string",
  "currentDebt": "number",
  "createdAt": "date"
}
```

### POST /clients
**Descripción:** Crear un nuevo cliente

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "string",
  "businessName": "string",
  "ownerName": "string",
  "dni": "string",
  "ruc": "string",
  "phone": "string",
  "email": "string",
  "address": "string",
  "origin": "string",
  "notes": "string",
  "photoUrl": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "businessName": "string",
  "ownerName": "string",
  "dni": "string",
  "ruc": "string",
  "phone": "string",
  "email": "string",
  "address": "string",
  "origin": "string",
  "notes": "string",
  "photoUrl": "string (optional)",
  "currentDebt": "number",
  "createdAt": "date"
}
```

### PUT /clients/:id
**Descripción:** Actualizar un cliente existente

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "string (optional)",
  "businessName": "string (optional)",
  "ownerName": "string (optional)",
  "dni": "string (optional)",
  "ruc": "string (optional)",
  "phone": "string (optional)",
  "email": "string (optional)",
  "address": "string (optional)",
  "origin": "string (optional)",
  "notes": "string (optional)",
  "photoUrl": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "businessName": "string",
  "ownerName": "string",
  "dni": "string",
  "ruc": "string",
  "phone": "string",
  "email": "string",
  "address": "string",
  "origin": "string",
  "notes": "string",
  "photoUrl": "string (optional)",
  "currentDebt": "number",
  "createdAt": "date",
  "updatedAt": "date"
}
```

### DELETE /clients/:id
**Descripción:** Eliminar un cliente

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Client deleted successfully"
}
```

### GET /clients/:id/summary
**Descripción:** Obtener resumen financiero de un cliente

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "totalCredit": "number",
  "totalPaid": "number",
  "pendingDebt": "number",
  "creditsCount": "number",
  "paymentsCount": "number"
}
```

---

## Créditos

### GET /credits
**Descripción:** Listar todos los créditos

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `clientId` (optional): filtrar por cliente
- `status` (optional): filtrar por estado (pending | partial | paid)
- `origin` (optional): filtrar por lugar de procedencia del cliente
- `startDate` (optional): fecha de inicio (ISO format)
- `endDate` (optional): fecha de fin (ISO format)
- `page` (optional): número de página
- `limit` (optional): registros por página

**Response:**
```json
{
  "credits": [
    {
      "id": "string",
      "companyId": "string",
      "clientId": "string",
      "clientName": "string",
      "clientOrigin": "string",
      "date": "date",
      "items": [
        {
          "description": "string",
          "price": "number"
        }
      ],
      "amount": "number",
      "status": "pending" | "partial" | "paid",
      "notes": "string",
      "createdAt": "date"
    }
  ],
  "total": "number",
  "page": "number",
  "limit": "number"
}
```

### GET /credits/:id
**Descripción:** Obtener detalles de un crédito específico

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "clientId": "string",
  "clientName": "string",
  "clientOrigin": "string",
  "date": "date",
  "items": [
    {
      "description": "string",
      "price": "number"
    }
  ],
  "amount": "number",
  "status": "pending" | "partial" | "paid",
  "notes": "string",
  "createdAt": "date"
}
```

### POST /credits
**Descripción:** Crear un nuevo crédito

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "clientId": "string",
  "date": "date (ISO format)",
  "items": [
    {
      "description": "string",
      "price": "number"
    }
  ],
  "notes": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "clientId": "string",
  "clientName": "string",
  "clientOrigin": "string",
  "date": "date",
  "items": [
    {
      "description": "string",
      "price": "number"
    }
  ],
  "amount": "number",
  "status": "pending",
  "notes": "string",
  "createdAt": "date"
}
```

### PUT /credits/:id
**Descripción:** Actualizar un crédito existente

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "date": "date (ISO format, optional)",
  "items": [
    {
      "description": "string",
      "price": "number"
    }
  ],
  "notes": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "clientId": "string",
  "clientName": "string",
  "clientOrigin": "string",
  "date": "date",
  "items": [
    {
      "description": "string",
      "price": "number"
    }
  ],
  "amount": "number",
  "status": "pending" | "partial" | "paid",
  "notes": "string",
  "createdAt": "date",
  "updatedAt": "date"
}
```

### PATCH /credits/:id/status
**Descripción:** Actualizar el estado de un crédito

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "status": "pending" | "partial" | "paid"
}
```

**Response:**
```json
{
  "id": "string",
  "status": "pending" | "partial" | "paid",
  "updatedAt": "date"
}
```

### GET /clients/:clientId/credits
**Descripción:** Obtener todos los créditos de un cliente específico

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "credits": [
    {
      "id": "string",
      "companyId": "string",
      "clientId": "string",
      "clientName": "string",
      "clientOrigin": "string",
      "date": "date",
      "items": [
        {
          "description": "string",
          "price": "number"
        }
      ],
      "amount": "number",
      "status": "pending" | "partial" | "paid",
      "notes": "string",
      "createdAt": "date"
    }
  ]
}
```

---

## Pagos con Chatarra

### GET /payments
**Descripción:** Listar todos los pagos con chatarra

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `clientId` (optional): filtrar por cliente
- `startDate` (optional): fecha de inicio (ISO format)
- `endDate` (optional): fecha de fin (ISO format)
- `page` (optional): número de página
- `limit` (optional): registros por página

**Response:**
```json
{
  "payments": [
    {
      "id": "string",
      "companyId": "string",
      "clientId": "string",
      "clientName": "string",
      "clientOrigin": "string",
      "date": "date",
      "items": [
        {
          "scrapId": "string",
          "scrapName": "string",
          "amount": "number"
        }
      ],
      "totalValue": "number",
      "notes": "string",
      "createdAt": "date"
    }
  ],
  "total": "number",
  "page": "number",
  "limit": "number"
}
```

### GET /payments/:id
**Descripción:** Obtener detalles de un pago específico

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "clientId": "string",
  "clientName": "string",
  "clientOrigin": "string",
  "date": "date",
  "items": [
    {
      "scrapId": "string",
      "scrapName": "string",
      "amount": "number"
    }
  ],
  "totalValue": "number",
  "notes": "string",
  "createdAt": "date"
}
```

### POST /payments
**Descripción:** Crear un nuevo pago con chatarra

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "clientId": "string",
  "date": "date (ISO format)",
  "items": [
    {
      "scrapId": "string",
      "amount": "number"
    }
  ],
  "notes": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "clientId": "string",
  "clientName": "string",
  "clientOrigin": "string",
  "date": "date",
  "items": [
    {
      "scrapId": "string",
      "scrapName": "string",
      "amount": "number"
    }
  ],
  "totalValue": "number",
  "notes": "string",
  "createdAt": "date"
}
```

### PUT /payments/:id
**Descripción:** Actualizar un pago existente

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "date": "date (ISO format, optional)",
  "items": [
    {
      "scrapId": "string",
      "amount": "number"
    }
  ],
  "notes": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "clientId": "string",
  "clientName": "string",
  "clientOrigin": "string",
  "date": "date",
  "items": [
    {
      "scrapId": "string",
      "scrapName": "string",
      "amount": "number"
    }
  ],
  "totalValue": "number",
  "notes": "string",
  "createdAt": "date",
  "updatedAt": "date"
}
```

### GET /clients/:clientId/payments
**Descripción:** Obtener todos los pagos de un cliente específico

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "payments": [
    {
      "id": "string",
      "companyId": "string",
      "clientId": "string",
      "clientName": "string",
      "clientOrigin": "string",
      "date": "date",
      "items": [
        {
          "scrapId": "string",
          "scrapName": "string",
          "amount": "number"
        }
      ],
      "totalValue": "number",
      "notes": "string",
      "createdAt": "date"
    }
  ]
}
```

---

## Tipos de Chatarra (Scraps)

### GET /scraps
**Descripción:** Listar todos los tipos de chatarra

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): búsqueda por nombre o descripción
- `page` (optional): número de página
- `limit` (optional): registros por página

**Response:**
```json
{
  "scraps": [
    {
      "id": "string",
      "companyId": "string",
      "name": "string",
      "description": "string",
      "createdAt": "date"
    }
  ],
  "total": "number",
  "page": "number",
  "limit": "number"
}
```

### GET /scraps/:id
**Descripción:** Obtener detalles de un tipo de chatarra específico

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "description": "string",
  "createdAt": "date"
}
```

### POST /scraps
**Descripción:** Crear un nuevo tipo de chatarra

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "string",
  "description": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "description": "string",
  "createdAt": "date"
}
```

### PUT /scraps/:id
**Descripción:** Actualizar un tipo de chatarra existente

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "string (optional)",
  "description": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "description": "string",
  "createdAt": "date",
  "updatedAt": "date"
}
```

### DELETE /scraps/:id
**Descripción:** Eliminar un tipo de chatarra

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Scrap type deleted successfully"
}
```

---

## Lugares de Procedencia (Origins)

### GET /origins
**Descripción:** Listar todos los lugares de procedencia

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): búsqueda por nombre o descripción
- `page` (optional): número de página
- `limit` (optional): registros por página

**Response:**
```json
{
  "origins": [
    {
      "id": "string",
      "companyId": "string",
      "name": "string",
      "description": "string",
      "createdAt": "date"
    }
  ],
  "total": "number",
  "page": "number",
  "limit": "number"
}
```

### GET /origins/:id
**Descripción:** Obtener detalles de un lugar de procedencia específico

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "description": "string",
  "createdAt": "date"
}
```

### POST /origins
**Descripción:** Crear un nuevo lugar de procedencia

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "string",
  "description": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "description": "string",
  "createdAt": "date"
}
```

### PUT /origins/:id
**Descripción:** Actualizar un lugar de procedencia existente

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "string (optional)",
  "description": "string (optional)"
}
```

**Response:**
```json
{
  "id": "string",
  "companyId": "string",
  "name": "string",
  "description": "string",
  "createdAt": "date",
  "updatedAt": "date"
}
```

### DELETE /origins/:id
**Descripción:** Eliminar un lugar de procedencia

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Origin deleted successfully"
}
```

---

## Códigos de Estado HTTP

- `200 OK`: Solicitud exitosa
- `201 Created`: Recurso creado exitosamente
- `400 Bad Request`: Solicitud inválida (datos faltantes o incorrectos)
- `401 Unauthorized`: No autenticado o token inválido
- `403 Forbidden`: No autorizado para realizar la acción
- `404 Not Found`: Recurso no encontrado
- `500 Internal Server Error`: Error del servidor

## Notas Adicionales

1. **Autenticación:** Todos los endpoints (excepto `/auth/login`) requieren un token de autenticación en el header `Authorization: Bearer {token}`.

2. **Fechas:** Todas las fechas deben enviarse en formato ISO 8601 (ej: `2024-01-24T10:30:00Z`).

3. **Paginación:** Los endpoints que soportan paginación retornan un objeto con `total`, `page`, y `limit` además de los datos.

4. **Filtros:** Los parámetros de query son opcionales y permiten filtrar los resultados según las necesidades.

5. **Validación:** El backend debe validar que:
   - Los IDs de cliente, crédito, pago, scrap y origin existan antes de crear relaciones
   - Los montos sean números positivos
   - Los campos requeridos estén presentes
   - El `companyId` se obtenga del token de autenticación (no debe enviarse en el body)

6. **Cálculo de Deuda:** El campo `currentDebt` de los clientes debe calcularse automáticamente como la diferencia entre el total de créditos y el total de pagos recibidos.

7. **Actualización de Estado:** Cuando se actualiza el estado de un crédito o se crea/actualiza un pago, el sistema debe recalcular automáticamente la deuda del cliente asociado.
